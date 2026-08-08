<?php

namespace App\Core;

abstract class ApiController
{
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function error(string $mensaje, int $status = 400): void
    {
        $this->json(['error' => $mensaje], $status);
    }

    protected function paginacion(): array
    {
        $pagina = max(1, (int) Request::input('pagina', 1));
        $porPagina = min(100, max(1, (int) Request::input('por_pagina', 20)));
        return [$pagina, $porPagina];
    }
}
