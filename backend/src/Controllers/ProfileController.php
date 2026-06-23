<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Returns and updates the authenticated user's profile.
 */
class ProfileController extends BaseController
{
    public function __construct(private readonly PDO $db) {}

    /**
     * Returns the authenticated user's profile (password is never included).
     *
     * @return Response JSON `{ user: UserRow }` or `{ error }` (404)
     */
    public function me(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');

        $stmt = $this->db->prepare('SELECT id, name, email, avatar_url, created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return $this->json($response, ['error' => 'User not found.'], 404);
        }

        return $this->json($response, ['user' => $user]);
    }

    /**
     * Updates the user's display name and optional avatar URL.
     *
     * `name` is required (max 100 characters). Omitting `avatar_url` from the
     * body sets it to null, which clears the avatar.
     *
     * @return Response JSON `{ user: UserRow }` or `{ errors }` (400)
     */
    public function update(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();
        $name = trim((string) ($body['name'] ?? ''));

        if ($name === '') {
            return $this->json($response, ['errors' => ['name' => 'Name is required.']], 400);
        }

        if (strlen($name) > 100) {
            return $this->json($response, ['errors' => ['name' => 'Name must be 100 characters or fewer.']], 400);
        }

        $avatarUrl = isset($body['avatar_url']) ? (string) $body['avatar_url'] : null;

        $this->db->prepare('UPDATE users SET name = ?, avatar_url = ? WHERE id = ?')
            ->execute([$name, $avatarUrl, $userId]);

        $stmt = $this->db->prepare('SELECT id, name, email, avatar_url, created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        return $this->json($response, ['user' => $stmt->fetch()]);
    }

    /**
     * Changes the user's password after verifying the current one.
     *
     * Expects `current` (existing password) and `next` (new password, min 8
     * characters) in the request body. Returns 401 when `current` is wrong.
     *
     * @return Response JSON `{ success: true }` or `{ error }` (401) / `{ errors }` (400)
     */
    public function changePassword(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $body = (array) $request->getParsedBody();
        $current = (string) ($body['current'] ?? '');
        $next = (string) ($body['next'] ?? '');

        $stmt = $this->db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            return $this->json($response, ['error' => 'Current password is incorrect.'], 401);
        }

        if (strlen($next) < 8) {
            return $this->json($response, ['errors' => ['next' => 'New password must be at least 8 characters.']], 400);
        }

        $this->db->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($next, PASSWORD_BCRYPT), $userId]);

        return $this->json($response, ['success' => true]);
    }
}
