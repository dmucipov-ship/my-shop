<?php
session_start();
require_once 'functions.php';
require_once 'auth.php';

requireLogin();

// Разрешаем только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

csrfCheck();   // ← ЗАЩИТА

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id > 0) {
    try {
        deleteProduct($id);
    } catch (PDOException $e) {
        // В реальном проекте здесь лучше логировать ошибку
        die('Ошибка удаления: ' . $e->getMessage());
    }
}

header('Location: admin.php?deleted=1');
exit;