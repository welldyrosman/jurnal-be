<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QontakPipelineStageRepository
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

    public function upsertMany(string $crmPipelineId, array $items): void
    {
        if (empty($items)) {
            return;
        }

        $now = now();
        $qontakPipelineId = DB::table('qontak_pipelines')
            ->where('crm_pipeline_id', $crmPipelineId)
            ->value('id');

        $rows = collect($items)
            ->map(function ($stage) use ($crmPipelineId, $qontakPipelineId, $now) {
                $crmStageId = $stage['id'] ?? null;
                if (!$crmStageId) {
                    return null;
                }

                return [
                    'qontak_pipeline_id' => $qontakPipelineId,
                    'crm_pipeline_id' => $crmPipelineId,
                    'crm_stage_id' => (string) $crmStageId,
                    'name' => $stage['name'] ?? null,
                    'stage_order' => isset($stage['order']) ? (int) $stage['order'] : null,
                    'active' => (bool) ($stage['active'] ?? true),
                    'crm_status_id' => isset($stage['crm_status_id']) ? (string) $stage['crm_status_id'] : null,
                    'crm_type_id' => isset($stage['crm_type_id']) ? (string) $stage['crm_type_id'] : null,
                    'crm_stage_additional_field_id' => isset($stage['crm_stage_additional_field_id']) ? (string) $stage['crm_stage_additional_field_id'] : null,
                    'win_probability' => isset($stage['win_probability']) && $stage['win_probability'] !== ''
                        ? (float) $stage['win_probability']
                        : null,
                    'created_at_qontak' => $this->normalizeDate($stage['created_at'] ?? null),
                    'updated_at_qontak' => $this->normalizeDate($stage['updated_at'] ?? null),
                    'raw' => json_encode($stage),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (empty($rows)) {
            return;
        }

        DB::table('qontak_pipeline_stages')->upsert(
            $rows,
            ['crm_pipeline_id', 'crm_stage_id'],
            [
                'qontak_pipeline_id',
                'name',
                'stage_order',
                'active',
                'crm_status_id',
                'crm_type_id',
                'crm_stage_additional_field_id',
                'win_probability',
                'created_at_qontak',
                'updated_at_qontak',
                'raw',
                'updated_at',
            ]
        );
    }
}
