<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QontakCompanyRepository
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

    public function upsertMany(array $items): void
    {
        $now = now();

        $sources = collect($items)
            ->map(function ($item) {
                $sourceId = $item['source']['id'] ?? ($item['crm_source_id'] ?? null);
                $sourceName = $item['source']['name'] ?? ($item['crm_source_name'] ?? null);

                if (empty($sourceId)) {
                    return null;
                }

                return [
                    'crm_source_id' => (string) $sourceId,
                    'crm_source_name' => $sourceName,
                ];
            })
            ->filter()
            ->unique('crm_source_id')
            ->map(fn($s) => [
                'crm_source_id'   => $s['crm_source_id'],
                'crm_source_name' => $s['crm_source_name'],
                'created_at'      => $now,
                'updated_at'      => $now,
            ])
            ->values()
            ->toArray();

        if (!empty($sources)) {
            DB::table('qontak_sources')->upsert(
                $sources,
                ['crm_source_id'],
                ['crm_source_name', 'updated_at']
            );
        }

        $sourceMap = DB::table('qontak_sources')->pluck('id', 'crm_source_id');

        $data = collect($items)->map(function ($c) use ($sourceMap, $now) {
            $crmSourceId = $c['source']['id'] ?? ($c['crm_source_id'] ?? null);
            $crmSourceName = $c['source']['name'] ?? ($c['crm_source_name'] ?? null);

            return [
                'crm_company_id'   => (string) $c['id'],
                'name'             => $c['name'] ?? null,
                'email'            => $c['email'] ?? null,
                'telephone'        => $c['telephone'] ?? ($c['phone'] ?? null),
                'website'          => $c['website'] ?? null,
                'address'          => $c['address'] ?? null,
                'qontak_source_id' => $crmSourceId ? ($sourceMap[(string) $crmSourceId] ?? null) : null,
                'crm_source_id'    => $crmSourceId ? (string) $crmSourceId : null,
                'crm_source_name'  => $crmSourceName,
                'crm_type_id'      => $c['crm_type_id'] ?? null,
                'crm_type_name'    => $c['crm_type_name'] ?? null,
                'slug'             => Str::slug($c['name'] ?? $c['id']),
                'created_at_qontak' => $this->normalizeDate($c['created_at'] ?? null),
                'updated_at_qontak' => $this->normalizeDate($c['updated_at'] ?? null),
                'creator_id'       => isset($c['creator_id']) ? (string) $c['creator_id'] : null,
                'creator_name'     => $c['creator_name'] ?? null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        })->toArray();

        DB::table('qontak_companies')->upsert(
            $data,
            ['crm_company_id'],
            [
                'name',
                'email',
                'telephone',
                'website',
                'address',
                'qontak_source_id',
                'crm_source_id',
                'crm_source_name',
                'crm_type_id',
                'crm_type_name',
                'slug',
                'created_at_qontak',
                'updated_at_qontak',
                'creator_id',
                'creator_name',
                'updated_at'
            ]
        );
    }
}
