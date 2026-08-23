<?php

$root = dirname(__DIR__);
$poppler = $root . '/tools/poppler/dist/poppler-26.02.0/Library/bin/pdftoppm.exe';
$sourceDir = $root . '/images/books';
$workDir = $root . '/images/books/generated/pdf-renders';
$outputDir = $root . '/images/books/covers';

$books = [
    'miracle-of-genuine-confession' => '4a - The Miracle Of Genuine Confession - Hard Cover - 20260720.pdf',
    'calling-of-God' => '14a - Calling Of God And Its Coordinates - Book Cover.pdf',
    'body-over-brand' => '9a - BODY OVER BRAND - FINAL COVER - 20260730.pdf',
    '12cs' => '3b - The 12Cs Of Christ - Christ At The Centre - Hard Cover - [20260612].pdf',
    'marriage-made-in-heaven' => '12a - Marriage Made In Heaven - 20260526.pdf',
    'when-heaven-touches-earth' => '5a - When The Heavens Touch The Earth - Hard Cover.pdf',
    'prayer-that-works' => '8a - Prayer That Works The Triple-Locked Way Hard Cover [20260730].pdf',
    'pasis-model' => '2a - PASIS Model - Hard Cover.pdf',
    'kingdom-leadership' => '1a - KINGDOM LEADERSHIP BOOK COVER FINAL DESIGN.pdf',
];

if (!is_file($poppler)) {
    fwrite(STDERR, "Missing Poppler renderer: {$poppler}\n");
    exit(1);
}

foreach ([$workDir, $outputDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

foreach ($books as $slug => $pdf) {
    $source = $sourceDir . '/' . $pdf;
    if (!is_file($source)) {
        fwrite(STDERR, "Skipping {$slug}; missing {$pdf}\n");
        continue;
    }

    $renderPrefix = $workDir . '/' . $slug;
    $rendered = $renderPrefix . '.jpg';
    $output = $outputDir . '/' . $slug . '.jpg';

    $cmd = [
        $poppler,
        '-jpeg',
        '-jpegopt',
        'quality=94',
        '-f',
        '1',
        '-singlefile',
        '-r',
        '220',
        $source,
        $renderPrefix,
    ];

    $escaped = array_map('escapeshellarg', $cmd);
    passthru(implode(' ', $escaped), $code);
    if ($code !== 0 || !is_file($rendered)) {
        fwrite(STDERR, "Render failed for {$slug}\n");
        continue;
    }

    cropFrontCover($rendered, $output);
    echo "Rendered {$slug} -> " . str_replace($root . '/', '', $output) . PHP_EOL;
}

function cropFrontCover(string $source, string $destination): void
{
    [$width, $height] = getimagesize($source);
    $image = imagecreatefromjpeg($source);

    $cropX = (int) round($width * 0.525);
    $cropWidth = $width - $cropX;

    $cover = imagecrop($image, [
        'x' => $cropX,
        'y' => 0,
        'width' => $cropWidth,
        'height' => $height,
    ]);

    if (!$cover) {
        imagedestroy($image);
        throw new RuntimeException("Could not crop {$source}");
    }

    imageinterlace($cover, true);
    imagejpeg($cover, $destination, 92);
    imagedestroy($cover);
    imagedestroy($image);
}
