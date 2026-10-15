<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsExport;
use App\Services\Analytics\AnalyticsReport;
use App\Support\SimpleXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unduh Laporan Analitik sebagai Excel (.xlsx, satu tabel per sheet) atau CSV (semua tabel berurutan).
 * Periode & tab mengikuti parameter yang sama dengan halaman laporan.
 */
class AnalyticsExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tab = $request->query('tab') === 'iklan' ? 'iklan' : 'konten';
        Gate::authorize('analytics.export');
        Gate::authorize($tab === 'iklan' ? 'ads.view-reports' : 'analytics.view-content');

        $format = $request->query('format') === 'csv' ? 'csv' : 'xlsx';
        $report = AnalyticsReport::fromPeriod($request->query('periode'), $request->query('dari'), $request->query('sampai'));
        $export = new AnalyticsExport($report);
        $tables = $export->tables($tab);
        $filename = $export->filename($tab, $format);

        activity('analitik')
            ->causedBy($request->user())
            ->withProperties(['tab' => $tab, 'format' => $format, 'dari' => $report->from->toDateString(), 'sampai' => $report->to->toDateString()])
            ->log("Laporan analitik {$tab} periode {$report->periodLabel()} diekspor ke " . strtoupper($format));

        if ($format === 'xlsx') {
            $xlsx = new SimpleXlsx;
            foreach ($tables as $table) {
                $xlsx->addSheet($table['title'], $table['rows']);
            }

            return response()->download($xlsx->save(), $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
        }

        return response()->streamDownload(function () use ($tables) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar huruf non-ASCII terbaca benar di Excel
            foreach ($tables as $i => $table) {
                if ($i > 0) {
                    fputcsv($out, [], ',', '"', '');
                }
                fputcsv($out, ['# ' . $table['title']], ',', '"', '');
                foreach ($table['rows'] as $row) {
                    fputcsv($out, array_map(fn ($v) => $v ?? '', $row), ',', '"', '');
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
