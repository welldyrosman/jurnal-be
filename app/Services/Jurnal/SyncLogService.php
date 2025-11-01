<?php

namespace App\Services\Jurnal;

use App\Models\SyncLog;
use Throwable;

class SyncLogService
{
    /**
     * Mencatat bahwa proses sinkronisasi telah dimulai.
     *
     * @param string $moduleName Nama modul yang disinkronkan (misal: 'invoices')
     * @return SyncLog Instance log yang baru dibuat.
     */
    public function start(string $moduleName): SyncLog
    {
        return SyncLog::create([
            'module_name' => $moduleName,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Menandai proses sinkronisasi sebagai 'success'.
     *
     * @param SyncLog $log         Instance log yang didapat dari method start().
     * @param int     $totalSynced Jumlah data yang berhasil disinkronkan.
     * @return void
     */
    public function finish(SyncLog $log, int $totalSynced): void
    {
        $log->update([
            'status' => 'success',
            'total_synced' => $totalSynced,
            'finished_at' => now(),
        ]);
    }

    /**
     * Menandai proses sinkronisasi sebagai 'failed'.
     *
     * @param SyncLog   $log       Instance log yang didapat dari method start().
     * @param Throwable $exception Exception yang menyebabkan kegagalan.
     * @return void
     */
    public function fail(SyncLog $log, Throwable $exception): void
    {
        $log->update([
            'status' => 'failed',
            'notes' => $this->formatExceptionMessage($exception),
            'finished_at' => now(),
        ]);
    }

    /**
     * Helper untuk memformat pesan error dari exception.
     */
    private function formatExceptionMessage(Throwable $e): string
    {
        return sprintf(
            "Error: %s\nFile: %s\nLine: %d",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
    }
}
