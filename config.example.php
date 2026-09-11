<?php
/**
 * Пример файла конфигурации.
 *
 * Скопируйте этот файл в config.php и укажите свои данные:
 *   cp config.example.php config.php
 *
 * Файл config.php НЕ коммитится в Git — он в .gitignore.
 */
return [
    'host'    => '127.0.0.1',   // адрес MySQL-сервера
    'port'    => 3306,          // порт (XAMPP обычно 3306, иногда 3307)
    'dbname'  => 'shop',        // имя базы данных
    'user'    => 'root',        // пользователь MySQL
    'pass'    => '',            // пароль (у XAMPP по умолчанию пустой)
    'charset' => 'utf8mb4',     // кодировка
];