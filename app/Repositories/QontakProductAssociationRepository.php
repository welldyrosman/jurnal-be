<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class QontakProductAssociationRepository
{
    public function upsertMany(array $items): void
    {
        if (empty($items)) return;

        $now = now();
        $crmDealIds = collect($items)
            ->pluck('crm_deal_id')
            ->filter()
            ->unique()
            ->values();

        $dealMap = DB::table('qontak_deals')
            ->whereIn('deal_id', $crmDealIds)
            ->pluck('id', 'deal_id');

        /**
         * 2. Pastikan semua product ada
         * map: crm_product_id => qontak_products.id
         */
        $crmProductIds = collect($items)
            ->pluck('crm_product_id')
            ->filter()
            ->unique()
            ->values();

        $productMap = DB::table('qontak_products')
            ->whereIn('crm_product_id', $crmProductIds)
            ->pluck('id', 'crm_product_id');

        $insertProducts = [];

        foreach ($items as $item) {
            $crmProductId = $item['crm_product_id'] ?? null;

            if (!$crmProductId || isset($productMap[$crmProductId])) {
                continue;
            }

            $insertProducts[$crmProductId] = [
                'crm_product_id' => $crmProductId,
                'name'           => $item['name'] ?? null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (!empty($insertProducts)) {
            DB::table('qontak_products')->insert(array_values($insertProducts));

            $productMap = DB::table('qontak_products')
                ->whereIn('crm_product_id', $crmProductIds)
                ->pluck('id', 'crm_product_id');
        }

        /**
         * 3. Upsert product associations
         */
        $payload = collect($items)->map(function ($item) use ($dealMap, $productMap, $now) {
            return [
                'crm_association_id' => $item['id'],
                'qontak_deal_id'     => $dealMap[$item['crm_deal_id']] ?? null,
                'qontak_product_id' => $productMap[$item['crm_product_id']] ?? null,
                'crm_deal_id'        => $item['crm_deal_id'] ?? null,
                'crm_product_id'     => $item['crm_product_id'] ?? null,
                'product_name'       => $item['name'] ?? null,
                'currency'           => $item['currency'] ?? null,
                'price'              => (float) ($item['price'] ?? 0),
                'quantity'           => (float) ($item['quantity'] ?? 1),
                'discount'           => (float) ($item['discount'] ?? 0),
                'nominal_discount'   => (float) ($item['nominal_discount'] ?? 0),
                'total_price'        => (float) ($item['total_price'] ?? 0),
                'crm_tag_ids'        => json_encode($item['crm_tag_ids'] ?? []),
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        })
            ->filter(fn($row) => $row['qontak_deal_id'] !== null)
            ->values()
            ->toArray();

        DB::table('qontak_deal_product_associations')->upsert(
            $payload,
            ['crm_association_id'],
            [
                'qontak_deal_id',
                'qontak_product_id',
                'product_name',
                'currency',
                'price',
                'quantity',
                'discount',
                'nominal_discount',
                'total_price',
                'crm_tag_ids',
                'updated_at'
            ]
        );
    }
}
