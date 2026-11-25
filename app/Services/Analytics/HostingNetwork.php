<?php

namespace App\Services\Analytics;

use MaxMind\Db\Reader;
use Throwable;

/**
 * Kenali alamat IP dari jaringan pusat data / layanan cloud (Google Cloud, AWS, Azure, OVH, dst.) memakai
 * database DB-IP ASN Lite. Pembaca manusia hampir tidak pernah datang dari jaringan ini, sedangkan perayap
 * dan skrip otomatis yang menyamar sebagai browser biasa justru banyak berasal dari sana.
 *
 * Cloudflare (13335), Akamai (36183, 20940) dan Fastly (54113) sengaja TIDAK dimasukkan: iCloud Private
 * Relay dan WARP memakai jaringan tersebut untuk pengguna sungguhan.
 */
class HostingNetwork
{
    /** ASN penyedia cloud, hosting, dan perayap besar. */
    private const ASNS = [
        // Google / Google Cloud
        15169, 396982, 139070, 139190, 19527, 36040, 43515,
        // Amazon / AWS
        16509, 14618, 8987, 38895,
        // Microsoft / Azure / Bing
        8075, 8068, 8069, 12076,
        // Oracle Cloud
        31898, 7160,
        // Alibaba, Tencent, Huawei Cloud, ByteDance, Baidu, Yandex
        45102, 37963, 24429, 132203, 45090, 132591, 136907, 55990, 396986, 138699, 55967, 38365, 13238, 208722,
        // Meta (perayap pratinjau tautan)
        32934,
        // DigitalOcean, Linode, Vultr, OVH, Hetzner, Contabo, Scaleway, IONOS
        14061, 393406, 63949, 20473, 16276, 35540, 24940, 213230, 51167, 141995, 12876, 8560,
        // Leaseweb, M247, CDN77/DataCamp, Psychz, QuadraNet, ColoCrossing, Hostinger
        60781, 28753, 16265, 30633, 59253, 9009, 60068, 40676, 8100, 36352, 47583, 204915,
        // Censys (pemindai internet)
        398324, 398705,
    ];

    /** Kata kunci nama organisasi ASN yang jelas menandakan hosting. */
    private const ORG_KEYWORDS = ['hosting', 'datacenter', 'data center', 'colocation', 'dedicated server', 'vps'];

    private ?Reader $reader = null;

    private bool $unavailable = false;

    public function contains(?string $ip): bool
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }

        $reader = $this->reader();
        if (! $reader) {
            return false;
        }

        try {
            $record = $reader->get($ip);
        } catch (Throwable) {
            return false;
        }

        if (! is_array($record)) {
            return false;
        }

        if (in_array((int) ($record['autonomous_system_number'] ?? 0), self::ASNS, true)) {
            return true;
        }

        $org = strtolower((string) ($record['autonomous_system_organization'] ?? ''));
        foreach (self::ORG_KEYWORDS as $keyword) {
            if (str_contains($org, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function reader(): ?Reader
    {
        if ($this->reader || $this->unavailable) {
            return $this->reader;
        }

        $path = config('analytics.geoip.asn_path');

        try {
            return $this->reader = is_file($path) ? new Reader($path) : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        } finally {
            $this->unavailable = $this->reader === null;
        }
    }
}
