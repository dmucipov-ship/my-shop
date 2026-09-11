<?php

require_once __DIR__ . '/../upload.php';
/**
 * Шаблон карточки товара.
 * Ожидает переменную $product — ассоциативный массив с данными товара.
 * Опционально: $selectedCategory, $sort, $search — для сохранения фильтров при добавлении в корзину.
 */

// Значения по умолчанию, чтобы шаблон не падал, если переменные не передали
$selectedCategory = $selectedCategory ?? '';
$sort             = $sort             ?? 'default';
$search           = $search           ?? '';
?>

<div class="card h-100 product-card shadow-sm border-0">
    <div class="product-image position-relative">
        <a href="product.php?id=<?php echo $product['id']; ?>"></a>
        <img src="<?php echo productImageUrl($product['image'] ?? null, $product['title']); ?>"
         class="card-img-top"
         style="height: 200px; object-fit: cover;"
         alt="<?php echo htmlspecialchars($product['title']); ?>">
        </a>

        <?php if (!$product['in_stock']): ?>
            <span class="badge bg-secondary position-absolute top-0 start-0 m-2">Нет в наличии</span>
        <?php endif; ?>

        <?php if ($product['old_price'] !== null): ?>
            <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                -<?php echo round((1 - $product['price'] / $product['old_price']) * 100); ?>%
            </span>
        <?php endif; ?>
    </div>

    <div class="card-body d-flex flex-column">
        <small class="text-uppercase text-muted"><?php echo htmlspecialchars($product['category']); ?></small>
       <h5 class="card-title mt-1">
         <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark stretched-link-title">
           <?php echo htmlspecialchars($product['title']); ?>
         </a>
       </h5>

        <!-- Рейтинг -->
        <div class="product-rating mb-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?php echo $i <= floor($product['rating']) ? 'filled' : ''; ?>">★</span>
            <?php endfor; ?>
            <span class="text-muted small ms-1"><?php echo $product['rating']; ?></span>
        </div>

        <p class="card-text text-muted small flex-grow-1">
            <?php echo htmlspecialchars($product['desc']); ?>
        </p>

        <div class="mt-auto">
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <span class="fs-4 fw-bold text-warning">
                    <?php echo number_format($product['price'], 0, '.', ' '); ?> ₽
                </span>
                <?php if ($product['old_price'] !== null): ?>
                    <span class="old-price">
                        <?php echo number_format($product['old_price'], 0, '.', ' '); ?> ₽
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($product['in_stock']): ?>
                <form method="post">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory); ?>">
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-success w-100">🛒 В корзину</button>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary w-100" disabled>Нет в наличии</button>
            <?php endif; ?>
        </div>
    </div>
</div>