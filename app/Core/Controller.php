<?php

namespace App\Core;

abstract class Controller
{
    /** Renderiza una vista dentro del layout principal (sidebar + topbar) */
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (!is_file($viewPath)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        ob_start();
        require $viewPath;
        $contenido = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /** Renderiza una vista SIN layout (para impresion/PDF/recetas/facturas) */
    protected function viewRaw(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (!is_file($viewPath)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        require $viewPath;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . Url::to($path));
        exit;
    }

    protected function flashExito(string $mensaje): void
    {
        $_SESSION['flash'][] = ['tipo' => 'exito', 'mensaje' => $mensaje];
    }

    protected function flashError(string $mensaje): void
    {
        $_SESSION['flash'][] = ['tipo' => 'error', 'mensaje' => $mensaje];
    }

    /** Devuelve el ID entero de la URL o aborta con 404 si no es valido */
    protected function idOrFail(mixed $id): int
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            exit;
        }
        return (int) $id;
    }
}
