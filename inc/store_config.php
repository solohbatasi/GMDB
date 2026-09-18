<?php

function gdmb_store_api_base_url(): string
{
    $url = getenv('GDMB_STORE_API_URL');

    if (is_string($url) && trim($url) !== '') {
        return rtrim(trim($url), '/');
    }

    return 'http://localhost:8000/api/store';
}
