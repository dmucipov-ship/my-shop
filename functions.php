<?php
// functions.php — работа с MySQL через PDO

function getDb(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $config = require __DIR__ . '/config.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );
        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Ошибка подключения к БД: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function loadProducts(): array
{
    $stmt = getDb()->query("SELECT * FROM products ORDER BY id");
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['id']        = (int)$row['id'];
        $row['price']     = (int)$row['price'];
        $row['old_price'] = $row['old_price'] !== null ? (int)$row['old_price'] : null;
        $row['rating']    = (float)$row['rating'];
        $row['in_stock']  = (bool)$row['in_stock'];
    }
    return $rows;
}

/**
 * Возвращает товары с учётом фильтрации, поиска, сортировки и пагинации.
 *
 * @return array{items: array, total: int}
 */
function loadProductsPaginated(
    string $category = '',
    string $search = '',
    string $sort = 'default',
    int $page = 1,
    int $perPage = 6
): array {
    $pdo = getDb();

    // 1. Собираем WHERE
    $where = [];
    $params = [];

    if ($category !== '') {
        $where[] = 'category = :category';
        $params[':category'] = $category;
    }
    if ($search !== '') {
        $where[] = 'title LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // 2. Собираем ORDER BY (белый список!)
    $orderSql = match ($sort) {
        'price_asc'   => 'ORDER BY price ASC',
        'price_desc'  => 'ORDER BY price DESC',
        'rating_desc' => 'ORDER BY rating DESC',
        'title_asc'   => 'ORDER BY title ASC',
        'title_desc'  => 'ORDER BY title DESC',
        default       => 'ORDER BY id ASC',
    };

    // 3. Считаем общее количество (без LIMIT)
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // 4. Выбираем страницу
    $offset = max(0, ($page - 1) * $perPage);
    $sql = "SELECT * FROM products $whereSql $orderSql LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    // Привязываем параметры WHERE
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // LIMIT и OFFSET — обязательно как INT, а не как строка!
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll();

    // Приводим типы
    foreach ($rows as &$row) {
        $row['id']        = (int)$row['id'];
        $row['price']     = (int)$row['price'];
        $row['old_price'] = $row['old_price'] !== null ? (int)$row['old_price'] : null;
        $row['rating']    = (float)$row['rating'];
        $row['in_stock']  = (bool)$row['in_stock'];
    }

    return ['items' => $rows, 'total' => $total];
}

function getProductById(int $id): ?array
{
    $stmt = getDb()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['id']        = (int)$row['id'];
    $row['price']     = (int)$row['price'];
    $row['old_price'] = $row['old_price'] !== null ? (int)$row['old_price'] : null;
    $row['rating']    = (float)$row['rating'];
    $row['in_stock']  = (bool)$row['in_stock'];
    return $row;
}

function addProduct(array $data): int
{
    $stmt = getDb()->prepare("
        INSERT INTO products (title, category, price, old_price, `desc`, image, rating, in_stock)
        VALUES (:title, :category, :price, :old_price, :desc, :image, :rating, :in_stock)
    ");
    $stmt->execute([
        ':title'     => $data['title'],
        ':category'  => $data['category'],
        ':price'     => $data['price'],
        ':old_price' => $data['old_price'],
        ':desc'      => $data['desc'],
        ':image'     => $data['image'],
        ':rating'    => $data['rating'],
        ':in_stock'  => $data['in_stock'] ? 1 : 0,
    ]);
    return (int)getDb()->lastInsertId();
}

function updateProduct(int $id, array $data): bool
{
    $stmt = getDb()->prepare("
        UPDATE products SET
            title = :title,
            category = :category,
            price = :price,
            old_price = :old_price,
            `desc` = :desc,
            image = :image,
            rating = :rating,
            in_stock = :in_stock
        WHERE id = :id
    ");
    return $stmt->execute([
        ':id'        => $id,
        ':title'     => $data['title'],
        ':category'  => $data['category'],
        ':price'     => $data['price'],
        ':old_price' => $data['old_price'],
        ':desc'      => $data['desc'],
        ':image'     => $data['image'],
        ':rating'    => $data['rating'],
        ':in_stock'  => $data['in_stock'] ? 1 : 0,
    ]);
}

function deleteProduct(int $id): bool
{
    $stmt = getDb()->prepare("DELETE FROM products WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Возвращает товары той же категории, кроме указанного id.
 */
function getRelatedProducts(string $category, int $excludeId, int $limit = 4): array
{
    $stmt = getDb()->prepare("
        SELECT * FROM products
        WHERE category = ? AND id != ?
        ORDER BY rating DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute([$category, $excludeId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row['id']        = (int)$row['id'];
        $row['price']     = (int)$row['price'];
        $row['old_price'] = $row['old_price'] !== null ? (int)$row['old_price'] : null;
        $row['rating']    = (float)$row['rating'];
        $row['in_stock']  = (bool)$row['in_stock'];
    }
    return $rows;
}

/**
 * Возвращает товары с учётом фильтрации, поиска, сортировки и пагинации.
 *
 * @return array{items: array, total: int}
 */

/**
 * Строит URL для страницы пагинации с сохранением текущих фильтров.
 */
function buildPageUrl(int $page): string
{
    $params = $_GET;
    $params['p'] = $page;
    return 'index.php?' . http_build_query($params);
}