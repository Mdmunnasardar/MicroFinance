<?php

declare(strict_types=1);

namespace App\Helpers;

final class Request
{
    private array $body;
    private array $routeParams = [];

    public function __construct(
        private readonly string $method,
        private readonly string $path,
    ) {
        $this->body = $this->parseBody();
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = $_GET['route'] ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }

        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        return new self($method, '/' . ltrim($path, '/'));
    }

    public function method(): string { return $this->method; }
    public function path(): string { return rtrim($this->path, '/') ?: '/'; }
    public function body(): array { return $this->body; }
    public function input(string $key, mixed $default = null): mixed { return $this->body[$key] ?? $_POST[$key] ?? $default; }
    public function query(string $key, mixed $default = null): mixed { return $_GET[$key] ?? $default; }
    public function allQuery(): array { return $_GET; }
    public function params(): array { return $this->routeParams; }
    public function param(string $key, mixed $default = null): mixed { return $this->routeParams[$key] ?? $default; }
    public function setRouteParams(array $params): void { $this->routeParams = $params; }

    private function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains(strtolower($contentType), 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw === false || trim($raw) === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($_POST !== []) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');
        if ($raw !== false && trim($raw) !== '') {
            parse_str($raw, $parsed);
            return is_array($parsed) ? $parsed : [];
        }

        return [];
    }
}
