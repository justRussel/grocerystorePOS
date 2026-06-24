<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Grocery POS System – Create Account</title>

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

        .register-card {
            width: 100%;
            max-width: 480px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .10);
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background-color: #198754;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .brand-icon i {
            font-size: 2rem;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container px-3 py-4">
    <div class="card register-card mx-auto">
        <div class="card-body p-4 p-md-5">

            <!-- Brand -->
            <div class="text-center mb-4">
                <div class="brand-icon">
                    <i class="bi bi-cart3"></i>
                </div>
                <h4 class="fw-bold mb-0">Smart Grocery POS System</h4>
                <p class="text-muted small mt-1">Create your account</p>
            </div>

            <!-- Error alert -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Register form -->
            <form method="POST" action="<?= htmlspecialchars(BASE_URL) ?>?module=auth&action=register" novalidate>

                <!-- Full Name -->
                <div class="mb-3">
                    <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control<?= !empty($error) && empty($input['full_name']) ? ' is-invalid' : '' ?>"
                            placeholder="Juan dela Cruz"
                            value="<?= htmlspecialchars($input['full_name'] ?? '') ?>"
                            required
                            autofocus
                            autocomplete="name"
                        >
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($input['email'] ?? '') ?>"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>

                <!-- Phone (optional) -->
                <div class="mb-3">
                    <label for="phone" class="form-label fw-semibold">Phone <span class="text-muted fw-normal small">(optional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            class="form-control"
                            placeholder="09xxxxxxxxx"
                            value="<?= htmlspecialchars($input['phone'] ?? '') ?>"
                            autocomplete="tel"
                        >
                    </div>
                </div>

                <!-- Store Name (optional) -->
                <div class="mb-3">
                    <label for="store_name" class="form-label fw-semibold">Store Name <span class="text-muted fw-normal small">(optional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shop"></i></span>
                        <input
                            type="text"
                            id="store_name"
                            name="store_name"
                            class="form-control"
                            placeholder="My Grocery Store"
                            value="<?= htmlspecialchars($input['store_name'] ?? '') ?>"
                            autocomplete="organization"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Min. 6 characters"
                            required
                            minlength="6"
                            autocomplete="new-password"
                        >
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show/hide password" tabindex="-1">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label for="password_confirm" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            class="form-control"
                            placeholder="Re-enter your password"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                    <div id="passwordMatchFeedback" class="form-text"></div>
                </div>

                <!-- Submit -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-success btn-lg fw-semibold">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                </div>

                <!-- Back to login -->
                <p class="text-center mb-0 small">
                    Already have an account?
                    <a href="<?= htmlspecialchars(BASE_URL) ?>?module=auth&action=login" class="text-success fw-semibold text-decoration-none">Sign In</a>
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

    // Toggle password visibility
    const btn  = document.getElementById('togglePassword');
    const icon = document.getElementById('togglePasswordIcon');
    const pwd  = document.getElementById('password');

    if (btn && pwd) {
        btn.addEventListener('click', function () {
            const isHidden = pwd.type === 'password';
            pwd.type = isHidden ? 'text' : 'password';
            icon.classList.toggle('bi-eye',       !isHidden);
            icon.classList.toggle('bi-eye-slash',  isHidden);
        });
    }

    // Live password-match feedback
    const pwdConfirm = document.getElementById('password_confirm');
    const feedback   = document.getElementById('passwordMatchFeedback');

    function checkMatch() {
        if (!pwdConfirm.value) {
            feedback.textContent = '';
            pwdConfirm.classList.remove('is-valid', 'is-invalid');
            return;
        }
        const match = pwd.value === pwdConfirm.value;
        feedback.textContent     = match ? 'Passwords match.' : 'Passwords do not match.';
        feedback.style.color     = match ? '#198754' : '#dc3545';
        pwdConfirm.classList.toggle('is-valid',   match);
        pwdConfirm.classList.toggle('is-invalid', !match);
    }

    if (pwd && pwdConfirm) {
        pwd.addEventListener('input', checkMatch);
        pwdConfirm.addEventListener('input', checkMatch);
    }
})();
</script>

</body>
</html>
