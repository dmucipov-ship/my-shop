<?php
session_start();
require_once 'functions.php';
require_once 'auth.php';
require_once 'upload.php';

requireLogin();

$errors = [];
$success = false;

// Получаем id из GET или POST
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// Загружаем товар
$product = getProductById($id);

if (!$product) {
    die('Товар не найден.');
}

// --- ОБРАБОТКА POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     csrfCheck();
    $title     = trim($_POST['title'] ?? '');
    $category  = trim($_POST['category'] ?? '');
    $price     = (int)($_POST['price'] ?? 0);
    $old_price = trim($_POST['old_price'] ?? '');
    $desc      = trim($_POST['desc'] ?? '');
    // Изображение: если загрузили новое — сохраняем, иначе оставляем старое
    $image = $product['image'];
    if (!empty($_FILES['image']['name'])) {
    $uploadError = null;
    $newImage = handleImageUpload($_FILES['image'], $uploadError);
    if ($uploadError) {
        $errors[] = $uploadError;
    } elseif ($newImage) {
        // Удаляем старое изображение (если было локальное)
        deleteUploadedImage($product['image']);
        $image = $newImage;
    }
}
    $rating    = (float)($_POST['rating'] ?? 0);
    $in_stock  = isset($_POST['in_stock']);

    // Валидация
    if ($title === '')    $errors[] = 'Введите название.';
    if ($category === '') $errors[] = 'Введите категорию.';
    if ($price <= 0)      $errors[] = 'Цена должна быть положительной.';
    if ($rating < 0 || $rating > 5) $errors[] = 'Рейтинг от 0 до 5.';

    if (empty($errors)) {
        try {
            updateProduct($id, [
                'title'     => $title,
                'category'  => $category,
                'price'     => $price,
                'old_price' => $old_price !== '' ? (int)$old_price : null,
                'desc'      => $desc,
                'image'     => $image,
                'rating'    => $rating,
                'in_stock'  => $in_stock,
            ]);
            // Перезагружаем данные, чтобы форма показала сохранённые значения
            $product = getProductById($id);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Ошибка БД: ' . $e->getMessage();
        }
    } else {
        // Если валидация не прошла — показываем то, что ввёл пользователь
        $product = array_merge($product, [
            'title'     => $title,
            'category'  => $category,
            'price'     => $price,
            'old_price' => $old_price !== '' ? (int)$old_price : null,
            'desc'      => $desc,
            'image'     => $image,
            'rating'    => $rating,
            'in_stock'  => $in_stock,
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать товар</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1>Редактирование товара</h1>
            <p class="tagline">ID: <?php echo $product['id']; ?></p>
        </div>
    </header>

    <nav class="main-nav">
        <div class="container">
            <ul>
                <li><a href="index.php">Каталог</a></li>
                <li><a href="admin.php">Админка</a></li>
            </ul>
        </div>
    </nav>

    <main class="content">
        <div class="container">
            <h2><?php echo htmlspecialchars($product['title']); ?></h2>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    Изменения сохранены. <a href="admin.php">Вернуться в админку</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                 <?php echo csrfField(); ?>
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

                <div class="form-group">
                    <label for="title">Название *</label>
                    <input type="text" id="title" name="title" required
                           value="<?php echo htmlspecialchars($product['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="category">Категория *</label>
                    <input type="text" id="category" name="category" required
                           value="<?php echo htmlspecialchars($product['category']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Цена (₽) *</label>
                        <input type="number" id="price" name="price" min="1" required
                               value="<?php echo (int)$product['price']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="old_price">Старая цена (₽)</label>
                        <input type="number" id="old_price" name="old_price" min="1"
                               value="<?php echo $product['old_price'] !== null ? (int)$product['old_price'] : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="desc">Описание</label>
                    <textarea id="desc" name="desc" rows="4"><?php echo htmlspecialchars($product['desc'] ?? ''); ?></textarea>
                </div>

                <div class="col-12 col-md-6 mb-3">
                <label class="form-label">Изображение</label>
                <?php if (!empty($product['image']) && is_file(UPLOAD_DIR . basename($product['image']))): ?>
                <div class="mb-2">
                <img src="<?php echo productImageUrl($product['image']); ?>"
                 alt="Текущее изображение"
                 style="max-height: 100px; border-radius: 6px;">
                 </div>
                 <?php endif; ?>
                 <input type="file" name="image" class="form-control" accept="image/*">
                 <div class="form-text">Оставьте пустым, чтобы сохранить текущее изображение.</div>
                 </div>
                    <div class="form-group">
                        <label for="rating">Рейтинг (0–5)</label>
                        <input type="number" id="rating" name="rating" min="0" max="5" step="0.1"
                               value="<?php echo $product['rating']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="in_stock" value="1"
                               <?php echo $product['in_stock'] ? 'checked' : ''; ?>>
                        В наличии
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-add">Сохранить</button>
                    <a href="admin.php" class="btn">Отмена</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; 2025 Мой сайт.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>