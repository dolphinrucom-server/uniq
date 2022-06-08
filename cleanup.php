<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$files = scandir('/var/www/html/files');
foreach ($files as $file) {
    if ($file == '.' || $file == '..') {
        continue;
    }
    $time_diff = time() - filemtime("/var/www/html/files/$file");
    echo $time_diff . PHP_EOL;
    $time_to_keep = 60 * 30;
    if (disk_free_space('/') / disk_total_space('/') <= 0.25) {
        $time_to_keep = 60 * 5;
    }
    if ($time_diff > $time_to_keep) {
        echo "/var/www/html/files/$file" . PHP_EOL;
	`rm -rf /var/www/html/files/$file`;
    }
}
