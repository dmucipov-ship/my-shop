-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Сен 28 2026 г., 22:34
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `shop`
--

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `old_price` int(11) DEFAULT NULL,
  `desc` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `in_stock` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`id`, `title`, `category`, `price`, `old_price`, `desc`, `image`, `rating`, `in_stock`) VALUES
(1, 'Смартфон Galaxy S24', 'Смартфоны', 79990, 89990, 'Современный смартфон с отличной камерой.', 'products/Смартфон Galaxy S24.jpg', 4.8, 1),
(2, 'Ноутбук UltraBook Pro', 'Ноутбуки', 124990, NULL, 'Лёгкий и производительный ноутбук.', 'products/Ноутбук UltraBook Pro.jpg', 4.5, 1),
(3, 'Наушники Wireless ANC', 'Аудио', 15990, 19990, 'Беспроводные наушники с шумоподавлением.', 'products/Наушники Wireless ANC.jpg', 4.2, 0),
(4, 'Умные часы Fit Pro', 'Гаджеты', 24990, NULL, 'Часы с мониторингом здоровья.', 'products/Умные часы Fit Pro.jpg', 4.6, 1),
(5, 'Планшет Tab S9', 'Планшеты', 54990, 59990, 'Мощный планшет для работы.', 'products/Планшет Tab S9.jpg', 4.7, 1),
(6, 'Монитор 27\" 4K', 'Мониторы', 32990, NULL, 'Профессиональный монитор.', 'products/Монитор 27 4K.jpg', 4.4, 1),
(7, 'Клавиатура Mechanical Pro', 'Периферия', 7990, 9990, 'Механическая клавиатура с подсветкой.', 'keyboard.jpg', 4.3, 1),
(8, 'Мышь Wireless Silent', 'Периферия', 2490, NULL, 'Бесшумная беспроводная мышь.', 'mouse.jpg', 4.0, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$12$wPT1UpapFEMFdS71zQNEQOuawm.XIDPeKtALuYgDP/ighqqRl6qk.', 'admin', '2026-09-10 08:54:28');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
