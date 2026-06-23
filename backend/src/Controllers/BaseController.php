<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Shared utilities for all API controllers.
 */
abstract class BaseController
{
    /**
     * Writes JSON-encoded data to the response with the appropriate `Content-Type` header.
     * 
     * @param Response $response The PSR-7 response object to write to.
     * @param mixed $data The data to encode as JSON.
     * @param int $status The HTTP status code to set on the response (default 200).
     * @return Response The modified response object with the JSON body and headers.
     */
    protected function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
