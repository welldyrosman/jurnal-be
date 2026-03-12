<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QontakDealStageHistoryRepository
{
    private function normalizeDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)
            ->setTimezone('Asia/Jakarta')
            ->format('Y-m-d H:i:s');
    }

    public function upsertMany(int|string $crmDealId, array $items): void
    {
        if (empty($items)) {
            return;
        }

        $now = now();
        $crmDealId = (int) $crmDealId;

        $qontakDealId = DB::table('qontak_deals')
            ->where('deal_id', $crmDealId)
            ->value('id');

        $rows = collect($items)
            ->map(function ($item) use ($crmDealId, $qontakDealId, $now) {
                $prev = $item['prev_stage'] ?? [];
                $current = $item['current_stage'] ?? [];

                $movedDate = $this->normalizeDate($item['moved_date'] ?? null);
                $fingerprint = sha1(json_encode([
                    'crm_deal_id' => $crmDealId,
                    'prev_stage_id' => isset($prev['id']) ? (string) $prev['id'] : null,
                    'prev_stage_name' => $prev['name'] ?? null,
                    'current_stage_id' => isset($current['id']) ? (string) $current['id'] : null,
                    'current_stage_name' => $current['name'] ?? null,
                    'moved_date' => $movedDate,
                    'moved_by' => $item['moved_by'] ?? null,
                    'current_owner' => $item['current_owner'] ?? null,
                ]));

                return [
                    'qontak_deal_id' => $qontakDealId,
                    'crm_deal_id' => $crmDealId,
                    'current_owner' => $item['current_owner'] ?? null,
                    'prev_stage_id' => isset($prev['id']) ? (string) $prev['id'] : null,
                    'prev_stage_name' => $prev['name'] ?? null,
                    'current_stage_id' => isset($current['id']) ? (string) $current['id'] : null,
                    'current_stage_name' => $current['name'] ?? null,
                    'moved_date' => $movedDate,
                    'moved_by' => $item['moved_by'] ?? null,
                    'fingerprint' => $fingerprint,
                    'raw' => json_encode($item),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        DB::table('qontak_deal_stage_histories')->upsert(
            $rows,
            ['fingerprint'],
            [
                'qontak_deal_id',
                'current_owner',
                'prev_stage_id',
                'prev_stage_name',
                'current_stage_id',
                'current_stage_name',
                'moved_date',
                'moved_by',
                'raw',
                'updated_at',
            ]
        );
    }
}
