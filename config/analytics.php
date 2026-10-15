<?php

return [
    /*
    | Lokasi pembaca (Laporan Analitik). Database lokasi IP: DB-IP City Lite (gratis, lisensi CC BY 4.0,
    | wajib mencantumkan atribusi "IP Geolocation by DB-IP"). Diperbarui bulanan lewat `php artisan geoip:update`.
    | Alamat IP pembaca tidak pernah disimpan; yang tersimpan hanya rekap tayangan per wilayah per hari.
    */
    'geoip' => [
        'enabled' => env('GEOIP_ENABLED', true),
        'path' => storage_path('app/geoip/dbip-city-lite.mmdb'),
        // {month} diganti format YYYY-MM
        'download_url' => 'https://download.db-ip.com/free/dbip-city-lite-{month}.mmdb.gz',
        'attribution' => 'IP Geolocation by DB-IP',
        'attribution_url' => 'https://db-ip.com',
    ],

    // Wilayah yang dihitung sebagai "pembaca lokal" (nama sesuai database lokasi, bahasa Inggris)
    'local' => [
        'city' => env('ANALYTICS_LOCAL_CITY', 'Kebumen'),
        'region' => env('ANALYTICS_LOCAL_REGION', 'Central Java'),
        'country' => 'ID',
    ],
];
