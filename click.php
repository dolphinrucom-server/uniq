<?php

error_reporting(0);
$banner = $_GET['banner'] ?? null;

//if ($banner) {
//  $banners_stat = json_decode(file_get_contents('banners_stat.json'), true) ?? [];
//  if (!isset($banners_stat[$banner])) {
//    $banners_stat[$banner] = ['impressions' => 0, 'clicks' => 0];
//  }
//  $banners_stat[$banner]['clicks']++;
//  file_put_contents('banners_stat.json', json_encode($banners_stat));
//}

header("Location: http://dolphin.ru.com/?utm_source=mini-services&utm_medium=uniq&utm_campaign={$banner}");
