<?php

declare(strict_types=1);

use App\Helpers\JsonResponse;
use App\Helpers\Request;
use App\Middleware\AuthMiddleware;

require __DIR__ . '/../app/Bootstrap/autoload.php';
require __DIR__ . '/../app/Bootstrap/bootstrap.php';

$request = Request::capture();
$routes = require __DIR__ . '/../app/Routes/api.php';

$method = strtoupper($request->method());
$path = rtrim($request->path(), '/');
if ($path === '') {
    $path = '/';
}

$pathMatched = false;
foreach ($routes as [$routeMethod, $routePath, $controller, $action, $middleware]) {
    $params = matchPath($routePath, $path);
    if ($params === null) {
        continue;
    }

    $pathMatched = true;

    if (strtoupper($routeMethod) !== $method) {
        continue;
    }

    $request->setRouteParams($params);
    foreach ($middleware as $name) {
        if ($name === 'auth') {
            AuthMiddleware::handle();
        }
    }
    (new $controller())->{$action}($request);
    return;
}

if ($pathMatched) {
    JsonResponse::error('METHOD_NOT_ALLOWED', 'Method not allowed for this endpoint.', 405);
}

JsonResponse::error('NOT_FOUND', 'No API endpoint matches ' . $method . ' ' . $path, 404);

function matchPath(string $routePath, string $requestPath): ?array
{
    $routeParts = array_values(array_filter(explode('/', $routePath), static fn ($p) => $p !== ''));
    $pathParts = array_values(array_filter(explode('/', $requestPath), static fn ($p) => $p !== ''));

    if (count($routeParts) !== count($pathParts)) {
        return null;
    }

    $params = [];
    foreach ($routeParts as $index => $segment) {
        if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $segment, $matches)) {
            $params[$matches[1]] = $pathParts[$index];
        } elseif ($segment !== $pathParts[$index]) {
            return null;
        }
    }

    return $params;
}
