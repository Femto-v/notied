<?php

declare(strict_types=1);

namespace App\Controllers;

use Firebase\JWT\JWT;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handles user registration and login, issuing signed JWT tokens on success.
 */
class AuthController extends BaseController
{
    public function __construct(
        private readonly PDO $db,
        private readonly string $jwtSecret,
    ) {}

    /**
     * Creates a new user account and returns an auth token.
     *
     * Validates that name is non-empty, email is valid, and password is at least
     * 8 characters. Returns 400 on validation failure, 409 if the email is already
     * registered, and 201 with `{ token, user }` on success.
     *
     * @return Response JSON `{ token: string, user: UserRow }` (201) or `{ errors }` / `{ error }`
     */
    public function register(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $name = trim((string) ($body['name'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($errors) {
            return $this->json($response, ['errors' => $errors], 400);
        }

        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            return $this->json($response, ['error' => 'Email already registered.'], 409);
        }

        $now = $this->now();
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([$name, $email, $hash, $now]);

        $id = (int) $this->db->lastInsertId();
        $token = $this->buildToken($id);
        $user  = ['id' => $id, 'name' => $name, 'email' => $email, 'avatar_url' => null, 'created_at' => $now];

        return $this->json($response, ['token' => $token, 'user' => $user], 201);
    }

    /**
     * Authenticates an existing user and returns an auth token.
     *
     * Returns 401 for any combination of unknown email or wrong password to
     * prevent user enumeration.
     *
     * @return Response JSON `{ token: string, user: UserRow }` (200) or `{ error }` (401)
     */
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        $stmt = $this->db->prepare(
            'SELECT id, name, email, password, avatar_url, created_at FROM users WHERE email = ?'
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->json($response, ['error' => 'Invalid email or password.'], 401);
        }

        $token = $this->buildToken((int) $user['id']);
        unset($user['password']);

        return $this->json($response, ['token' => $token, 'user' => $user]);
    }

    /**
     * Issues an HS256 JWT with a 24-hour expiry (`exp = iat + 86400`).
     *
     * @param int $userId Stored in the `sub` claim.
     */
    private function buildToken(int $userId): string
    {
        $payload = ['sub' => $userId, 'iat' => time(), 'exp' => time() + 86400];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Returns the current Unix timestamp in milliseconds.
     */
    private function now(): int
    {
        return (int) round(microtime(true) * 1000);
    }
}
