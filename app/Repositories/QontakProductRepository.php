<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class QontakProductRepository
{
    public function upsertMany(array $items): void
    {
        $now = now();

        $data = collect($items)->map(fn($p) => [
            'crm_product_id' => $p['id'],
            'name'           => $p['name'] ?? null,
            'currency'       => $p['currency'] ?? null,
            'default_price'          => $p['price'] ?? 0,
            //'quantity'       => $p['quantity'] ?? 0,
            'created_at'     => $now,
            'updated_at'     => $now,
        ])->toArray();

        DB::table('qontak_products')->upsert(
            $data,
            ['crm_product_id'],
            ['name', 'currency', 'default_price',  'updated_at']
        );
    }
}
