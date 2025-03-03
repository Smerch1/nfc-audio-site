<?php
// Настройки директорий
$soundDir = 'uploads/sounds/';
$coverDir = 'uploads/covers/';

// Создаем директории, если их нет
if (!file_exists($soundDir)) {
    mkdir($soundDir, 0777, true);
}
if (!file_exists($coverDir)) {
    mkdir($coverDir, 0777, true);
}

// Проверка загрузки файлов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    
    // Обработка звукового файла
    if(isset($_FILES['sound']) && $_FILES['sound']['error'] === UPLOAD_ERR_OK) {
        $soundTmpPath = $_FILES['sound']['tmp_name'];
        $soundName = basename($_FILES['sound']['name']);
        $soundExt = strtolower(pathinfo($soundName, PATHINFO_EXTENSION));
        if ($soundExt !== 'mp3') {
            die("Поддерживаются только MP3 файлы.");
        }
        // Генерируем уникальное имя файла
        $newSoundName = uniqid('sound_', true) . ".mp3";
        $soundDest = $soundDir . $newSoundName;
        move_uploaded_file($soundTmpPath, $soundDest);
    } else {
        die("Ошибка загрузки звукового файла.");
    }
    
    // Обработка обложки (изображения)
    if(isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $coverTmpPath = $_FILES['cover']['tmp_name'];
        // Определяем расширение исходного изображения
        $coverInfo = getimagesize($coverTmpPath);
        if($coverInfo === false) {
            die("Неверный формат изображения.");
        }
        // Создаем изображение из файла (поддержка png и jpeg)
        $mime = $coverInfo['mime'];
        if($mime == 'image/jpeg'){
            $srcImage = imagecreatefromjpeg($coverTmpPath);
        } elseif($mime == 'image/png'){
            $srcImage = imagecreatefrompng($coverTmpPath);
        } else {
            die("Поддерживаются только JPEG и PNG изображения.");
        }
        
        // Получаем размеры исходного изображения
        $width = imagesx($srcImage);
        $height = imagesy($srcImage);
        // Определяем размер квадрата (минимальная сторона)
        $side = min($width, $height);
        
        // Создаем квадратное изображение
        $squareImage = imagecreatetruecolor($side, $side);
        // Центрируем вырезание
        imagecopyresampled($squareImage, $srcImage, 0, 0, ($width - $side)/2, ($height - $side)/2, $side, $side, $side, $side);
        
        // Создаем изображение с альфа-каналом для круглой маски
        $circleImage = imagecreatetruecolor($side, $side);
        imagesavealpha($circleImage, true);
        $transparent = imagecolorallocatealpha($circleImage, 0, 0, 0, 127);
        imagefill($circleImage, 0, 0, $transparent);
        
        // Рисуем круг (маску)
        $center = $side / 2;
        $radius = $side / 2;
        for ($x = 0; $x < $side; $x++) {
            for ($y = 0; $y < $side; $y++) {
                $distance = sqrt(pow($x - $center, 2) + pow($y - $center, 2));
                if ($distance < $radius) {
                    $color = imagecolorat($squareImage, $x, $y);
                    imagesetpixel($circleImage, $x, $y, $color);
                }
            }
        }
        
        // Генерируем имя файла для обложки
        $newCoverName = uniqid('cover_', true) . ".png";
        $coverDest = $coverDir . $newCoverName;
        imagepng($circleImage, $coverDest);
        
        // Освобождаем память
        imagedestroy($srcImage);
        imagedestroy($squareImage);
        imagedestroy($circleImage);
    } else {
        die("Ошибка загрузки обложки.");
    }
    
    // Здесь можно сохранить информацию о новом звуке (название, пути к файлам) в базу данных или файл.
    // Для примера просто перенаправляем пользователя обратно в каталог.
    header("Location: catalog.html");
    exit;
} else {
    die("Неверный метод запроса.");
}
?>
