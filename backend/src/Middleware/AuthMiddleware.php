<?php

declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Validates the JWT Bearer token on every protected route.
 *
 * On success, the decoded subject claim is forwarded as the `user_id` request
 * attribute so downstream handlers can identify the caller without re-decoding
 * the token.
 * 
 * On failure, a 401 JSON response is returned immediately and the inner handler
 * is never invoked.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $jwtSecret) {}

    /**
     * Extracts and verifies the Bearer token from the Authorization header.
     *
     * Returns 401 when the header is absent, malformed, expired, or signed with
     * a different secret. The authenticated user's ID is injected as `user_id`.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = substr($authHeader, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        } catch (\Throwable) {
            return $this->unauthorized();
        }

        return $handler->handle($request->withAttribute('user_id', (int) $decoded->sub));
    }

    private function unauthorized(): ResponseInterface
    {
        $response = new Response(401);
        $response->getBody()->write(json_encode(['error' => 'Unauthorized']));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
