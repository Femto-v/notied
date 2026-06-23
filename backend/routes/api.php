<?php

declare(strict_types=1);

/** @var \Slim\App $app */

use App\Controllers\ActivityController;
use App\Controllers\AuthController;
use App\Controllers\BoardController;
use App\Controllers\NoteController;
use App\Controllers\ProfileController;
use App\Controllers\SearchController;
use App\Middleware\AuthMiddleware;
use Slim\Interfaces\RouteCollectorProxyInterface;

$app->group('/api', function (RouteCollectorProxyInterface $group) {
    // Public routes
    $group->group('/auth', function (RouteCollectorProxyInterface $auth) {
        $auth->post('/register', [AuthController::class, 'register']);
        $auth->post('/login', [AuthController::class, 'login']);
    });

    // Protected routes
    $group->group('', function (RouteCollectorProxyInterface $auth) {
        $auth->get('/me', [ProfileController::class, 'me']);
        $auth->put('/me', [ProfileController::class, 'update']);
        $auth->put('/me/password', [ProfileController::class, 'changePassword']);

        $auth->get('/boards', [BoardController::class, 'list']);
        $auth->post('/boards', [BoardController::class, 'create']);
        $auth->get('/boards/{id}', [BoardController::class, 'get']);
        $auth->put('/boards/{id}', [BoardController::class, 'update']);
        $auth->delete('/boards/{id}', [BoardController::class, 'delete']);

        $auth->get('/boards/{id}/notes', [NoteController::class, 'listForBoard']);
        $auth->post('/boards/{id}/notes', [NoteController::class, 'create']);
        $auth->put('/notes/{id}', [NoteController::class, 'update']);
        $auth->delete('/notes/{id}', [NoteController::class, 'delete']);

        $auth->get('/search', [SearchController::class, 'search']);
        $auth->get('/activity', [ActivityController::class, 'list']);
    })->add(AuthMiddleware::class);
});
