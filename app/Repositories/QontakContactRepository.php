<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QontakContactRepository
{
    public function upsertMany(array $items): void
    {
        $now = now();

        $data = collect($items)->map(fn($c) => [
            'crm_contact_id'   => $c['id'],
            'first_name'       => $c['first_name'] ?? null,
            'last_name'        => $c['last_name'] ?? null,
            'email'            => $c['email'] ?? null,
            'telephone'        => $c['phone'] ?? null,
            'slug'             => Str::slug($c['name'] ?? $c['id']),
            'created_at'       => $now,
            'updated_at'       => $now,
        ])->toArray();

        DB::table('qontak_contacts')->upsert(
            $data,
            ['crm_contact_id'],
            [
                'first_name',
                'last_name',
                'email',
                'telephone',
                'slug',
                'updated_at'
            ]
        );
    }
}
