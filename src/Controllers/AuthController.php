<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthController
{
    public function loginForm(Request $request): void
    {
        if (Session::has('user_id')) {
            Response::redirect('/admin');
        }
        $error = Session::getFlash('login_error');
        Response::render('auth/login', ['error' => $error]);
    }

    public function login(Request $request): void
    {
        $email    = trim((string) $request->post('email', ''));
        $password = (string) $request->post('password', '');

        if ($email === '' || $password === '') {
            Session::flash('login_error', 'Email et mot de passe requis.');
            Response::redirect('/login');
        }

        $db   = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1',
            [$email]
        );

        if ($user === false || !password_verify($password, $user['password'])) {
            Session::flash('login_error', 'Identifiants incorrects.');
            Response::redirect('/login');
        }

        session_regenerate_id(true);
        Session::set('user_id',   $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_role', $user['role']);

        Response::redirect('/admin');
    }

    public function logout(Request $request): void
    {
        Session::destroy();
        Response::redirect('/login');
    }
}
