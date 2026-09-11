<?php
session_start();
require_once 'functions.php';
require_once 'auth.php';
require_once 'upload.php';

requireLogin();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $title     = trim($_POST['title'] ?? '');
    $category  = trim($_POST['category'] ?? '');
    $price     = (int)($_POST['price'] ?? 0);
    $old_price = trim($_POST['old_price'] ?? '');
    $desc      = trim($_POST['desc'] ?? '');
    $rating    = (float)($_POST['rating'] ?? 0);
    $in_stock  = isset($_POST['in_stock']);

    $image = null;

    // Загружаем изображение, если передали
    if (!empty($_FILES['image']['name'])) {
    $uploadError = null;
    $image = handleImageUpload($_FILES['image'], $uploadError);
    if ($uploadError) {
        $errors[] = $uploadError;
    }
    }

    if ($title === '')    $errors[] = 'Введите название.';
    if ($category === '') $errors[] = 'Введите категорию.';
    if ($price <= 0)      $errors[] = 'Цена должна быть положительной.';
    if ($rating < 0 || $rating > 5) $errors[] = 'Рейтинг от 0 до 5.';

    if (empty($errors)) {
        try {
            addProduct([
                'title'     => $title,
                'category'  => $category,
                'price'     => $price,
                'old_price' => $old_price !== '' ? (int)$old_price : null,
                'desc'      => $desc,
                'image'     => $image ?? 'placeholder.jpg',
                'rating'    => $rating,
                'in_stock'  => $in_stock,
            ]);
            $success = true;
            $_POST = [];
        } catch (PDOException $e) {
            $errors[] = 'Ошибка БД: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
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
                <li class="nav-item"><a class="nav-link" href="admin.php">Админка</a></li>
                <li class="nav-item"><a class="nav-link active" href="add_product.php">+ Добавить</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <h2 class="mb-4">Новый товар</h2>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    ✅ Товар успешно добавлен! <a href="index.php" class="alert-link">Перейти в каталог</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong>Исправьте ошибки:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form method="post" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <div class="mb-3">
                            <label class="form-label">Название *</label>
                            <input type="text" name="title" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Категория *</label>
                            <input type="text" name="category" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label">Цена (₽) *</label>
                                <input type="number" name="price" class="form-control" min="1" required
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label">Старая цена (₽)</label>
                                <input type="number" name="old_price" class="form-control" min="1"
                                       value="<?php echo htmlspecialchars($_POST['old_price'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea name="desc" rows="4" class="form-control"><?php echo htmlspecialchars($_POST['desc'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                           <div class="col-12 col-md-6 mb-3">
                               <label class="form-label">Изображение</label>
                               <input type="file" name="image" class="form-control" accept="image/*">
                               <div class="form-text">JPG, PNG, WEBP, GIF. До 5 МБ.</div>
                            </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label">Рейтинг (0–5)</label>
                                <input type="number" name="rating" class="form-control" min="0" max="5" step="0.1"
                                       value="<?php echo htmlspecialchars($_POST['rating'] ?? '4.5'); ?>">
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="in_stock" value="1" id="in_stock" checked>
                            <label class="form-check-label" for="in_stock">В наличии</label>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">Добавить товар</button>
                            <a href="index.php" class="btn btn-outline-secondary">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container"><small>&copy; 2025 Мой магазин.</small></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>