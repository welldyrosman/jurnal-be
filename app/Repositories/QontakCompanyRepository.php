<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QontakCompanyRepository
{
    public function upsertMany(array $items): void
    {
        $now = now();

        $sources = collect($items)
            ->pluck('source')
            ->filter()
            ->unique('id')
            ->map(fn($s) => [
                'crm_source_id'   => $s['id'],
                'crm_source_name' => $s['name'] ?? null,
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
            $crmSourceId = $c['source']['id'] ?? null;

            return [
                'crm_company_id'   => $c['id'],
                'name'             => $c['name'] ?? null,
                'email'            => $c['email'] ?? null,
                'telephone'        => $c['phone'] ?? null,
                'website'          => $c['website'] ?? null,
                'address'          => $c['address'] ?? null,
                'qontak_source_id' => $crmSourceId ? ($sourceMap[$crmSourceId] ?? null) : null,
                'crm_source_id'    => $crmSourceId,
                'crm_source_name'  => $c['source']['name'] ?? null,
                'crm_type_id'      => $c['crm_type_id'] ?? null,
                'crm_type_name'     => $c['crm_type_name'] ?? null,
                'slug'             => Str::slug($c['name'] ?? $c['id']),
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
                'updated_at'
            ]
        );
    }
}
