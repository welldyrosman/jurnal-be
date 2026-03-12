<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QontakEntityTimelineRepository
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

    public function upsertMany(string $entityType, int|string $entityCrmId, array $items): void
    {
        if (empty($items)) {
            return;
        }

        $entityCrmId = (string) $entityCrmId;
        $now = now();

        $qontakDealId = null;
        $qontakContactId = null;
        $qontakCompanyId = null;

        if ($entityType === 'deal') {
            $qontakDealId = DB::table('qontak_deals')
                ->where('deal_id', $entityCrmId)
                ->value('id');
        }

        if ($entityType === 'contact') {
            $qontakContactId = DB::table('qontak_contacts')
                ->where('crm_contact_id', $entityCrmId)
                ->value('id');
        }

        if ($entityType === 'company') {
            $qontakCompanyId = DB::table('qontak_companies')
                ->where('crm_company_id', $entityCrmId)
                ->value('id');
        }

        $rows = collect($items)
            ->map(function ($item) use (
                $entityType,
                $entityCrmId,
                $qontakDealId,
                $qontakContactId,
                $qontakCompanyId,
                $now
            ) {
                $eventId = isset($item['id']) && is_numeric($item['id'])
                    ? (int) $item['id']
                    : null;

                $eventAt = $this->normalizeDate($item['when'] ?? null);

                $fingerprint = sha1(json_encode([
                    'entity_type' => $entityType,
                    'entity_crm_id' => $entityCrmId,
                    'event_id' => $eventId,
                    'when' => $item['when'] ?? null,
                    'why' => $item['why'] ?? null,
                    'how' => $item['how'] ?? null,
                    'content' => $item['content'] ?? null,
                ]));

                return [
                    'entity_type' => $entityType,
                    'entity_crm_id' => $entityCrmId,
                    'event_id' => $eventId,
                    'event_at' => $eventAt,
                    'actor' => $item['who'] ?? null,
                    'action' => $item['how'] ?? null,
                    'target' => $item['what'] ?? null,
                    'summary' => $item['why'] ?? null,
                    'content' => $item['content'] ?? null,
                    'task_id' => isset($item['task_id']) && is_numeric($item['task_id']) ? (int) $item['task_id'] : null,
                    'ticket_id' => isset($item['ticket_id']) && is_numeric($item['ticket_id']) ? (int) $item['ticket_id'] : null,
                    'auditable_id' => isset($item['auditable_id']) && is_numeric($item['auditable_id']) ? (int) $item['auditable_id'] : null,
                    'email_id' => isset($item['email_id']) && is_numeric($item['email_id']) ? (int) $item['email_id'] : null,
                    'crm_deal_id' => isset($item['crm_deal_id']) && is_numeric($item['crm_deal_id']) ? (int) $item['crm_deal_id'] : null,
                    'qontak_deal_id' => $qontakDealId,
                    'qontak_contact_id' => $qontakContactId,
                    'qontak_company_id' => $qontakCompanyId,
                    'fingerprint' => $fingerprint,
                    'raw' => json_encode($item),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        DB::table('qontak_entity_timelines')->upsert(
            $rows,
            ['fingerprint'],
            [
                'event_id',
                'event_at',
                'actor',
                'action',
                'target',
                'summary',
                'content',
                'task_id',
                'ticket_id',
                'auditable_id',
                'email_id',
                'crm_deal_id',
                'qontak_deal_id',
                'qontak_contact_id',
                'qontak_company_id',
                'raw',
                'updated_at',
            ]
        );
    }
}
