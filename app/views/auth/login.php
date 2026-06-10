<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?> – Sign In</title>

    <!-- Bootstrap 5 CSS -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>
        body {
            background-color: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .10);
        }

        .login-brand-icon {
            width: 64px;
            height: 64px;
            background-color: #198754;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .login-brand-icon i {
            font-size: 2rem;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container px-3">
    <div class="card login-card mx-auto">
        <div class="card-body p-4 p-md-5">

            <!-- Brand -->
            <div class="text-center mb-4">
                <div class="login-brand-icon">
                    <i class="bi bi-cart3"></i>
                </div>
                <h4 class="fw-bold mb-0"><?= htmlspecialchars(APP_NAME) ?></h4>
                <p class="text-muted small mt-1">Sign in to your account</p>
            </div>

            <!-- Error alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Login form -->
            <form method="POST" action="<?= htmlspecialchars(BASE_URL) ?>?module=auth&action=login" novalidate>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control<?= !empty($error) ? ' is-invalid' : '' ?>"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control<?= !empty($error) ? ' is-invalid' : '' ?>"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button
                            class="btn btn-outline-secondary"
                            type="button"
                            id="togglePassword"
                            aria-label="Show/hide password"
                            tabindex="-1"
                        >
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-success btn-lg fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </div>

                <!-- Create account link -->
                <p class="text-center mb-0 small">
                    Don't have an account?
                    <a href="<?= htmlspecialchars(BASE_URL) ?>?module=auth&action=register" class="text-success fw-semibold text-decoration-none">Create Account</a>
                </p>

            </form>

        </div><!-- /.card-body -->
    </div><!-- /.card -->

    <p class="text-center text-muted small mt-3">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>
    </p>
</div>

<!-- Bootstrap 5 JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmS5VKoZb7FE1LFjMuSVlLDLRmX"
    crossorigin="anonymous"
></script>

<script>
(function () {
    'use strict';
    const btn  = document.getElementById('togglePassword');
    const icon = document.getElementById('togglePasswordIcon');
    const pwd  = document.getElementById('password');

    if (btn && pwd) {
        btn.addEventListener('click', function () {
            const isHidden = pwd.type === 'password';
            pwd.type = isHidden ? 'text' : 'password';
            icon.classList.toggle('bi-eye',      !isHidden);
            icon.classList.toggle('bi-eye-slash',  isHidden);
        });
    }
})();
</script>

</body>
</html>
