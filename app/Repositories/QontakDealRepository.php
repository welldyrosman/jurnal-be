<?php

namespace App\Repositories;

use App\Models\QontakDeal;
use Illuminate\Support\Facades\DB;

class QontakDealRepository
{
    /**
     * Bulk upsert deals
     */
    public function upsertMany(array $items): void
    {
        $now = now();

        $data = collect($items)->map(function ($deal) use ($now) {
            return [
                'deal_id'               => $deal['id'],
                'name'                  => $deal['name'] ?? null,
                'slug'                  => $deal['slug'] ?? null,

                'created_at_qontak'     => $deal['created_at'] ?? null,
                'updated_at_qontak'     => $deal['updated_at'] ?? null,

                'currency'              => $deal['currency'] ?? null,
                'amount'                => $deal['size'] ?? 0,

                'crm_pipeline_id'       => $deal['crm_pipeline_id'] ?? null,
                'crm_pipeline_name'     => $deal['crm_pipeline_name'] ?? null,

                'crm_stage_id'          => $deal['crm_stage_id'] ?? null,
                'crm_stage_name'        => $deal['crm_stage_name'] ?? null,

                'crm_priority_id'       => $deal['crm_priority_id'] ?? null,
                'crm_priority_name'     => $deal['crm_priority_name'] ?? null,

                'crm_lost_reason_id'    => $deal['crm_lost_reason_id'] ?? null,
                'crm_lost_reason_name'  => $deal['crm_lost_reason_name'] ?? null,

                // FK ke tabel lokal (sudah disync sebelumnya)
                'qontak_company_id'     => $this->mapCompanyId($deal['crm_company_id'] ?? null),
                'qontak_source_id'      => $this->mapSourceId($deal['crm_source_id'] ?? null, $deal['crm_source_name']),

                'start_date'            => $deal['start_date'] ?? null,
                'closed_date'           => $deal['closed_date'] ?? null,
                'expired_date'          => $deal['expired_date'] ?? null,

                'creator_id'            => $deal['creator_id'] ?? null,
                'creator_name'          => $deal['creator_name'] ?? null,

                'unique_deal_id'        => $deal['unique_deal_id'] ?? null,
                'idempotency_key'       => $deal['idempotency_key'] ?? null,

                'raw'                   => json_encode($deal),

                'created_at'            => $now,
                'updated_at'            => $now,
            ];
        })->toArray();

        DB::table('qontak_deals')->upsert(
            $data,
            ['deal_id'],
            [
                'name',
                'slug',
                'currency',
                'amount',
                'crm_pipeline_id',
                'crm_pipeline_name',
                'crm_stage_id',
                'crm_stage_name',
                'crm_priority_id',
                'crm_priority_name',
                'crm_lost_reason_id',
                'crm_lost_reason_name',
                'qontak_company_id',
                'qontak_source_id',
                'start_date',
                'closed_date',
                'expired_date',
                'creator_id',
                'creator_name',
                'raw',
                'updated_at',
            ]
        );

        // Sinkronisasi relasi (dipisah agar tetap cepat & aman)
        $this->syncDealProducts($items);
        $this->syncAdditionalFields($items);
    }

    /**
     * Sync deal products
     */
    private function syncDealProducts(array $items): void
    {
        foreach ($items as $deal) {
            $dealModel = QontakDeal::where('deal_id', $deal['id'])->first();
            if (!$dealModel) {
                continue;
            }

            $dealModel->products()->delete();

            $ids   = $deal['product_association_ids'] ?? [];
            $names = $deal['product_association_name'] ?? [];
            $qtys  = $deal['product_association_quantity'] ?? [];
            $prices = $deal['product_association_price'] ?? [];

            $crmProductIds = $ids ?? [];

            $productMap = DB::table('qontak_products')
                ->whereIn('crm_product_id', $crmProductIds)
                ->pluck('name', 'crm_product_id');

            foreach ($ids as $i => $crmProductId) {
                $dealModel->products()->create([
                    'qontak_product_id' => $productMap[$crmProductId] ?? null,
                    'crm_product_id'    => $crmProductId,
                    'product_name'      => $names[$i] ?? null,
                    'quantity'          => $qtys[$i] ?? 0,
                    'price'             => $prices[$i] ?? 0,
                ]);
            }
        }
    }

    /**
     * Sync additional fields
     */
    private function syncAdditionalFields(array $items): void
    {
        foreach ($items as $deal) {
            $dealModel = QontakDeal::where('deal_id', $deal['id'])->first();
            if (!$dealModel) {
                continue;
            }

            $dealModel->additionalFields()->delete();

            foreach ($deal['additional_fields'] ?? [] as $f) {
                $dealModel->additionalFields()->create([
                    'field_id'   => $f['id'] ?? null,
                    'name'       => $f['name'] ?? null,
                    'value'      => $f['value'] ?? null,
                    'value_name' => $f['value_name'] ?? null,
                ]);
            }
        }
    }

    /**
     * Mapping helper
     */
    private function mapCompanyId(?string $crmCompanyId): ?int
    {
        if (!$crmCompanyId) return null;

        return DB::table('qontak_companies')
            ->where('crm_company_id', $crmCompanyId)
            ->value('id');
    }

    private function mapSourceId(?string $crmSourceId, ?string $sourceName = null): ?int
    {
        if (!$crmSourceId) {
            return null;
        }

        return DB::transaction(function () use ($crmSourceId, $sourceName) {
            $id = DB::table('qontak_sources')
                ->where('crm_source_id', $crmSourceId)
                ->value('id');

            if ($id) {
                return $id;
            }

            return DB::table('qontak_sources')->insertGetId([
                'crm_source_id' => $crmSourceId,
                'crm_source_name'          => $sourceName,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });
    }
}
