<?php
require_once __DIR__ . '/backend/admin_auth.php';

$redirectTarget = sanitize_storage_redirect($_GET['redirect'] ?? 'admin.php');

if (is_storage_admin_logged_in()) {
    header('Location: /' . $redirectTarget);
    exit;
}

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirectTarget = sanitize_storage_redirect($_POST['redirect'] ?? 'admin.php');

    if ($username === '' || $password === '') {
        $errorMessage = 'Username dan password wajib diisi.';
    } elseif (storage_admin_login($username, $password)) {
        header('Location: /' . $redirectTarget);
        exit;
    } else {
        $errorMessage = 'Kredensial tidak valid.';
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin Penyimpanan</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: grid;
            place-items: center;
            padding: 60px 16px;
        }

        .login-card {
            width: min(420px, 100%);
            background: linear-gradient(135deg, rgba(22, 22, 40, 0.95), rgba(25, 25, 48, 0.9));
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 48px rgba(10, 10, 22, 0.35);
            padding: 36px 32px;
            display: grid;
            gap: 20px;
        }

        .login-card h1 {
            margin: 0;
            text-align: center;
        }

        .login-card p {
            margin: 0;
            text-align: center;
            color: var(--muted);
        }

        .login-card form {
            display: grid;
            gap: 16px;
        }

        .login-card label {
            display: grid;
            gap: 8px;
            text-align: left;
            font-size: 0.92rem;
            color: var(--muted);
        }

        .login-card input {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(12, 12, 25, 0.7);
            color: var(--text);
            font: inherit;
        }

        .login-card input:focus {
            outline: none;
            border-color: rgba(125, 91, 255, 0.6);
            box-shadow: 0 0 0 3px rgba(125, 91, 255, 0.18);
        }

        .login-card button {
            padding: 12px 16px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 12px 24px rgba(125, 91, 255, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(125, 91, 255, 0.3);
        }

        .error {
            padding: 12px;
            border-radius: 12px;
            background: rgba(255, 95, 129, 0.18);
            border: 1px solid rgba(255, 95, 129, 0.4);
            color: #fff;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Login Admin</h1>
        <p>Masuk untuk mengelola koleksi anime.</p>

        <?php if ($errorMessage): ?>
            <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget); ?>">
            <label>Username
                <input type="text" name="username" required>
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <button type="submit">Masuk</button>
        </form>
    </div>
</body>
</html>
