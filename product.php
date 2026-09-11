<?php
session_start();
require_once 'functions.php';
require_once 'upload.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProductById($id);

// Счётчик корзины
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Если товар не найден — 404
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Товар не найден';
} else {
    $pageTitle = htmlspecialchars($product['title']);
    $related = getRelatedProducts($product['category'], $product['id'], 4);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> — Мой магазин</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🛍 Мой магазин</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Каталог</a></li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=cart">
                        Корзина
                        <?php if ($cartCount > 0): ?>
                            <span class="badge bg-warning text-dark"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link" href="admin.php">Админка</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">

<?php if (!$product): ?>
    <!-- 404 -->
    <div class="text-center py-5">
        <h1 class="display-1 fw-bold text-muted">404</h1>
        <p class="lead">Товар не найден.</p>
        <a href="index.php" class="btn btn-primary">← Вернуться в каталог</a>
    </div>
<?php else: ?>

    <!-- Хлебные крошки -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Каталог</a></li>
            <li class="breadcrumb-item">
                <a href="index.php?category=<?php echo urlencode($product['category']); ?>">
                    <?php echo htmlspecialchars($product['category']); ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($product['title']); ?>
            </li>
        </ol>
    </nav>

    <!-- Товар -->
    <div class="row g-5 mt-2">
        <div class="col-12 col-md-6">
            <div class="product-detail-image">
                <img src="<?php echo productImageUrl($product['image'] ?? null, $product['title']); ?>"
                alt="<?php echo htmlspecialchars($product['title']); ?>"
                class="img-fluid rounded shadow-sm w-100">
                <?php if (!$product['in_stock']): ?>
                    <span class="badge bg-secondary position-absolute top-0 start-0 m-3">Нет в наличии</span>
                <?php endif; ?>
                <?php if ($product['old_price'] !== null): ?>
                    <span class="badge bg-danger position-absolute top-0 end-0 m-3">
                        -<?php echo round((1 - $product['price'] / $product['old_price']) * 100); ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <small class="text-uppercase text-muted"><?php echo htmlspecialchars($product['category']); ?></small>
            <h1 class="mt-1 mb-3"><?php echo htmlspecialchars($product['title']); ?></h1>

            <div class="product-rating mb-3">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star <?php echo $i <= floor($product['rating']) ? 'filled' : ''; ?>">★</span>
                <?php endfor; ?>
                <span class="text-muted ms-2"><?php echo $product['rating']; ?> из 5</span>
            </div>

            <div class="d-flex align-items-baseline gap-3 mb-4">
                <span class="display-5 fw-bold text-warning">
                    <?php echo number_format($product['price'], 0, '.', ' '); ?> ₽
                </span>
                <?php if ($product['old_price'] !== null): ?>
                    <span class="old-price fs-4">
                        <?php echo number_format($product['old_price'], 0, '.', ' '); ?> ₽
                    </span>
                <?php endif; ?>
            </div>

            <p class="text-muted mb-4"><?php echo htmlspecialchars($product['desc']); ?></p>

            <?php if ($product['in_stock']): ?>
                <form method="post" action="index.php" class="mb-4">
                     <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-success btn-lg flex-grow-1">
                            🛒 Добавить в корзину
                        </button>
                        <a href="index.php?page=cart" class="btn btn-outline-primary btn-lg">
                            Перейти в корзину
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">Товар временно отсутствует.</div>
            <?php endif; ?>

            <div class="card bg-light border-0">
                <div class="card-body">
                    <h6 class="mb-3">Характеристики</h6>
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">Артикул</td>
                            <td class="fw-semibold">#<?php echo str_pad($product['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Категория</td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($product['category']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Наличие</td>
                            <td class="fw-semibold"><?php echo $product['in_stock'] ? '✅ На складе' : '❌ Нет'; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Рейтинг</td>
                            <td class="fw-semibold"><?php echo $product['rating']; ?> / 5</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($related)): ?>
        <section class="mt-5 pt-4 border-top">
            <h2 class="mb-4">Похожие товары</h2>
            <div class="row g-4">
                <?php foreach ($related as $product): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <?php include 'templates/product_card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

<?php endif; ?>
</main>

<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container"><small>&copy; 2025 Мой магазин.</small></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>