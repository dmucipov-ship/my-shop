<?php
session_start();
require_once 'auth.php';

// Если уже залогинен — сразу в админку
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Заполните оба поля.';
    } elseif (attemptLogin($username, $password)) {
        $redirect = $_SESSION['redirect_after_login'] ?? 'admin.php';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Неверный логин или пароль (или превышено число попыток).';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="text-center mb-4">
                <a href="index.php" class="text-decoration-none">
                    <h1 class="h3 text-dark">🛍 Мой магазин</h1>
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h4 text-center mb-4">Вход в админ-панель</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger small"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">
                        <?php echo csrfField(); ?>
                        <div class="mb-3">
                            <label for="username" class="form-label">Логин</label>
                            <input type="text" id="username" name="username" class="form-control"
                                   required autofocus
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="text-muted small text-decoration-none">← Вернуться в каталог</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>