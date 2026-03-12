<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QontakTaskRepository
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
        if (empty($items)) {
            return;
        }

        $now = now();

        $crmDealIds = collect($items)
            ->pluck('crm_deal_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $crmCompanyIds = collect($items)
            ->pluck('crm_company_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $crmContactIds = collect($items)
            ->pluck('crm_person_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        $dealMap = DB::table('qontak_deals')
            ->whereIn('deal_id', $crmDealIds)
            ->pluck('id', 'deal_id');

        $companyMap = DB::table('qontak_companies')
            ->whereIn('crm_company_id', $crmCompanyIds)
            ->pluck('id', 'crm_company_id');

        $contactMap = DB::table('qontak_contacts')
            ->whereIn('crm_contact_id', $crmContactIds)
            ->pluck('id', 'crm_contact_id');

        $rows = collect($items)
            ->map(function ($task) use ($now, $dealMap, $companyMap, $contactMap) {
                $crmTaskId = $task['id'] ?? null;
                if (!is_numeric($crmTaskId)) {
                    return null;
                }

                $crmDealId = $task['crm_deal_id'] ?? null;
                $crmCompanyId = $task['crm_company_id'] ?? null;
                $crmContactId = $task['crm_person_id'] ?? null;

                $teamIds = $task['crm_team_ids'] ?? [];
                if (!is_array($teamIds)) {
                    $teamIds = [];
                }

                return [
                    'crm_task_id' => (int) $crmTaskId,
                    'name' => $task['name'] ?? null,

                    'created_at_qontak' => $this->normalizeDate($task['created_at'] ?? null),
                    'updated_at_qontak' => $this->normalizeDate($task['updated_at'] ?? null),

                    'qontak_contact_id' => $crmContactId ? ($contactMap[(string) $crmContactId] ?? null) : null,
                    'qontak_deal_id' => $crmDealId ? ($dealMap[(string) $crmDealId] ?? null) : null,
                    'qontak_company_id' => $crmCompanyId ? ($companyMap[(string) $crmCompanyId] ?? null) : null,

                    'crm_person_id' => $crmContactId ? (string) $crmContactId : null,
                    'crm_person_full_name' => $task['crm_person_full_name'] ?? null,

                    'crm_deal_id' => $crmDealId ? (string) $crmDealId : null,
                    'crm_deal_name' => $task['crm_deal_name'] ?? null,

                    'crm_company_id' => $crmCompanyId ? (string) $crmCompanyId : null,
                    'crm_company_name' => $task['crm_company_name'] ?? null,

                    'crm_task_status_id' => isset($task['crm_task_status_id']) ? (string) $task['crm_task_status_id'] : null,
                    'crm_task_priority_id' => isset($task['crm_task_priority_id']) ? (string) $task['crm_task_priority_id'] : null,

                    'crm_task_category_id' => isset($task['crm_task_category_id']) ? (string) $task['crm_task_category_id'] : null,
                    'crm_task_category_name' => $task['crm_task_category_name'] ?? null,

                    'user_id' => isset($task['user_id']) ? (string) $task['user_id'] : null,
                    'user_full_name' => $task['user_full_name'] ?? null,

                    'crm_team_ids' => json_encode($teamIds),
                    'crm_team_name' => $task['crm_team_name'] ?? null,

                    'customer_id' => isset($task['customer_id']) ? (string) $task['customer_id'] : null,
                    'customer_name' => $task['customer_name'] ?? null,

                    'due_date' => $this->normalizeDate($task['due_date'] ?? null),
                    'reminder_date' => $this->normalizeDate($task['reminder_date'] ?? null),

                    'detail' => $task['detail'] ?? null,
                    'next_step' => $task['next_step'] ?? null,
                    'attachment' => array_key_exists('attachment', $task) ? json_encode($task['attachment']) : null,

                    'unique_task_id' => $task['unique_task_id'] ?? null,
                    'raw' => json_encode($task),

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

        DB::table('qontak_tasks')->upsert(
            $rows,
            ['crm_task_id'],
            [
                'name',
                'created_at_qontak',
                'updated_at_qontak',
                'qontak_contact_id',
                'qontak_deal_id',
                'qontak_company_id',
                'crm_person_id',
                'crm_person_full_name',
                'crm_deal_id',
                'crm_deal_name',
                'crm_company_id',
                'crm_company_name',
                'crm_task_status_id',
                'crm_task_priority_id',
                'crm_task_category_id',
                'crm_task_category_name',
                'user_id',
                'user_full_name',
                'crm_team_ids',
                'crm_team_name',
                'customer_id',
                'customer_name',
                'due_date',
                'reminder_date',
                'detail',
                'next_step',
                'attachment',
                'unique_task_id',
                'raw',
                'updated_at',
            ]
        );
    }
}
