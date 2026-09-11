<?php
session_start();

// Миграция корзины: если структура старая — конвертируем в [id => quantity]
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $value) {
        if (is_array($value)) {
            // старая структура: ['title' => ..., 'price' => ..., 'quantity' => N]
            $_SESSION['cart'][$id] = (int)($value['quantity'] ?? 1);
        } else {
            $_SESSION['cart'][$id] = (int)$value;
        }
    }
} 

require_once 'functions.php';
require_once 'auth.php';
require_once 'upload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- ОБРАБОТКА ДЕЙСТВИЙ С КОРЗИНОЙ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrfCheck();   // ← ЗАЩИТА

    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    switch ($action) {
        case 'add':
    // Проверяем, что товар вообще есть в БД
    if (getProductById($id)) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]++;   // просто увеличиваем количество
        } else {
            $_SESSION['cart'][$id] = 1; // только количество
        }
        }
        break;
        case 'update':
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        if ($quantity > 0 && isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = $quantity;
        } else {
        unset($_SESSION['cart'][$id]);
        }
        break;
        case 'remove':
            unset($_SESSION['cart'][$id]);
            break;
        case 'clear':
            $_SESSION['cart'] = [];
            break;
    }

    $redirectParams = [];
    if (!empty($_POST['category'])) $redirectParams['category'] = $_POST['category'];
    if (!empty($_POST['sort']))     $redirectParams['sort'] = $_POST['sort'];
    if (!empty($_POST['search']))   $redirectParams['search'] = $_POST['search'];
    if (isset($_POST['page']) && $_POST['page'] === 'cart') {
        $redirectParams['page'] = 'cart';
    }
    header('Location: index.php' . ($redirectParams ? '?' . http_build_query($redirectParams) : ''));
    exit;
}

$page = $_GET['page'] ?? 'catalog';

// --- КАТАЛОГ: фильтрация, поиск, сортировка, пагинация ---
$totalPages  = 1;
$currentPage = 1;

if ($page === 'catalog') {
    // Список категорий для выпадающего списка — берём все, независимо от фильтра
    $allCategories = array_unique(array_column(loadProducts(), 'category'));
    sort($allCategories);

    // Параметры из URL
    $selectedCategory = $_GET['category'] ?? '';
    $search           = trim($_GET['search'] ?? '');
    $sort             = $_GET['sort'] ?? 'default';
    $perPage          = 6;
    $currentPage      = max(1, (int)($_GET['p'] ?? 1));

    // Один запрос — и товары страницы, и общее количество
    $result           = loadProductsPaginated($selectedCategory, $search, $sort, $currentPage, $perPage);
    $filteredProducts = $result['items'];   // только товары текущей страницы
    $totalProducts    = $result['total'];
    $totalPages       = max(1, (int)ceil($totalProducts / $perPage));

    // Если запросили страницу больше, чем есть — прижимаем к последней
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
    }
}

// Счётчик корзины для бейджа в навбаре
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);   // сумма всех quantity
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой магазин</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<!-- ================= НАВБАР ================= -->
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
                <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a class="nav-link" href="admin.php">Админка</a></li>
                <li class="nav-item"><a class="nav-link" href="add_product.php">+ Добавить</a></li>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                👤 <?php echo htmlspecialchars(currentUser()['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="admin.php">Админ-панель</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php">Выйти</a></li>
                </ul>
                </li>
                <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="login.php">Войти</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">

<?php if ($page === 'cart'): ?>
    <!-- ================= СТРАНИЦА КОРЗИНЫ ================= -->
    <h2 class="mb-4">🛒 Корзина</h2>

    <?php
    // Загружаем товары из БД и строим «развёрнутую» корзину
    $cartItems = [];
    $total = 0;
    $removed = [];   // товары, которые уже удалены из БД

    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $id => $quantity) {
            $product = getProductById((int)$id);
            if (!$product) {
                // Товар удалён из БД — убираем его из корзины
                unset($_SESSION['cart'][$id]);
                $removed[] = $id;
                continue;
            }
            $sum = $product['price'] * $quantity;
            $total += $sum;
            $cartItems[] = [
                'product'  => $product,
                'quantity' => $quantity,
                'sum'      => $sum,
            ];
        }
    }
    ?>

    <?php if (!empty($removed)): ?>
        <div class="alert alert-warning">
            Некоторые товары больше недоступны и были удалены из корзины.
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <div class="alert alert-info">
            Ваша корзина пуста. <a href="index.php" class="alert-link">Перейти в каталог</a>
        </div>
    <?php else: ?>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Товар</th>
                            <th class="text-end">Цена</th>
                            <th class="text-center">Количество</th>
                            <th class="text-end">Сумма</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <?php $p = $item['product']; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo productImageUrl($p['image'] ?? null, $p['title']); ?>"
                                             alt="<?php echo htmlspecialchars($p['title']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                                        <div>
                                            <a href="product.php?id=<?php echo $p['id']; ?>"
                                               class="text-decoration-none text-dark fw-semibold">
                                                <?php echo htmlspecialchars($p['title']); ?>
                                            </a>
                                            <div class="small text-muted"><?php echo htmlspecialchars($p['category']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end"><?php echo number_format($p['price'], 0, '.', ' '); ?> ₽</td>
                                <td class="text-center">
                                    <form method="post" class="d-inline-flex align-items-center gap-1">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="page" value="cart">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
                                               min="1" class="form-control form-control-sm" style="width: 70px;">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">OK</button>
                                    </form>
                                </td>
                                <td class="text-end fw-bold"><?php echo number_format($item['sum'], 0, '.', ' '); ?> ₽</td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="page" value="cart">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Итого:</td>
                            <td class="text-end fs-5 fw-bold text-warning">
                                <?php echo number_format($total, 0, '.', ' '); ?> ₽
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="index.php" class="btn btn-primary">Продолжить покупки</a>
            <form method="post" class="d-inline">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="clear">
                <input type="hidden" name="page" value="cart">
                <button type="submit" class="btn btn-outline-danger">Очистить корзину</button>
            </form>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- ================= КАТАЛОГ ================= -->
    <h2 class="mb-4">Наши товары</h2>

    <!-- Форма фильтрации -->
    <form method="get" class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted">Поиск</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Название товара..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted">Категория</label>
                    <select name="category" class="form-select">
                        <option value="">Все категории</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"
                                <?php echo $selectedCategory === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted">Сортировка</label>
                    <select name="sort" class="form-select">
                        <option value="default"    <?php echo $sort === 'default' ? 'selected' : ''; ?>>По умолчанию</option>
                        <option value="price_asc"  <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Цена ↑</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Цена ↓</option>
                        <option value="rating_desc"<?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>Рейтинг</option>
                        <option value="title_asc"  <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>А-Я</option>
                        <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Я-А</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Применить</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Сетка товаров -->
    <?php if (empty($filteredProducts)): ?>
        <div class="alert alert-warning">Товары не найдены.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($filteredProducts as $product): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <?php include 'templates/product_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php if ($totalPages > 1): ?>
<nav aria-label="Пагинация" class="mt-4">
    <ul class="pagination justify-content-center">

        <!-- Назад -->
        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($currentPage - 1)); ?>">←</a>
        </li>

        <!-- Номера страниц -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($i)); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Вперёд -->
        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo htmlspecialchars(buildPageUrl($currentPage + 1)); ?>">→</a>
        </li>

    </ul>
</nav>
<?php endif; ?>

<?php endif; ?>
</main>

<footer class="bg-dark text-white text-center py-3 mt-5">
    <div class="container">
        <small>&copy; 2025 Мой магазин. Все права защищены.</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>