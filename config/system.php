<?php

return [
    'redaksi_email' => env('SYSTEM_REDAKSI_EMAIL', 'redaksi@portalkebumen.com'),

    // false di server testing/staging: semua halaman diberi noindex dan robots.txt melarang crawling
    'indexing' => (bool) env('SEO_INDEXING', true),
];
