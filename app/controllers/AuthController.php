<?php
/**
 * GroceryPOS - AuthController
 * Handles login (GET display / POST verify) and logout.
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    /**
     * Login action.
     *
     * GET  → display the login form
     * POST → validate credentials; on success set session and redirect to dashboard;
     *         on failure re-display the form with an error message
     */
    public function login(): void
    {
        // Bootstrap session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }

        // Already logged in → send to dashboard
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '?module=dashboard');
            exit;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email']    ?? '');
            $password = $_POST['password'] ?? '';

            // Basic input validation
            if ($email === '' || $password === '') {
                $error = 'Please enter your email and password.';
            } else {
                $user = User::findByEmail($email);

                if ($user === null || !password_verify($password, $user['password_hash'])) {
                    // Generic message — do not reveal whether email exists
                    $error = 'Invalid email or password.';
                } else {
                    // ── Credentials valid — populate session ──────────────
                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['user_name']     = $user['full_name'];
                    $_SESSION['user_photo']    = $user['photo'] ?? '';
                    $_SESSION['store_name']    = $user['store_name']    ?? APP_NAME;
                    $_SESSION['store_address'] = $user['store_address'] ?? '';

                    // Regenerate session ID to prevent fixation attacks
                    session_regenerate_id(true);

                    header('Location: ' . BASE_URL . '?module=dashboard');
                    exit;
                }
            }
        }

        // Render login view (GET or failed POST)
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Register action.
     *
     * GET  → display the create-account form
     * POST → validate input, create user, auto-login, redirect to dashboard
     */
    public function register(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }

        // Already logged in → send to dashboard
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '?module=dashboard');
            exit;
        }

        $error   = null;
        $success = null;
        $input   = []; // repopulate form on error

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input['full_name']   = trim($_POST['full_name']   ?? '');
            $input['email']       = strtolower(trim($_POST['email'] ?? ''));
            $input['phone']       = trim($_POST['phone']       ?? '');
            $input['store_name']  = trim($_POST['store_name']  ?? '');
            $password             = $_POST['password']         ?? '';
            $passwordConfirm      = $_POST['password_confirm'] ?? '';

            // ── Validation ────────────────────────────────────────────────
            if ($input['full_name'] === '') {
                $error = 'Full name is required.';
            } elseif ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'A valid email address is required.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Passwords do not match.';
            } elseif (User::emailExists($input['email'])) {
                $error = 'That email address is already registered.';
            } else {
                // ── Create account ────────────────────────────────────────
                try {
                    $newId = User::create([
                        'full_name'  => $input['full_name'],
                        'email'      => $input['email'],
                        'phone'      => $input['phone'],
                        'password'   => $password,
                        'store_name' => $input['store_name'],
                    ]);

                    // Auto-login after registration
                    $user = User::findById($newId);

                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_name']  = $user['full_name'];
                    $_SESSION['user_photo'] = $user['photo'] ?? '';
                    $_SESSION['store_name'] = $user['store_name'] ?? APP_NAME;

                    session_regenerate_id(true);

                    header('Location: ' . BASE_URL . '?module=dashboard');
                    exit;
                } catch (\Exception $e) {
                    $error = 'Could not create account. Please try again.';
                }
            }
        }

        require_once __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Logout action.
     * Destroys the session and redirects to the login page.
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }

        // Clear all session data
        $_SESSION = [];

        // Destroy the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: ' . BASE_URL . '?module=auth&action=login');
        exit;
    }
}
