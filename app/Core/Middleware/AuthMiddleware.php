<?php

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Url;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            $_SESSION['url_intentada'] = $_SERVER['REQUEST_URI'] ?? '';
            header('Location: ' . Url::to('login'));
            exit;
        }
    }
}
