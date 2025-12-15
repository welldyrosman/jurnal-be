<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class QontakPipelineRepository
{
    public function upsertMany(array $items): void
    {
        if (empty($items)) {
            return;
        }

        $now = now();

        $payload = collect($items)->map(function ($item) use ($now) {
            return [
                'crm_pipeline_id' => (string) $item['id'],
                'name'            => $item['name'] ?? null,
                'alias_name'      => $item['alias_name'] ?? null,
                'slug'            => $item['slug'] ?? null,
                'active'          => (bool) ($item['active'] ?? true),
                'organization_id' => $item['organization_id'] ?? null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        })->toArray();

        DB::table('qontak_pipelines')->upsert(
            $payload,
            ['crm_pipeline_id'], // unique key
            [
                'name',
                'alias_name',
                'slug',
                'active',
                'organization_id',
                'updated_at',
            ]
        );
    }
}
