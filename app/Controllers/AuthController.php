<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Services\TwoFactorService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $error = Session::flash('error');
        $this->view('auth/login', compact('error'), 'auth');
    }

    public function login(): void
    {
        $this->requireCsrf();

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, (string)$user['password'])) {
            Session::flash('error', 'Identifiants incorrects.');
            $this->redirect('/login');
        }

        $cfg = require dirname(__DIR__) . '/config/auth.php';
        $twofaEnabled = (bool)($cfg['two_factor']['enabled'] ?? false);

        if ($twofaEnabled) {
            Session::set('_2fa_pending', (int)$user['id']);
            $svc = new TwoFactorService();
            $svc->generateCode((int)($cfg['two_factor']['code_ttl_seconds'] ?? 300));
            // Ici : tu peux envoyer le code par email/SMS. Pour le moment, on le laisse en session.
            $this->redirect('/twofa');
        }

        Auth::login((int)$user['id']);
        $this->redirect('/dashboard');
    }

    public function twofaForm(): void
    {
        if (!Session::get('_2fa_pending')) $this->redirect('/login');
        $error = Session::flash('error');
        $this->view('auth/twofa', compact('error'), 'auth');
    }

    public function twofaVerify(): void
    {
        $this->requireCsrf();

        $pending = (int)Session::get('_2fa_pending', 0);
        if ($pending <= 0) $this->redirect('/login');

        $code = trim((string)($_POST['code'] ?? ''));
        $svc = new TwoFactorService();
        if (!$svc->verify($code)) {
            Session::flash('error', 'Code invalide ou expiré.');
            $this->redirect('/twofa');
        }

        Session::forget('_2fa_pending');
        Auth::login($pending);
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
