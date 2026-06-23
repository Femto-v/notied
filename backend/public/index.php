<?php

declare(strict_types=1);

use App\Controllers\ActivityController;
use App\Controllers\AuthController;
use App\Controllers\BoardController;
use App\Controllers\NoteController;
use App\Controllers\ProfileController;
use App\Controllers\SearchController;
use App\Middleware\AuthMiddleware;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env file if it exists.
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
}

// Build the DI container.
$builder = new ContainerBuilder();

$builder->addDefinitions([
    PDO::class => function () {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? '127.0.0.1',
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'] ?? 'notied',
        );

        return new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },

    'jwt.secret' => fn() => $_ENV['JWT_SECRET'] ?? '',

    // AuthController needs the jwt.secret primitive, so we need to wire it explicitly.
    AuthController::class => fn(\DI\Container $c) => new AuthController($c->get(PDO::class), $c->get('jwt.secret')),

    // AuthMiddleware also needs jwt.secret.
    AuthMiddleware::class => fn(\DI\Container $c) => new AuthMiddleware($c->get('jwt.secret')),

    // Remaining controllers are auto-wired by the container.
    BoardController::class => \DI\autowire(),
    NoteController::class => \DI\autowire(),
    SearchController::class => \DI\autowire(),
    ActivityController::class => \DI\autowire(),
    ProfileController::class => \DI\autowire(),
]);

$container = $builder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// Middleware configuration
// Slim applies in LIFO order, so the last added runs first.
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(new App\Middleware\CorsMiddleware());
$app->addErrorMiddleware(
    displayErrorDetails: ($_ENV['APP_ENV'] ?? 'production') === 'development',
    logErrors: true,
    logErrorDetails: true,
);

// Routes
require __DIR__ . '/../routes/api.php';

$app->run();
