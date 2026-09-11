<?php
session_start();
require_once 'functions.php';
require_once 'auth.php';

requireLogin();

$products = loadProducts();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🛍 Мой магазин</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Каталог</a></li>
                <li class="nav-item"><a class="nav-link active" href="admin.php">Админка</a></li>
                <li class="nav-item"><a class="nav-link" href="add_product.php">+ Добавить</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">⚙️ Управление товарами</h2>
        <a href="add_product.php" class="btn btn-success">+ Новый товар</a>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Товар удалён.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Товар обновлён.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="alert alert-info">Товаров пока нет. <a href="add_product.php">Добавить первый</a>.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Категория</th>
                            <th class="text-end">Цена</th>
                            <th class="text-center">Рейтинг</th>
                            <th class="text-center">Наличие</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?php echo $p['id']; ?></td>
                                <td><?php echo htmlspecialchars($p['title']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                <td class="text-end"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₽</td>
                                <td class="text-center"><?php echo $p['rating']; ?></td>
                                <td class="text-center">
                                    <?php if ($p['in_stock']): ?>
                                        <span class="badge bg-success">Да</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Нет</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit_product.php?id=<?php echo $p['id']; ?>"
                                       class="btn btn-sm btn-outline-primary">Изменить</a>
                                    <form method="post" action="delete_product.php" class="d-inline"
                                          onsubmit="return confirm('Удалить «<?php echo htmlspecialchars($p['title']); ?>»?');">
                                          <?php echo csrfField(); ?>
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container"><small>&copy; 2025 Мой магазин.</small></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>