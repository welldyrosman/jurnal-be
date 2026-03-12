<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QontakContactRepository
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
            ->filter(fn($c) => !empty($c['crm_source_id']))
            ->map(fn($c) => [
                'crm_source_id' => (string) $c['crm_source_id'],
                'crm_source_name' => $c['crm_source_name'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->unique('crm_source_id')
            ->values()
            ->all();

        if (!empty($sources)) {
            DB::table('qontak_sources')->upsert(
                $sources,
                ['crm_source_id'],
                ['crm_source_name', 'updated_at']
            );
        }

        $sourceMap = DB::table('qontak_sources')->pluck('id', 'crm_source_id');

        $data = collect($items)->map(function ($c) use ($sourceMap, $now) {
            $crmSourceId = $c['crm_source_id'] ?? null;
            $crmCompanyId = $c['crm_company_id'] ?? null;
            $fullName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));

            return [
                'crm_contact_id' => (string) $c['id'],
                'first_name' => $c['first_name'] ?? null,
                'last_name' => $c['last_name'] ?? null,
                'email' => $c['email'] ?? null,
                'telephone' => $c['telephone'] ?? ($c['phone'] ?? null),
                'slug' => Str::slug($fullName !== '' ? $fullName : (string) $c['id']),

                'created_at_qontak' => $this->normalizeDate($c['created_at'] ?? null),
                'updated_at_qontak' => $this->normalizeDate($c['updated_at'] ?? null),

                'crm_source_id' => $crmSourceId ? (string) $crmSourceId : null,
                'crm_source_name' => $c['crm_source_name'] ?? null,
                'qontak_source_id' => $crmSourceId ? ($sourceMap[(string) $crmSourceId] ?? null) : null,

                'crm_company_id' => $crmCompanyId ? (string) $crmCompanyId : null,
                'crm_company_name' => $c['crm_company_name'] ?? null,

                'creator_id' => isset($c['creator_id']) ? (string) $c['creator_id'] : null,
                'creator_name' => $c['creator_name'] ?? null,

                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        DB::table('qontak_contacts')->upsert(
            $data,
            ['crm_contact_id'],
            [
                'first_name',
                'last_name',
                'email',
                'telephone',
                'slug',
                'created_at_qontak',
                'updated_at_qontak',
                'crm_source_id',
                'crm_source_name',
                'qontak_source_id',
                'crm_company_id',
                'crm_company_name',
                'creator_id',
                'creator_name',
                'updated_at'
            ]
        );
    }
}
