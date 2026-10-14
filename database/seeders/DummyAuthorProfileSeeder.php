<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DummyAuthorProfileSeeder extends Seeder
{
    /**
     * Isi bio dummy untuk halaman profil penulis (/penulis/{id}).
     */
    public function run(): void
    {
        // Data dummy: boleh di local/testing/staging, tidak pernah di production
        if (app()->isProduction()) {
            return;
        }

        $bios = [
            'Bagus Prakoso' => 'Jurnalis PortalKebumen.com yang meliput isu pemerintahan daerah, kebijakan publik, dan peristiwa di wilayah Kebumen. Lulusan Ilmu Komunikasi, berpengalaman lebih dari lima tahun di media lokal Jawa Tengah. Senang menelusuri data anggaran dan cerita warga di balik setiap kebijakan.',
            'Ahmad Fauzan' => 'Jurnalis PortalKebumen.com dengan fokus liputan ekonomi kerakyatan, UMKM, pariwisata, dan olahraga daerah. Lahir dan besar di Kebumen, ia gemar mengangkat potensi desa, kuliner khas, serta prestasi atlet lokal agar dikenal lebih luas.',
        ];

        User::role('Penulis')->whereIn('name', array_keys($bios))->get()
            ->each(fn (User $user) => $user->forceFill(['bio' => $bios[$user->name]])->saveQuietly());
    }
}
