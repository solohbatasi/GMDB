<?php

function gdmb_store_api_base_url(): string
{
    $url = getenv('GDMB_STORE_API_URL');

    if (is_string($url) && trim($url) !== '') {
        return rtrim(trim($url), '/');
    }

    if (! empty($_SERVER['HTTP_HOST'])) {
        $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) === '443');
        $scheme = $https ? 'https' : 'http';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = $scriptDir === '/' || $scriptDir === '.' ? '' : rtrim($scriptDir, '/');

        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath . '/backend/api/store';
    }

    return 'http://localhost:8000/api/store';
}
