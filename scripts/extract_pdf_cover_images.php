<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/extract_pdf_cover_images.php <pdf> <output-dir>\n");
    exit(1);
}

$pdfPath = $argv[1];
$outputDir = rtrim($argv[2], DIRECTORY_SEPARATOR);

if (!is_file($pdfPath)) {
    fwrite(STDERR, "PDF not found: {$pdfPath}\n");
    exit(1);
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$content = file_get_contents($pdfPath);
$images = [];
preg_match_all('/<<(.*?)>>\s*stream\r?\n?(.*?)\r?\n?endstream/s', $content, $matches, PREG_SET_ORDER);

foreach ($matches as $index => $match) {
    $dict = $match[1];

    if (strpos($dict, '/Subtype/Image') === false && strpos($dict, '/Subtype /Image') === false) {
        continue;
    }

    if (strpos($dict, '/DCTDecode') === false) {
        continue;
    }

    if (!preg_match('/\/Width\s+(\d+)/', $dict, $widthMatch) || !preg_match('/\/Height\s+(\d+)/', $dict, $heightMatch)) {
        continue;
    }

    $width = (int) $widthMatch[1];
    $height = (int) $heightMatch[1];
    $jpeg = ltrim($match[2], "\r\n");

    if ($width < 300 || $height < 300 || strlen($jpeg) < 10000) {
        continue;
    }

    $images[] = [
        'index' => $index,
        'width' => $width,
        'height' => $height,
        'area' => $width * $height,
        'jpeg' => $jpeg,
    ];
}

usort($images, function ($a, $b) {
    return $b['area'] <=> $a['area'];
});

$baseName = pathinfo($pdfPath, PATHINFO_FILENAME);
$baseName = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($baseName));
$baseName = trim($baseName, '-');

foreach (array_slice($images, 0, 5) as $rank => $image) {
    $target = $outputDir . DIRECTORY_SEPARATOR . "{$baseName}-candidate-" . ($rank + 1) . ".jpg";
    file_put_contents($target, $image['jpeg']);
    echo basename($target) . " {$image['width']}x{$image['height']}\n";
}
