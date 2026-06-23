<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Returns recent activity events for the authenticated user.
 */
class ActivityController extends BaseController
{
    public function __construct(private readonly PDO $db) {}

    /**
     * Returns the most recent activity events, newest first.
     *
     * Accepts an optional `limit` query parameter (default 12, capped at 100).
     *
     * @return Response JSON `{ activity: ActivityRow[] }`
     */
    public function list(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $limit = min((int) ($request->getQueryParams()['limit'] ?? 12), 100);

        $stmt = $this->db->prepare(
            'SELECT * FROM activity WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );

        $stmt->execute([$userId, $limit]);

        return $this->json($response, ['activity' => $stmt->fetchAll()]);
    }
}
