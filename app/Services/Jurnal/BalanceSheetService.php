<?php

namespace App\Services\Jurnal;

use Carbon\Carbon;
use Throwable;

class BalanceSheetService extends JurnalBaseService
{
    public function getReport(array $queryParams = []): array
    {
        $params = [];
        if (!empty($queryParams['end_date'])) {
            $params['end_date'] = Carbon::parse($queryParams['end_date'])->format('d/m/Y');
        }

        try {
            $response = $this->get('balance_sheet', $params);
            return $response['balance_sheet'] ?? [];

        } catch (Throwable $e) {
            logger()->error('Gagal mengambil Balance Sheet dari Jurnal API', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Gagal terhubung ke Jurnal API: ' . $e->getMessage());
        }
    }
}
