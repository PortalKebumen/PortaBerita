<?php

namespace App\Support;

use App\Services\Analytics\HostingNetwork;
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

    /**
     * Bot menurut User-Agent ATAU berasal dari jaringan pusat data/cloud. Dipakai untuk penghitungan
     * tayangan dan iklan, karena banyak perayap menyamar sebagai browser biasa dari server cloud.
     */
    public static function isAutomated(Request $request): bool
    {
        return self::isBot($request) || app(HostingNetwork::class)->contains($request->ip());
    }
}
