<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Deteksi sederhana bot / klien otomatis dari User-Agent. Dipakai agar tayangan artikel,
 * impresi & klik iklan tidak digelembungkan oleh crawler, scraper, atau skrip.
 */
final class BotDetector
{
    private const NEEDLES = [
        'bot', 'crawler', 'spider', 'slurp', 'preview', 'facebookexternalhit', 'curl', 'wget',
        'python-requests', 'python-urllib', 'aiohttp', 'httpclient', 'go-http-client', 'java/', 'okhttp',
        'libwww', 'scrapy', 'headlesschrome', 'phantomjs', 'puppeteer', 'playwright', 'axios/', 'node-fetch',
    ];

    public static function isBot(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());

        if ($agent === '') {
            return true;
        }

        foreach (self::NEEDLES as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }

        return false;
    }
}
