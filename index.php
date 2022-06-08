<?php

session_start();
//$banners_stat = json_decode(file_get_contents('banners_stat.json'), true) ?? [];
$banners = [
    ['img' => 'yellow1.png'],
    ['img' => 'yellow2.png'],
    ['img' => 'yellow3.png'],
];
$banner = $banners[array_rand($banners)];
//if (!isset($banners_stat[$banner['img']])) {
//    $banners_stat[$banner['img']] = ['impressions' => 0, 'clicks' => 0];
//}
//$banners_stat[$banner['img']]['impressions']++;
//file_put_contents('banners_stat.json', json_encode($banners_stat));

if (isset($_GET['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

class Uniq
{
    public static function image($source, $dest)
    {
        $image = file_get_contents($source);
        $im = new Imagick();
        $im->readimageblob($image);

        $width = $im->getImageWidth();
        $height = $im->getImageHeight();

        do {
            $ratio = rand(0, 1000) / 100;
            $new_width = floor($width * $ratio);
            $new_height = floor($height * $ratio);
        } while (
            ($new_width < 700 || $new_width > 1000)
            && ($new_height < 700 || $new_height > 1000)
        );

        $im->scaleImage($new_width, $new_height);
        $im->rotateImage('#00000000', rand(-30, 30) / 100);
        $crop_pixels = rand(0, 5);
        $im->cropImage(
            $new_width - abs($crop_pixels),
            $new_height - abs($crop_pixels),
            0,
            0
        );

        $color = new ImagickPixel();
        $rand_color1 = rand(0, 255);
        $rand_color2 = rand(0, 255);
        $rand_color3 = rand(0, 255);
        $color->setColor("rgb($rand_color1,$rand_color2,$rand_color3)");
        $im->borderImage($color, rand(0, 1), rand(0, 1));

        $im->brightnessContrastImage(rand(-5, 5), rand(-5, 5));

        $image = $im->getimageblob();
        file_put_contents($dest, $image);
    }

    public static function video($source, $dest)
    {
        $noise_types = ['all', 'c0', 'c1', 'c2', 'c3'];
        $noise_flags = ['a', 'p', 't', 'u'];
        $noise = $noise_types[array_rand($noise_types)];
        $noise_flag = $noise_flags[array_rand($noise_flags)];
        $noise_value = rand(0, 10);
        $bitrate = rand(750, 1250);
        $command
            = "ffmpeg -i {$source} -vf noise={$noise}s={$noise_value}:{$noise}f={$noise_flag} -b:v {$bitrate}K {$dest} >/dev/null";
        shell_exec($command);
    }
}

if ($_FILES) {
    define('CAPTCHA_COOKIE', 'imgcaptcha_');
    $copies = isset($_POST['copies']) ? $_POST['copies'] : 1;
    // заметим: поле `captcha` обязательно для заполнения
    if (empty($_POST['captcha']) || md5($_POST['captcha']) != @$_COOKIE[CAPTCHA_COOKIE]) {
        $_SESSION['error_message'] = 'Корректно разгадайте CAPTCHA';
        header("Location: /");
        die();
    }

    $uniq_files = [];
    $file = [
        'name'     => $_FILES['file']['name'],
        'type'     => $_FILES['file']['type'],
        'tmp_name' => $_FILES['file']['tmp_name'],
    ];

    if (
        strpos($file['type'], 'video') !== false
        || empty(trim($file['type']))
    ) {
        if ($copies > 3) {
            $_SESSION['error_message'] = 'Для видео можно сделать не более 3 копий';
            header("Location: /");
            die();
        }
    }

    if ($copies > 10) {
        $_SESSION['error_message'] = 'Максимальное кол-во копий - 10';
        header("Location: /");
        die();
    }

    $file_parts = explode('.', $file['name']);
    $file['extension'] = end($file_parts);
    $file['basename'] = str_replace(
        ".{$file['extension']}",
        '',
        $file['name']
    );

    $file['basename'] = urlencode($file['basename']);

    $dir = "/var/www/html/files/" . time() . rand(1000, 9999);
    `mkdir -p $dir`;
    $file['source'] = $dir . "/source.{$file['extension']}";
    move_uploaded_file($file['tmp_name'], $file['source']);

    for ($i = 1; $i <= $copies; $i++) {
        if (strpos($file['type'], 'image') !== false) {
            $copy_filename
                = $dir . "/{$file['basename']}_uniq_{$i}.{$file['extension']}";
            Uniq::image($file['source'], $copy_filename);
            $uniq_files[] = $copy_filename;
        }

        if (strpos($file['type'], 'video') !== false) {
            $copy_filename
                = $dir . "/{$file['basename']}_uniq_{$i}.{$file['extension']}";
            Uniq::video($file['source'], $copy_filename);
            $uniq_files[] = $copy_filename;
        }
    }

    $zip = new ZipArchive();
    $zip_filename = $dir . "/uniq_result.zip";
    @unlink($zip_filename);
    $zip->open($zip_filename, ZipArchive::CREATE);

    foreach ($uniq_files as $uniq_file) {
        if (@file_exists($uniq_file)) {
            $zip->addFile($uniq_file, pathinfo($uniq_file)['basename']);
        }
    }
    $zip->close();

    if (is_file($zip_filename) && is_readable($zip_filename)) {
        header("Location: /files/" . str_replace('/var/www/html/files/', '', $zip_filename));
    }
}
?>

<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link
            rel="stylesheet"
            href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
            integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous"
    >
    <link rel="shortcut icon" href="/favicon.png" type="image/x-icon">
    <title>Creatives uniq</title>
</head>

<body>
<div class="container" style="display: flex; align-items: center; height: 100vh;">
    <div class="col-12 col-lg-8 offset-lg-2">
        <div class="my-5 text-center">
            <a href="click.php?banner=<?= $banner['img']; ?>" target="_blank">
                <img src="banners/<?= $banner['img']; ?>"/>
            </a>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?php
            if (!empty($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error_message'] ?>
                </div>
            <?php
            endif; ?>
            <div class="custom-file">
                <input type="file" class="custom-file-input form-control-lg" id="customFile" name="file">
                <label class="custom-file-label" for="customFile">Choose creative</label>
            </div>
            <div class="my-3 col-12 col-lg-6 offset-lg-3">
                <input type="number" class="form-control" placeholder="Copies count" name="copies">
            </div>
            <div class="my-3 col-12 col-lg-6 offset-lg-3">
                CAPTCHA: <input name="captcha" class="form-control" style="display: inline !important; width: 100px;">
                <img class="mt-1" title="Щёлкните для нового кода" alt="Код" src="captcha/jcaptcha.php" style="position: relative; top: -2px; border: 1px solid #000000"
                     onclick="this.src='captcha/jcaptcha.php?id=' + (+new Date());"><br>
            </div>
            <div class="my-3 text-center">
                <button type="submit" class="btn btn-lg btn-primary">
                    Uniq 😎
                </button>
            </div>
            <div class="alert alert-info">
                2020-07-21: Improved application stability
            </div>

            <div class="text-center text-muted text-small" style="font-size:10px;">
                Made by
                <a href="https://vk.com/dencpa" target="_blank">Denis Zhitnyakov</a>
                &
                <a href="https://dolphin.ru.com/" tagrte="_blank">Dolphin</a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6"
        crossorigin="anonymous"></script>
<style>
    body {
        /*background-image: radial-gradient(circle farthest-corner at 50.1% 52.3%, rgba(255, 231, 98, 1) 58.2%, rgba(251, 212, 0, 1) 90.1%);*/
    }
</style>

<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function (m, e, t, r, i, k, a) {
        m[i] = m[i] || function () {
            (m[i].a = m[i].a || []).push(arguments)
        };
        m[i].l = 1 * new Date();
        k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
    })
    (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    ym(61586506, "init", {
        clickmap: true,
        trackLinks: true,
        accurateTrackBounce: true,
        webvisor: true
    });
</script>
<noscript>
    <div><img src="https://mc.yandex.ru/watch/61586506" style="position:absolute; left:-9999px;" alt=""/></div>
</noscript>
<!-- /Yandex.Metrika counter -->
</body>

</html>
<?php
unset($_SESSION['error_message']);
?>
