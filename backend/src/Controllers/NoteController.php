<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * CRUD controller for sticky notes within a board.
 */
class NoteController extends BaseController
{
    private const VALID_COLORS = ['yellow', 'green', 'blue', 'pink', 'purple', 'orange'];

    public function __construct(private readonly PDO $db) {}

    /**
     * Returns all non-archived notes for a board.
     *
     * @return Response JSON `{ notes: NoteRow[] }`
     */
    public function listForBoard(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $boardId = (int) $args['id'];
        $board = $this->fetchBoard($boardId);

        if (!$board) {
            return $this->json($response, ['error' => 'Board not found.'], 404);
        }

        if ((int) $board['owner_id'] !== $userId) {
            return $this->json($response, ['error' => 'Forbidden.'], 403);
        }

        $stmt = $this->db->prepare('SELECT * FROM notes WHERE board_id = ? AND is_archived = 0');
        $stmt->execute([$boardId]);

        return $this->json($response, ['notes' => $stmt->fetchAll()]);
    }

    /**
     * Creates a note on the board. Unrecognised colors fall back to `yellow`.
     *
     * Default position: (60, 60). Default size: 220 × 180 px.
     * Also bumps the parent board's `updated_at` timestamp.
     *
     * @return Response JSON `{ note: NoteRow }` (201)
     */
    public function create(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $boardId = (int) $args['id'];
        $board = $this->fetchBoard($boardId);

        if (!$board) {
            return $this->json($response, ['error' => 'Board not found.'], 404);
        }

        if ((int) $board['owner_id'] !== $userId) {
            return $this->json($response, ['error' => 'Forbidden.'], 403);
        }

        $body = (array) $request->getParsedBody();
        $content = (string) ($body['content'] ?? '');
        $color = (string) ($body['color'] ?? 'yellow');
        $posX = (int) ($body['pos_x'] ?? 60);
        $posY = (int) ($body['pos_y'] ?? 60);
        $width = (int) ($body['width'] ?? 220);
        $height = (int) ($body['height'] ?? 180);

        if (!in_array($color, self::VALID_COLORS, true)) {
            $color = 'yellow';
        }

        $now = $this->now();
        $stmt = $this->db->prepare(
            'INSERT INTO notes (board_id, author_id, content, pos_x, pos_y, width, height, color, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([$boardId, $userId, $content, $posX, $posY, $width, $height, $color, $now]);
        $id = (int) $this->db->lastInsertId();

        $this->db->prepare('UPDATE boards SET updated_at = ? WHERE id = ?')->execute([$now, $boardId]);
        $this->logActivity($userId, 'note_created', (int) $board['id'], (string) $board['title'], $id);

        $note = [
            'id' => $id,
            'board_id' => $boardId,
            'author_id' => $userId,
            'content' => $content,
            'pos_x' => $posX,
            'pos_y' => $posY,
            'width' => $width,
            'height' => $height,
            'color' => $color,
            'is_archived' => 0,
            'updated_at' => $now
        ];

        return $this->json($response, ['note' => $note], 201);
    }

    /**
     * Updates a note's content, color, position, or dimensions. Omitted fields
     * retain their current values. Unrecognised colors are silently ignored.
     * Also bumps the parent board's `updated_at` timestamp.
     *
     * @return Response JSON `{ note: NoteRow }`
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $note = $this->fetchNote((int) $args['id']);

        if (!$note) {
            return $this->json($response, ['error' => 'Note not found.'], 404);
        }

        $board = $this->fetchBoard((int) $note['board_id']);

        if (!$board || (int) $board['owner_id'] !== $userId) {
            return $this->json($response, ['error' => 'Forbidden.'], 403);
        }

        $body = (array) $request->getParsedBody();
        $content = (string) ($body['content'] ?? $note['content']);
        $color = (string) ($body['color'] ?? $note['color']);
        $posX = isset($body['pos_x']) ? (int) $body['pos_x'] : (int) $note['pos_x'];
        $posY = isset($body['pos_y']) ? (int) $body['pos_y'] : (int) $note['pos_y'];
        $width = isset($body['width']) ? (int) $body['width'] : (int) $note['width'];
        $height = isset($body['height']) ? (int) $body['height'] : (int) $note['height'];

        if (!in_array($color, self::VALID_COLORS, true)) {
            $color = (string) $note['color'];
        }

        $now = $this->now();

        $this->db->prepare(
            'UPDATE notes SET content = ?, pos_x = ?, pos_y = ?, width = ?, height = ?, color = ?, updated_at = ?
             WHERE id = ?'
        )->execute([$content, $posX, $posY, $width, $height, $color, $now, $note['id']]);

        $this->db->prepare('UPDATE boards SET updated_at = ? WHERE id = ?')->execute([$now, $board['id']]);
        $this->logActivity($userId, 'note_updated', (int) $board['id'], (string) $board['title'], (int) $note['id']);

        $updated = array_merge($note, [
            'content' => $content,
            'pos_x' => $posX,
            'pos_y' => $posY,
            'width' => $width,
            'height' => $height,
            'color' => $color,
            'updated_at' => $now
        ]);

        return $this->json($response, ['note' => $updated]);
    }

    /**
     * Soft-deletes a note by setting `is_archived = 1` rather than removing the row.
     * Also bumps the parent board's `updated_at` timestamp.
     *
     * @return Response 204 No Content on success.
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $userId = $request->getAttribute('user_id');
        $note = $this->fetchNote((int) $args['id']);

        if (!$note) {
            return $this->json($response, ['error' => 'Note not found.'], 404);
        }

        $board = $this->fetchBoard((int) $note['board_id']);

        if (!$board || (int) $board['owner_id'] !== $userId) {
            return $this->json($response, ['error' => 'Forbidden.'], 403);
        }

        $now = $this->now();

        $this->db->prepare('UPDATE notes SET is_archived = 1, updated_at = ? WHERE id = ?')
            ->execute([$now, $note['id']]);

        $this->db->prepare('UPDATE boards SET updated_at = ? WHERE id = ?')->execute([$now, $board['id']]);
        $this->logActivity($userId, 'note_deleted', (int) $board['id'], (string) $board['title'], (int) $note['id']);

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
     * Fetches a note row by primary key regardless of archived status.
     */
    private function fetchNote(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM notes WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /**
     * Inserts an activity record. Pass `$noteId` for note-level events, otherwise omit it for board-level events.
     */
    private function logActivity(int $userId, string $type, int $boardId, string $boardTitle, ?int $noteId = null): void
    {
        $this->db->prepare(
            'INSERT INTO activity (user_id, type, board_id, board_title, note_id, created_at) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$userId, $type, $boardId, $boardTitle, $noteId, $this->now()]);
    }
}
