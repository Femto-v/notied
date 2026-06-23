<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Full-text search across the authenticated user's non-archived notes.
 */
class SearchController extends BaseController
{
    public function __construct(private readonly PDO $db) {}

    /**
     * Searches note content using a case-insensitive LIKE query.
     *
     * Accepts a `q` query parameter. Returns an empty array for blank queries.
     * Results are capped at 50 and include a short context snippet centred on
     * the first match. Archived notes and notes on other users' boards are excluded.
     *
     * @return Response JSON `{ results: SearchResult[] }`
     */
    public function search(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $q = trim((string) ($request->getQueryParams()['q'] ?? ''));

        if ($q === '') {
            return $this->json($response, ['results' => []]);
        }

        $stmt = $this->db->prepare(
            'SELECT n.id, n.content, n.board_id, b.title AS board_title
             FROM notes n
             JOIN boards b ON b.id = n.board_id
             WHERE b.owner_id = ? AND n.is_archived = 0 AND n.content LIKE ?
             LIMIT 50'
        );

        $stmt->execute([$userId, '%' . $q . '%']);
        $rows = $stmt->fetchAll();

        $results = array_map(fn($row) => [
            'id' => $row['id'],
            'content' => $row['content'],
            'board_id' => $row['board_id'],
            'board_title' => $row['board_title'],
            'snippet' => $this->buildSnippet((string) $row['content'], $q),
        ], $rows);

        return $this->json($response, ['results' => $results]);
    }

    /**
     * Extracts a 120-character window around the first occurrence of `$term`.
     *
     * The window starts 30 characters before the match so the match is not at
     * the very beginning of the snippet. Leading and trailing ellipses (`…`) are
     * appended when the snippet does not start or end at a content boundary.
     */
    private function buildSnippet(string $content, string $term): string
    {
        $pos = mb_stripos($content, $term);

        if ($pos === false) {
            return mb_substr($content, 0, 120);
        }

        $start = max(0, $pos - 30);
        $snippet = mb_substr($content, $start, 120);

        if ($start > 0) {
            $snippet = '…' . $snippet;
        }

        if (mb_strlen($content) > $start + 120) {
            $snippet .= '…';
        }

        return $snippet;
    }
}
