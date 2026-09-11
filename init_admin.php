<?php
require_once 'functions.php';

// ==== ЗАДАТЬ ЛОГИН И ПАРОЛЬ АДМИНА ====
$username = 'admin';
$password = 'admin123';   // смените после первого входа!
// ======================================

// Проверим, есть ли уже такой пользователь
$stmt = getDb()->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Пользователь '$username' уже существует.\n";
    exit;
}

// Хешируем пароль
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = getDb()->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
$stmt->execute([$username, $hash]);

echo "Админ создан:\n";
echo "  Логин:  $username\n";
echo "  Пароль: $password\n";
echo "Обязательно смените пароль после первого входа!\n";