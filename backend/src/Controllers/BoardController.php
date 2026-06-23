<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * CRUD controller for boards owned by the authenticated user.
 */
class BoardController extends BaseController
{
    private const PALETTE = ['pine', 'yellow', 'blue', 'pink', 'green', 'purple', 'orange'];

    public function __construct(private readonly PDO $db) {}

    /**
     * Returns all non-archived boards belonging to the user, newest first.
     *
     * Each board includes a `note_count` of its non-archived notes.
     *
     * @return Response JSON `{ boards: BoardRow[] }`
     */
    public function list(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');

        $stmt = $this->db->prepare(
            'SELECT b.*, COUNT(n.id) AS note_count
             FROM boards b
             LEFT JOIN notes n ON n.board_id = b.id AND n.is_archived = 0
             WHERE b.owner_id = ? AND b.is_archived = 0
             GROUP BY b.id
             ORDER BY b.updated_at DESC'
        );

        $stmt->execute([$userId]);

        return $this->json($response, ['boards' => $stmt->fetchAll()]);
    }

    /**
     * Creates a board and assigns it a color from the palette deterministically
     * via `board_id % palette_size`, so the color is stable across restarts.
     *
     * `title` is required and limited to 120 characters. `description` is optional.
     *
     * @return Response JSON `{ board: BoardRow }` (201) or `{ errors }` (400)
     */
    public function create(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();
        $title = trim((string) ($body['title'] ?? ''));
        $desc = trim((string) ($body['description'] ?? ''));

        if ($title === '' || strlen($title) > 120) {
            return $this->json($response, ['errors' => ['title' => 'Title is required (max 120 characters).']], 400);
        }

        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO boards (owner_id, title, description, color, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([$userId, $title, $desc, 'pine', $now, $now]);
        $id = (int) $this->db->lastInsertId();

        $color = self::PALETTE[$id % count(self::PALETTE)];
        $this->db->prepare('UPDATE boards SET color = ? WHERE id = ?')->execute([$color, $id]);

        $board = [
            'id' => $id,
            'owner_id' => $userId,
            'title' => $title,
            'description' => $desc,
            'color' => $color,
            'is_archived' => 0,
            'created_at' => $now,
            'updated_at' => $now,
            'note_count' => 0
        ];

        $this->logActivity($userId, 'board_created', $id, $title);

        return $this->json($response, ['board' => $board], 201);
    }

    /**
     * Returns a single board together with its non-archived notes.
     *
     * Returns 404 if the board does not exist, 403 if it belongs to another user.
     *
     * @return Response JSON `{ board: BoardRow, notes: NoteRow[] }`
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $board = $this->fetchBoard((int) $args['id']);

        if (!$board) {
            return $this->json($response, ['error' => 'Board not found.'], 404);
        }

        if ((int) $board['owner_id'] !== $userId) {
            return $this->json($response, ['error' => 'Forbidden.'], 403);
        }

        $stmt = $this->db->prepare('SELECT * FROM notes WHERE board_id = ? AND is_archived = 0');
        $stmt->execute([$board['id']]);

        return $this->json($response, ['board' => $board, 'notes' => $stmt->fetchAll()]);
    }

    /**
     * Updates a board's title and/or description. Omitted fields fall back to
     * their current values, so partial updates are supported.
     *
     * @return Response JSON `{ board: BoardRow }` or `{ errors }` (400) / `{ error }` (403/404)
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $board = $this->fetchBoard((int) $args['id']);

        if (!$board) {
            return $this->json($response, ['error' => 'Board not found.'], 404);
        }

        if ((int) $board['owner_id'] !== $userId) {
            return $this->json($response, ['error' => 'Forbidden.'], 403);
        }

        $body = (array) $request->getParsedBody();
        $title = trim((string) ($body['title'] ?? $board['title']));
        $desc = trim((string) ($body['description'] ?? $board['description']));

        if ($title === '' || strlen($title) > 120) {
            return $this->json($response, ['errors' => ['title' => 'Title is required (max 120 characters).']], 400);
        }

        $now = $this->now();
        $this->db->prepare('UPDATE boards SET title = ?, description = ?, updated_at = ? WHERE id = ?')
            ->execute([$title, $desc, $now, $board['id']]);

        $this->logActivity($userId, 'board_updated', (int) $board['id'], $title);

        $board = array_merge($board, ['title' => $title, 'description' => $desc, 'updated_at' => $now]);

        return $this->json($response, ['board' => $board]);
    }

    /**
     * Permanently deletes the board (hard delete). Activity is logged first so
     * the record survives even though the board row is gone.
     *
     * @return Response 204 No Content on success, 403/404 on access failure.
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $board  = $this->fetchBoard((int) $args['id']);

        if (!$board) {
            return $this->json($response, ['error' => 'Board not found.'], 404);
        }

        if ((int) $board['owner_id'] !== $userId) {
            return $this->json($response, ['error' => 'Forbidden.'], 403);
        }

        $this->logActivity($userId, 'board_deleted', (int) $board['id'], (string) $board['title']);
        $this->db->prepare('DELETE FROM boards WHERE id = ?')->execute([$board['id']]);

        return $response->withStatus(204);
    }

    /** 
     * Fetches a board row by primary key regardless of archived status.
     */
    private function fetchBoard(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM boards WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Inserts an activity record. Pass `$noteId` for note-level events;
     * omit it for board-level events.
     */
    private function logActivity(int $userId, string $type, int $boardId, string $boardTitle, ?int $noteId = null): void
    {
        $this->db->prepare(
            'INSERT INTO activity (user_id, type, board_id, board_title, note_id, created_at) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $type, $boardId, $boardTitle, $noteId, $this->now()]);
    }

    /**
     * Returns the current Unix timestamp in milliseconds.
     */
    private function now(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
