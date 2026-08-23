<?php

$dir = $argv[1] ?? 'images/books/generated';
$target = $argv[2] ?? 'images/books/generated/contact-sheet.jpg';

$files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.jpg');
$files = array_filter($files, function ($file) use ($target) {
    return realpath($file) !== realpath($target);
});

$thumbWidth = 180;
$thumbHeight = 220;
$labelHeight = 56;
$columns = 4;
$rows = (int) ceil(count($files) / $columns);

$sheet = imagecreatetruecolor($columns * $thumbWidth, $rows * ($thumbHeight + $labelHeight));
$white = imagecolorallocate($sheet, 255, 255, 255);
$black = imagecolorallocate($sheet, 20, 20, 20);
$gray = imagecolorallocate($sheet, 238, 238, 238);
imagefill($sheet, 0, 0, $white);

foreach (array_values($files) as $i => $file) {
    $img = @imagecreatefromjpeg($file);
    if (!$img) {
        continue;
    }

    $x = ($i % $columns) * $thumbWidth;
    $y = intdiv($i, $columns) * ($thumbHeight + $labelHeight);
    imagefilledrectangle($sheet, $x, $y, $x + $thumbWidth - 1, $y + $thumbHeight + $labelHeight - 1, $gray);

    $srcWidth = imagesx($img);
    $srcHeight = imagesy($img);
    $scale = min(($thumbWidth - 16) / $srcWidth, ($thumbHeight - 16) / $srcHeight);
    $dstWidth = (int) round($srcWidth * $scale);
    $dstHeight = (int) round($srcHeight * $scale);
    $dstX = $x + (int) (($thumbWidth - $dstWidth) / 2);
    $dstY = $y + 8;

    imagecopyresampled($sheet, $img, $dstX, $dstY, 0, 0, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

    $label = basename($file);
    $label = preg_replace('/-candidate-/', "\n#", $label);
    imagestring($sheet, 2, $x + 6, $y + $thumbHeight + 8, substr($label, 0, 28), $black);
    imagestring($sheet, 2, $x + 6, $y + $thumbHeight + 24, substr($label, 28, 28), $black);
    imagestring($sheet, 2, $x + 6, $y + $thumbHeight + 40, substr($label, 56, 28), $black);
    imagedestroy($img);
}

imagejpeg($sheet, $target, 88);
imagedestroy($sheet);

echo $target . "\n";
