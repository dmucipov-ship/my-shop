<?php


/**
 * Авторизация пользователей.
 * Использует $_SESSION['user'] и таблицу users в MySQL.
 */

require_once __DIR__ . '/functions.php';

/**
 * Проверяет, залогинен ли пользователь.
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user']['id']);
}

/**
 * Возвращает текущего пользователя или null.
 */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Требует авторизации. Если не залогинен — редирект на login.php.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        // Запоминаем, куда пользователь хотел попасть, чтобы вернуть после входа
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'admin.php';
        header('Location: login.php');
        exit;
    }
}

/**
 * Пытается войти. Возвращает true при успехе, иначе false.
 * Простая защита от перебора: не более 5 попыток за 5 минут на сессию.
 */
function attemptLogin(string $username, string $password): bool
{
    // Простейшая защита от брутфорса (на уровне сессии)
    $now = time();
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = ['count' => 0, 'first_at' => $now];
    }
    $attempts = &$_SESSION['login_attempts'];

    // Если прошло больше 5 минут — сбрасываем счётчик
    if ($now - $attempts['first_at'] > 300) {
        $attempts = ['count' => 0, 'first_at' => $now];
    }
    if ($attempts['count'] >= 5) {
        return false; // слишком много попыток
    }

    // Ищем пользователя
    $stmt = getDb()->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Проверяем пароль
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $attempts['count']++;
        return false;
    }

    // Успешный вход — сбрасываем счётчик, обновляем ID сессии
    unset($_SESSION['login_attempts']);
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'       => (int)$user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
    ];

    return true;
}

/**
 * Выход из аккаунта.
 */
function logout(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

/**
 * Возвращает CSRF-токен текущей сессии, создавая его при необходимости.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Выводит скрытое поле с CSRF-токеном для формы.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Проверяет токен из POST-запроса. Прерывает выполнение с 403, если токен неверный.
 */
function csrfCheck(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Ошибка безопасности: неверный CSRF-токен.');
    }
}