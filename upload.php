<?php
/**
 * Загрузка изображений товаров.
 */

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');           // относительный путь для HTML
define('MAX_FILE_SIZE', 5 * 1024 * 1024);   // 5 МБ
define('ALLOWED_MIME', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
]);

/**
 * Обрабатывает загруженный файл из $_FILES.
 *
 * @param array  $file    Элемент из $_FILES, например $_FILES['image']
 * @param string &$error  Сюда запишется текст ошибки, если что-то не так
 * @return string|null    Имя сохранённого файла (например, "abc123.jpg") или null при ошибке
 */
function handleImageUpload(array $file, ?string &$error = null): ?string
{
    // 1. Проверим код ошибки
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;   // файл не загружали — это нормально
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Ошибка загрузки файла (код ' . $file['error'] . ').';
        return null;
    }

    // 2. Проверим размер
    if ($file['size'] > MAX_FILE_SIZE) {
        $error = 'Файл слишком большой. Максимум — ' . (MAX_FILE_SIZE / 1024 / 1024) . ' МБ.';
        return null;
    }

    // 3. Проверим, что это действительно изображение.
    //    getimagesize() читает заголовок — нельзя подделать расширением.
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        $error = 'Файл не является изображением.';
        return null;
    }

    // 4. Проверим MIME-тип (по реальному содержимому файла)
    $mime = $info['mime'];
    if (!isset(ALLOWED_MIME[$mime])) {
        $error = 'Недопустимый тип изображения. Разрешены: JPG, PNG, WEBP, GIF.';
        return null;
    }

    // 5. Сгенерируем уникальное имя файла, чтобы не перезаписать чужие
    $ext      = ALLOWED_MIME[$mime];
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    // 6. Убедимся, что папка существует и доступна для записи
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        $error = 'Не удалось создать папку для загрузок.';
        return null;
    }

    // 7. Переместим файл из временной папки в uploads/
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $error = 'Не удалось сохранить файл на сервере.';
        return null;
    }

    return $filename;
}

/**
 * Удаляет ранее загруженное изображение товара.
 * Безопасно игнорирует отсутствующие файлы.
 */
function deleteUploadedImage(?string $filename): void
{
    if (!$filename) {
        return;
    }
    // Не удаляем placeholder и внешние URL
    if (str_starts_with($filename, 'http') || $filename === 'placeholder.jpg') {
        return;
    }
    $path = UPLOAD_DIR . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Возвращает URL картинки для отображения в HTML.
 * Если файл локальный и существует — отдаёт путь из uploads/.
 * Иначе — заглушку.
 */
function productImageUrl(?string $filename, string $title = ''): string
{
    if ($filename && is_file(UPLOAD_DIR . basename($filename))) {
        return UPLOAD_URL . rawurlencode($filename);
    }
    // Заглушка
    return 'https://placehold.co/400x250/EEE/31343C?text=' . urlencode($title);
}