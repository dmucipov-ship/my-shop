<?php
require_once 'functions.php';

$pdo = getDb();
$count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

if ($count > 0) {
    echo "В таблице уже $count товаров. Инициализация пропущена.\n";
    exit;
}

$products = [
    ['Смартфон Galaxy S24',       'Смартфоны', 79990,  89990, 'Современный смартфон с отличной камерой.', 'phone.jpg',      4.8, 1],
    ['Ноутбук UltraBook Pro',     'Ноутбуки',  124990, null,  'Лёгкий и производительный ноутбук.',       'laptop.jpg',     4.5, 1],
    ['Наушники Wireless ANC',     'Аудио',     15990,  19990, 'Беспроводные наушники с шумоподавлением.', 'headphones.jpg', 4.2, 0],
    ['Умные часы Fit Pro',        'Гаджеты',   24990,  null,  'Часы с мониторингом здоровья.',            'watch.jpg',      4.6, 1],
    ['Планшет Tab S9',            'Планшеты',  54990,  59990, 'Мощный планшет для работы.',               'tablet.jpg',     4.7, 1],
    ['Монитор 27" 4K',            'Мониторы',  32990,  null,  'Профессиональный монитор.',                'monitor.jpg',    4.4, 1],
    ['Клавиатура Mechanical Pro', 'Периферия', 7990,   9990,  'Механическая клавиатура с подсветкой.',    'keyboard.jpg',   4.3, 1],
    ['Мышь Wireless Silent',      'Периферия', 2490,   null,  'Бесшумная беспроводная мышь.',             'mouse.jpg',      4.0, 0],
];

$stmt = $pdo->prepare("
    INSERT INTO products (title, category, price, old_price, `desc`, image, rating, in_stock)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($products as $p) {
    $stmt->execute($p);
}

echo "Добавлено " . count($products) . " товаров.\n";