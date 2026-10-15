<?php

namespace App\Livewire\Admin;

use App\Services\Analytics\AnalyticsReport;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Halaman Laporan Analitik: tab Konten (tayangan per hari/kategori/penulis/artikel)
 * dan tab Iklan (impresi, klik, CTR per hari/lokasi/iklan).
 */
class AnalyticsReportPage extends Component
{
    /** Preset (lihat AnalyticsReport::PRESETS) atau 'kustom' */
    #[Url(as: 'periode')]
    public string $preset = '30';

    #[Url]
    public string $dari = '';

    #[Url]
    public string $sampai = '';

    #[Url]
    public string $tab = 'konten';

    public function mount(): void
    {
        $this->authorize('view-analytics');

        $this->normalizePeriod();

        // Ads Manager tidak punya akses konten: langsung ke tab Iklan
        if (! $this->canContent()) {
            $this->tab = 'iklan';
        } elseif (! $this->canAds() || ! in_array($this->tab, ['konten', 'iklan'], true)) {
            $this->tab = 'konten';
        }
    }

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
        $this->dari = $this->sampai = '';
        $this->normalizePeriod();
        unset($this->report);
    }

    /** Dipanggil pemilih tanggal saat rentang kustom lengkap dipilih. */
    public function setRange(string $from, string $to): void
    {
        $this->preset = 'kustom';
        $this->dari = $from;
        $this->sampai = $to;
        $this->normalizePeriod();
        unset($this->report);
    }

    /** Samakan state dengan rentang yang benar-benar dipakai (tanggal tidak valid, melewati hari ini, terlalu panjang). */
    private function normalizePeriod(): void
    {
        if ($this->preset !== 'kustom' && ! array_key_exists($this->preset, AnalyticsReport::PRESETS)) {
            $this->preset = '30';
        }

        if ($this->preset === 'kustom') {
            $report = AnalyticsReport::fromPeriod('kustom', $this->dari, $this->sampai);
            if ($this->dari === '' || $this->sampai === '') {
                $this->preset = '30';
                $this->dari = $this->sampai = '';
            } else {
                $this->dari = $report->from->toDateString();
                $this->sampai = $report->to->toDateString();
            }
        } else {
            $this->dari = $this->sampai = '';
        }
    }

    /** Parameter periode untuk tautan ekspor. */
    public function periodQuery(): array
    {
        return array_filter(['periode' => $this->preset, 'dari' => $this->dari, 'sampai' => $this->sampai]);
    }

    public function setTab(string $tab): void
    {
        if (($tab === 'konten' && $this->canContent()) || ($tab === 'iklan' && $this->canAds())) {
            $this->tab = $tab;
        }
    }

    public function canContent(): bool
    {
        return Gate::allows('analytics.view-content');
    }

    public function canAds(): bool
    {
        return Gate::allows('ads.view-reports');
    }

    #[Computed]
    public function report(): AnalyticsReport
    {
        return AnalyticsReport::fromPeriod($this->preset, $this->dari, $this->sampai);
    }

    public function render()
    {
        return view('livewire.admin.analytics-report-page');
    }
}
