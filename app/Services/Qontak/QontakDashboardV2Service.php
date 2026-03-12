<?php

namespace App\Services\Qontak;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QontakDashboardV2Service
{
    public const CONTENT_CODES = [
        'SALES_PERFORMANCE',
        'YEARLY_SALES_COMPARISON',
        'DEALS_WON',
        'SUMMARY_REPORT',
        'DEALS_BY_STAGE',
        'SOURCES',
        'LOST_REASONS',
        'TASKS',
        'WEIGHTED_AVERAGE_DEALS_BY_STAGE',
        'DEALS_PIPELINE_CONVERSION',
        'CUMULATIVE_DAILY_SALES_PERFORMANCE_BY_MONTH',
    ];

    public function buildDashboard(array $filters, ?array $contentCodes = null): array
    {
        $normalized = $this->normalizeFilters($filters);
        $codes = $this->resolveContentCodes($contentCodes);

        $contents = [];
        foreach ($codes as $code) {
            $contents[$code] = $this->buildContentByCode($code, $normalized);
        }

        return [
            'filters' => [
                'start_date' => $normalized['start']->toDateString(),
                'end_date' => $normalized['end']->toDateString(),
                'metric' => $normalized['metric'],
                'selected_period' => $normalized['selected_period'],
                'pipeline_id' => $normalized['pipeline_id'],
                'pipeline_name' => $normalized['pipeline_name'],
                'stage_name' => $normalized['stage_name'],
                'team_name' => $normalized['team_name'],
                'source_entity' => $normalized['source_entity'],
                'month' => $normalized['month'],
                'task_status_id' => $normalized['task_status_id'],
                'task_priority_id' => $normalized['task_priority_id'],
            ],
            'available_contents' => self::CONTENT_CODES,
            'contents' => $contents,
        ];
    }

    public function buildSingleContent(string $contentCode, array $filters): array
    {
        $code = strtoupper(trim($contentCode));
        if (!in_array($code, self::CONTENT_CODES, true)) {
            return [
                'code' => $code,
                'data' => null,
                'error' => 'Unknown content code',
            ];
        }

        $normalized = $this->normalizeFilters($filters);

        return [
            'code' => $code,
            'data' => $this->buildContentByCode($code, $normalized),
        ];
    }

    public function teamOptions(): array
    {
        if (!Schema::hasTable('qontak_tasks')) {
            return ['items' => []];
        }

        $items = DB::table('qontak_tasks')
            ->selectRaw('TRIM(crm_team_name) as team_name')
            ->whereNotNull('crm_team_name')
            ->whereRaw("TRIM(crm_team_name) <> ''")
            ->distinct()
            ->orderBy('team_name')
            ->pluck('team_name')
            ->map(fn($name) => (string) $name)
            ->values()
            ->all();

        return ['items' => $items];
    }

    public function stageOptions(array $filters = []): array
    {
        if (!Schema::hasTable('qontak_deals')) {
            return ['items' => []];
        }

        $query = DB::table('qontak_deals as d')
            ->selectRaw('TRIM(d.crm_stage_name) as stage_name')
            ->whereNotNull('d.crm_stage_name')
            ->whereRaw("TRIM(d.crm_stage_name) <> ''");

        if (!empty($filters['pipeline_name']) && strtolower((string) $filters['pipeline_name']) !== 'all') {
            $query->where('d.crm_pipeline_name', (string) $filters['pipeline_name']);
        }

        if (!empty($filters['team_name']) && strtolower((string) $filters['team_name']) !== 'all') {
            $this->applyTeamFilter($query, ['team_name' => (string) $filters['team_name']], 'd');
        }

        $items = $query
            ->distinct()
            ->orderBy('stage_name')
            ->pluck('stage_name')
            ->map(fn($name) => (string) $name)
            ->values()
            ->all();

        return ['items' => $items];
    }

    private function buildContentByCode(string $code, array $normalized): mixed
    {
        return match ($code) {
            'SALES_PERFORMANCE' => $this->salesPerformance($normalized),
            'YEARLY_SALES_COMPARISON' => $this->yearlySalesComparison($normalized),
            'DEALS_WON' => $this->dealsWon($normalized),
            'SUMMARY_REPORT' => $this->summaryReport($normalized),
            'DEALS_BY_STAGE' => $this->dealsByStage($normalized),
            'SOURCES' => $this->sources($normalized),
            'LOST_REASONS' => $this->lostReasons($normalized),
            'TASKS' => $this->tasks($normalized),
            'WEIGHTED_AVERAGE_DEALS_BY_STAGE' => $this->weightedAverageDealsByStage($normalized),
            'DEALS_PIPELINE_CONVERSION' => $this->dealsPipelineConversion($normalized),
            'CUMULATIVE_DAILY_SALES_PERFORMANCE_BY_MONTH' => $this->cumulativeDailySalesPerformanceByMonth($normalized),
            default => null,
        };
    }

    private function normalizeFilters(array $filters): array
    {
        $now = now('Asia/Jakarta');

        $start = $this->parseDate($filters['start_date'] ?? null, $now->copy()->subMonths(3)->startOfDay(), false);
        $end = $this->parseDate($filters['end_date'] ?? null, $now->copy()->endOfDay(), true);

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $metric = strtolower((string) ($filters['metric'] ?? 'amount'));
        if (!in_array($metric, ['qty', 'amount'], true)) {
            $metric = 'amount';
        }

        $selectedPeriod = strtolower((string) ($filters['selected_period'] ?? 'monthly'));
        if (!in_array($selectedPeriod, ['daily', 'weekly', 'monthly'], true)) {
            $selectedPeriod = 'monthly';
        }

        $month = (string) ($filters['month'] ?? $end->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = $end->format('Y-m');
        }

        $sourceEntity = strtolower((string) ($filters['source_entity'] ?? 'all'));
        if (!in_array($sourceEntity, ['all', 'contacts', 'companies', 'deals'], true)) {
            $sourceEntity = 'all';
        }

        return [
            'start' => $start,
            'end' => $end,
            'metric' => $metric,
            'selected_period' => $selectedPeriod,
            'pipeline_id' => isset($filters['pipeline_id']) && $filters['pipeline_id'] !== '' ? (string) $filters['pipeline_id'] : null,
            'pipeline_name' => isset($filters['pipeline_name']) && $filters['pipeline_name'] !== '' ? (string) $filters['pipeline_name'] : null,
            'stage_name' => isset($filters['stage_name']) && $filters['stage_name'] !== '' && strtolower((string) $filters['stage_name']) !== 'all'
                ? (string) $filters['stage_name']
                : null,
            'team_name' => isset($filters['team_name']) && $filters['team_name'] !== '' && strtolower((string) $filters['team_name']) !== 'all'
                ? (string) $filters['team_name']
                : null,
            'source_entity' => $sourceEntity,
            'month' => $month,
            'task_status_id' => isset($filters['task_status_id']) && $filters['task_status_id'] !== '' ? (string) $filters['task_status_id'] : null,
            'task_priority_id' => isset($filters['task_priority_id']) && $filters['task_priority_id'] !== '' ? (string) $filters['task_priority_id'] : null,
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'rows_per_page' => min(100, max(1, (int) ($filters['rowsPerPage'] ?? 10))),
            'sort_by' => isset($filters['sortBy']) && $filters['sortBy'] !== '' ? (string) $filters['sortBy'] : 'date',
            'sort_type' => strtolower((string) ($filters['sortType'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
        ];
    }

    private function resolveContentCodes(?array $contentCodes): array
    {
        if (empty($contentCodes)) {
            return self::CONTENT_CODES;
        }

        return collect($contentCodes)
            ->map(fn($code) => strtoupper(trim((string) $code)))
            ->filter(fn($code) => in_array($code, self::CONTENT_CODES, true))
            ->unique()
            ->values()
            ->all();
    }

    private function parseDate(mixed $value, Carbon $fallback, bool $isEnd): Carbon
    {
        if (empty($value)) {
            return $fallback;
        }

        $value = (string) $value;

        $formats = ['Y-m-d', 'd/m/Y', 'Y-m-d H:i:s', Carbon::ATOM];
        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value, 'Asia/Jakarta');
                return $isEnd ? $dt->endOfDay() : $dt->startOfDay();
            } catch (\Throwable) {
                // continue
            }
        }

        try {
            $dt = Carbon::parse($value, 'Asia/Jakarta');
            return $isEnd ? $dt->endOfDay() : $dt->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function applyPipelineFilter($query, array $filters, string $alias = 'd'): void
    {
        if (!empty($filters['pipeline_id'])) {
            $query->where("{$alias}.crm_pipeline_id", (string) $filters['pipeline_id']);
            return;
        }

        if (!empty($filters['pipeline_name'])) {
            $query->where("{$alias}.crm_pipeline_name", (string) $filters['pipeline_name']);
        }
    }

    private function applyTeamFilter($query, array $filters, string $alias = 'd'): void
    {
        if (empty($filters['team_name'])) {
            return;
        }

        $teamName = (string) $filters['team_name'];
        if ($alias === 't') {
            $query->where("{$alias}.crm_team_name", $teamName);
            return;
        }

        if (!Schema::hasTable('qontak_tasks')) {
            return;
        }

        $query->whereExists(function ($subQuery) use ($alias, $teamName) {
            $subQuery->select(DB::raw(1))
                ->from('qontak_tasks as tq')
                ->whereColumn('tq.qontak_deal_id', "{$alias}.id")
                ->where('tq.crm_team_name', $teamName);
        });
    }

    private function periodLabel(Carbon $date, string $selectedPeriod): string
    {
        return match ($selectedPeriod) {
            'daily' => $date->format('Y-m-d'),
            'weekly' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('o-\\WW'),
            default => $date->format('Y-m'),
        };
    }

    private function periodSortKey(Carbon $date, string $selectedPeriod): string
    {
        return match ($selectedPeriod) {
            'daily' => $date->copy()->startOfDay()->format('Y-m-d H:i:s'),
            'weekly' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d H:i:s'),
            default => $date->copy()->startOfMonth()->format('Y-m-d H:i:s'),
        };
    }

    private function wonDealsInRange(array $filters): Collection
    {
        if (!Schema::hasTable('qontak_deal_stage_histories') || !Schema::hasTable('qontak_deals')) {
            return collect();
        }

        $start = $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s');
        $end = $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s');

        $sub = DB::table('qontak_deal_stage_histories as h')
            ->join('qontak_deals as d', 'd.deal_id', '=', 'h.crm_deal_id')
            ->where('h.current_stage_name', 'Won')
            ->whereNotNull('h.moved_date')
            ->whereBetween('h.moved_date', [$start, $end]);

        $this->applyPipelineFilter($sub, $filters, 'd');
        $this->applyTeamFilter($sub, $filters, 'd');

        $sub
            ->select('h.crm_deal_id', DB::raw('MIN(h.moved_date) as won_at'))
            ->groupBy('h.crm_deal_id');

        return DB::table('qontak_deals as d')
            ->joinSub($sub, 'w', function ($join) {
                $join->on('d.deal_id', '=', 'w.crm_deal_id');
            })
            ->select([
                'd.deal_id',
                'd.amount',
                'd.creator_id',
                'd.creator_name',
                'd.crm_pipeline_id',
                'd.crm_pipeline_name',
                'w.won_at',
            ])
            ->get();
    }

    private function salesPerformance(array $filters): array
    {
        $rows = $this->wonDealsInRange($filters);

        $grouped = [];
        foreach ($rows as $row) {
            $wonAt = Carbon::parse($row->won_at, 'Asia/Jakarta');
            $period = $this->periodLabel($wonAt, $filters['selected_period']);
            $sortKey = $this->periodSortKey($wonAt, $filters['selected_period']);

            if (!isset($grouped[$period])) {
                $grouped[$period] = [
                    'period' => $period,
                    'sort_key' => $sortKey,
                    'total' => 0.0,
                ];
            }

            $grouped[$period]['total'] += $filters['metric'] === 'qty' ? 1 : (float) ($row->amount ?? 0);
        }

        $items = collect($grouped)
            ->sortBy('sort_key')
            ->values()
            ->map(fn($item) => [
                'period' => $item['period'],
                'total' => round((float) $item['total'], 2),
            ])
            ->all();

        return [
            'metric' => $filters['metric'],
            'selected_period' => $filters['selected_period'],
            'items' => $items,
        ];
    }

    private function yearlySalesComparison(array $filters): array
    {
        if (!Schema::hasTable('qontak_deal_stage_histories') || !Schema::hasTable('qontak_deals')) {
            return [
                'metric' => $filters['metric'],
                'items' => [],
            ];
        }

        $sub = DB::table('qontak_deal_stage_histories as h')
            ->join('qontak_deals as d', 'd.deal_id', '=', 'h.crm_deal_id')
            ->where('h.current_stage_name', 'Won')
            ->whereNotNull('h.moved_date');

        $this->applyPipelineFilter($sub, $filters, 'd');
        $this->applyTeamFilter($sub, $filters, 'd');

        $sub
            ->selectRaw('h.crm_deal_id, YEAR(h.moved_date) as won_year, MIN(h.moved_date) as won_at')
            ->groupBy('h.crm_deal_id', DB::raw('YEAR(h.moved_date)'));

        $rows = DB::table('qontak_deals as d')
            ->joinSub($sub, 'w', function ($join) {
                $join->on('d.deal_id', '=', 'w.crm_deal_id');
            })
            ->selectRaw('w.won_year as year_num, SUM(d.amount) as total_amount, COUNT(*) as total_qty')
            ->groupBy('w.won_year')
            ->orderBy('w.won_year')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $year = (string) $row->year_num;
            $map[$year] = $filters['metric'] === 'qty'
                ? (float) $row->total_qty
                : (float) $row->total_amount;
        }

        if (Schema::hasTable('qontak_dashboard_overrides')) {
            $metricCode = $filters['metric'] === 'qty'
                ? 'YEARLY_SALES_COMPARISON_WON_QTY'
                : 'YEARLY_SALES_COMPARISON_WON_AMOUNT';

            $overrides = DB::table('qontak_dashboard_overrides')
                ->where('metric_code', $metricCode)
                ->where('period_type', 'year')
                ->where('active', true)
                ->get();

            foreach ($overrides as $override) {
                $map[(string) $override->period_key] = (float) $override->value;
            }
        }

        $items = collect($map)
            ->map(fn($value, $year) => [
                'year' => (int) $year,
                'total' => round((float) $value, 2),
            ])
            ->sortBy('year')
            ->values()
            ->all();

        return [
            'metric' => $filters['metric'],
            'items' => $items,
        ];
    }

    private function dealsWon(array $filters): array
    {
        $rows = $this->wonDealsInRange($filters);

        $periods = [];
        $creators = [];
        $matrix = [];

        foreach ($rows as $row) {
            $wonAt = Carbon::parse($row->won_at, 'Asia/Jakarta');
            $periodLabel = $this->periodLabel($wonAt, $filters['selected_period']);
            $periodSort = $this->periodSortKey($wonAt, $filters['selected_period']);
            $creator = trim((string) ($row->creator_name ?? 'Unknown'));
            if ($creator === '') {
                $creator = 'Unknown';
            }

            $periods[$periodLabel] = $periodSort;
            $creators[$creator] = true;

            $key = $periodLabel . '|' . $creator;
            if (!isset($matrix[$key])) {
                $matrix[$key] = 0.0;
            }

            $matrix[$key] += $filters['metric'] === 'qty' ? 1 : (float) ($row->amount ?? 0);
        }

        $orderedPeriods = collect($periods)
            ->sort()
            ->keys()
            ->values()
            ->all();

        $orderedCreators = collect(array_keys($creators))->sort()->values()->all();

        $series = [];
        foreach ($orderedCreators as $creator) {
            $data = [];
            foreach ($orderedPeriods as $period) {
                $data[] = round((float) ($matrix[$period . '|' . $creator] ?? 0), 2);
            }

            $series[] = [
                'name' => $creator,
                'data' => $data,
            ];
        }

        $rowsDetail = [];
        foreach ($orderedPeriods as $period) {
            foreach ($orderedCreators as $creator) {
                $value = (float) ($matrix[$period . '|' . $creator] ?? 0);
                if ($value <= 0) {
                    continue;
                }

                $rowsDetail[] = [
                    'period' => $period,
                    'creator_name' => $creator,
                    'total' => round($value, 2),
                ];
            }
        }

        return [
            'metric' => $filters['metric'],
            'selected_period' => $filters['selected_period'],
            'categories' => $orderedPeriods,
            'series' => $series,
            'rows' => $rowsDetail,
        ];
    }

    private function summaryReport(array $filters): array
    {
        if (!Schema::hasTable('qontak_entity_timelines')) {
            return [
                'total_events' => 0,
                'by_action' => [],
                'by_actor' => [],
                'by_target' => [],
                'by_entity_type' => [],
                'events' => [],
                'pagination' => [
                    'page' => $filters['page'],
                    'rowsPerPage' => $filters['rows_per_page'],
                    'total' => 0,
                ],
                'notes' => ['Timeline table belum tersedia.'],
            ];
        }

        $query = DB::table('qontak_entity_timelines as t')
            ->whereNotNull('t.event_at')
            ->whereBetween('t.event_at', [
                $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ]);

        if (!empty($filters['pipeline_id']) || !empty($filters['pipeline_name'])) {
            $query->join('qontak_deals as d', 'd.id', '=', 't.qontak_deal_id');
            $this->applyPipelineFilter($query, $filters, 'd');
        }

        if (!empty($filters['team_name']) && Schema::hasTable('qontak_tasks')) {
            $query->whereExists(function ($subQuery) use ($filters) {
                $subQuery->select(DB::raw(1))
                    ->from('qontak_tasks as tq')
                    ->whereColumn('tq.qontak_deal_id', 't.qontak_deal_id')
                    ->where('tq.crm_team_name', (string) $filters['team_name']);
            });
        }

        $events = (clone $query)
            ->select([
                't.entity_type',
                't.actor',
                't.action',
                't.target',
                't.summary',
            ])
            ->get();

        $sortByColumn = match (strtolower((string) ($filters['sort_by'] ?? 'date'))) {
            'action' => 't.action',
            'note' => 't.summary',
            default => 't.event_at',
        };

        $sortType = strtolower((string) ($filters['sort_type'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $page = max(1, (int) ($filters['page'] ?? 1));
        $rowsPerPage = min(100, max(1, (int) ($filters['rows_per_page'] ?? 10)));
        $offset = ($page - 1) * $rowsPerPage;

        $totalEvents = (clone $query)->count();
        $pagedRows = (clone $query)
            ->select([
                't.event_at',
                't.action',
                't.target',
                't.summary',
            ])
            ->orderBy($sortByColumn, $sortType)
            ->offset($offset)
            ->limit($rowsPerPage)
            ->get();

        $byAction = [];
        $byActor = [];
        $byTarget = [];
        $byEntityType = [];
        $eventRows = [];

        foreach ($events as $event) {
            $actionClass = $this->classifyAction($event->action, $event->summary);
            $actor = trim((string) ($event->actor ?? 'Unknown'));
            $target = trim((string) ($event->target ?? 'Unknown'));
            $entityType = trim((string) ($event->entity_type ?? 'unknown'));
            $summary = trim((string) ($event->summary ?? ''));
            $actionText = trim((string) ($event->action ?? ''));
            $note = $summary !== '' ? $summary : trim($actionText . ' ' . $target);

            if ($actor === '') {
                $actor = 'Unknown';
            }
            if ($target === '') {
                $target = 'Unknown';
            }
            if ($entityType === '') {
                $entityType = 'unknown';
            }
            if ($note === '') {
                $note = '-';
            }

            $byAction[$actionClass] = ($byAction[$actionClass] ?? 0) + 1;
            $byActor[$actor] = ($byActor[$actor] ?? 0) + 1;
            $byTarget[$target] = ($byTarget[$target] ?? 0) + 1;
            $byEntityType[$entityType] = ($byEntityType[$entityType] ?? 0) + 1;

        }

        foreach ($pagedRows as $row) {
            $actionClass = $this->classifyAction($row->action, $row->summary);
            $summary = trim((string) ($row->summary ?? ''));
            $actionText = trim((string) ($row->action ?? ''));
            $target = trim((string) ($row->target ?? ''));
            $note = $summary !== '' ? $summary : trim($actionText . ' ' . $target);

            $eventRows[] = [
                'date' => $row->event_at
                    ? Carbon::parse($row->event_at, 'Asia/Jakarta')->format('Y-m-d H:i:s')
                    : null,
                'action' => $actionClass,
                'note' => $note !== '' ? $note : '-',
            ];
        }

        return [
            'total_events' => (int) $totalEvents,
            'by_action' => $this->sortMapDesc($byAction),
            'by_actor' => $this->sortMapDesc($byActor, 15),
            'by_target' => $this->sortMapDesc($byTarget, 15),
            'by_entity_type' => $this->sortMapDesc($byEntityType),
            'events' => $eventRows,
            'pagination' => [
                'page' => $page,
                'rowsPerPage' => $rowsPerPage,
                'total' => (int) $totalEvents,
            ],
            'notes' => [
                'Summary report dihitung dari timeline entity yang tersinkron (deal/contact/company).',
            ],
        ];
    }

    private function classifyAction(?string $action, ?string $summary = null): string
    {
        $value = strtolower(trim((string) $action . ' ' . (string) $summary));

        if (str_contains($value, 'delet') || str_contains($value, 'remove')) {
            return 'DELETE';
        }

        if (str_contains($value, 'add') || str_contains($value, 'create')) {
            return 'CREATE';
        }

        if (
            str_contains($value, 'chang')
            || str_contains($value, 'updat')
            || str_contains($value, 'edit')
            || str_contains($value, 'move')
            || str_contains($value, 'associat')
        ) {
            return 'UPDATE';
        }

        return 'OTHER';
    }

    private function dealsByStage(array $filters): array
    {
        if (!Schema::hasTable('qontak_deals')) {
            return [
                'overall' => [],
                'by_pipeline' => [],
            ];
        }

        $query = DB::table('qontak_deals as d')
            ->whereBetween('d.created_at_qontak', [
                $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ]);

        $this->applyPipelineFilter($query, $filters, 'd');
        $this->applyTeamFilter($query, $filters, 'd');

        $rows = $query
            ->select([
                'd.crm_pipeline_name',
                'd.crm_stage_name',
                DB::raw('COUNT(*) as total_deals'),
                DB::raw('SUM(d.amount) as total_amount'),
            ])
            ->groupBy('d.crm_pipeline_name', 'd.crm_stage_name')
            ->orderBy('d.crm_pipeline_name')
            ->orderBy('d.crm_stage_name')
            ->get();

        $overall = [];
        $byPipeline = [];

        foreach ($rows as $row) {
            $pipeline = $row->crm_pipeline_name ?? 'Undefined Pipeline';
            $stage = $row->crm_stage_name ?? 'Undefined Stage';

            $byPipeline[$pipeline][] = [
                'stage' => $stage,
                'total_deals' => (int) $row->total_deals,
                'total_amount' => round((float) ($row->total_amount ?? 0), 2),
            ];

            if (!isset($overall[$stage])) {
                $overall[$stage] = [
                    'stage' => $stage,
                    'total_deals' => 0,
                    'total_amount' => 0.0,
                ];
            }

            $overall[$stage]['total_deals'] += (int) $row->total_deals;
            $overall[$stage]['total_amount'] += (float) ($row->total_amount ?? 0);
        }

        return [
            'overall' => collect($overall)
                ->map(fn($item) => [
                    'stage' => $item['stage'],
                    'total_deals' => $item['total_deals'],
                    'total_amount' => round((float) $item['total_amount'], 2),
                ])
                ->sortByDesc('total_deals')
                ->values()
                ->all(),
            'by_pipeline' => collect($byPipeline)
                ->map(fn($items, $pipeline) => [
                    'pipeline' => $pipeline,
                    'items' => collect($items)->sortByDesc('total_deals')->values()->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function sources(array $filters): array
    {
        $entities = [];

        if (in_array($filters['source_entity'], ['all', 'contacts'], true) && Schema::hasTable('qontak_contacts')) {
            $entities['contacts'] = DB::table('qontak_contacts')
                ->selectRaw("COALESCE(NULLIF(TRIM(crm_source_name), ''), 'Undefined Source') as source_name")
                ->selectRaw('COUNT(*) as total')
                ->whereBetween('created_at_qontak', [
                    $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                    $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
                ])
                ->groupBy('source_name')
                ->orderByDesc('total')
                ->get();
        }

        if (in_array($filters['source_entity'], ['all', 'companies'], true) && Schema::hasTable('qontak_companies')) {
            $entities['companies'] = DB::table('qontak_companies')
                ->selectRaw("COALESCE(NULLIF(TRIM(crm_source_name), ''), 'Undefined Source') as source_name")
                ->selectRaw('COUNT(*) as total')
                ->whereBetween('created_at_qontak', [
                    $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                    $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
                ])
                ->groupBy('source_name')
                ->orderByDesc('total')
                ->get();
        }

        if (in_array($filters['source_entity'], ['all', 'deals'], true) && Schema::hasTable('qontak_deals')) {
            $dealQuery = DB::table('qontak_deals as d')
                ->leftJoin('qontak_sources as s', 's.id', '=', 'd.qontak_source_id')
                ->selectRaw("COALESCE(NULLIF(TRIM(s.crm_source_name), ''), 'Undefined Source') as source_name")
                ->selectRaw('COUNT(*) as total')
                ->whereBetween('d.created_at_qontak', [
                    $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                    $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
                ]);

            $this->applyPipelineFilter($dealQuery, $filters, 'd');
            $this->applyTeamFilter($dealQuery, $filters, 'd');
            if (!empty($filters['stage_name'])) {
                $dealQuery->where('d.crm_stage_name', (string) $filters['stage_name']);
            }

            $entities['deals'] = $dealQuery
                ->groupBy('source_name')
                ->orderByDesc('total')
                ->get();
        }

        $combined = [];
        foreach ($entities as $rows) {
            foreach ($rows as $row) {
                $name = (string) $row->source_name;
                $combined[$name] = ($combined[$name] ?? 0) + (int) $row->total;
            }
        }

        return [
            'source_entity' => $filters['source_entity'],
            'combined' => collect($combined)
                ->map(fn($total, $name) => ['source_name' => $name, 'total' => (int) $total])
                ->sortByDesc('total')
                ->values()
                ->all(),
            'by_entity' => collect($entities)
                ->map(fn($rows, $entity) => [
                    'entity' => $entity,
                    'items' => collect($rows)->map(fn($row) => [
                        'source_name' => (string) $row->source_name,
                        'total' => (int) $row->total,
                    ])->values()->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function lostReasons(array $filters): array
    {
        if (!Schema::hasTable('qontak_deal_stage_histories') || !Schema::hasTable('qontak_deals')) {
            return [
                'items' => [],
                'notes' => ['Table stage history/deals belum tersedia.'],
            ];
        }

        $sub = DB::table('qontak_deal_stage_histories as h')
            ->join('qontak_deals as d', 'd.deal_id', '=', 'h.crm_deal_id')
            ->whereRaw('LOWER(h.current_stage_name) = ?', ['lost'])
            ->whereNotNull('h.moved_date')
            ->whereBetween('h.moved_date', [
                $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ]);

        $this->applyPipelineFilter($sub, $filters, 'd');
        $this->applyTeamFilter($sub, $filters, 'd');

        $sub
            ->select('h.crm_deal_id', DB::raw('MIN(h.moved_date) as lost_at'))
            ->groupBy('h.crm_deal_id');

        $rows = DB::table('qontak_deals as d')
            ->joinSub($sub, 'l', function ($join) {
                $join->on('d.deal_id', '=', 'l.crm_deal_id');
            })
            ->selectRaw("COALESCE(NULLIF(TRIM(d.crm_lost_reason_name), ''), 'Undefined Lost Reason') as lost_reason")
            ->selectRaw('COUNT(*) as total_deals')
            ->selectRaw('SUM(d.amount) as total_amount')
            ->groupBy('lost_reason')
            ->orderByDesc('total_deals')
            ->get();

        return [
            'items' => $rows->map(fn($row) => [
                'lost_reason' => (string) $row->lost_reason,
                'total_deals' => (int) $row->total_deals,
                'total_amount' => round((float) ($row->total_amount ?? 0), 2),
            ])->values()->all(),
            'notes' => empty($rows->all())
                ? ['Tidak ada deal yang masuk stage Lost pada periode terpilih.']
                : [],
        ];
    }

    private function tasks(array $filters): array
    {
        if (!Schema::hasTable('qontak_tasks')) {
            return [
                'summary' => [
                    'total_tasks' => 0,
                ],
                'items' => [],
            ];
        }

        $query = DB::table('qontak_tasks as t')
            ->whereBetween('t.created_at_qontak', [
                $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ]);

        if (!empty($filters['task_status_id'])) {
            $query->where('t.crm_task_status_id', (string) $filters['task_status_id']);
        }

        if (!empty($filters['task_priority_id'])) {
            $query->where('t.crm_task_priority_id', (string) $filters['task_priority_id']);
        }

        $this->applyTeamFilter($query, $filters, 't');

        if (!empty($filters['pipeline_id']) || !empty($filters['pipeline_name'])) {
            $query->leftJoin('qontak_deals as d', 'd.id', '=', 't.qontak_deal_id');
            $this->applyPipelineFilter($query, $filters, 'd');
        }

        $totalTasks = (int) (clone $query)->count('t.crm_task_id');

        $byStatusRows = (clone $query)
            ->selectRaw("COALESCE(NULLIF(TRIM(t.crm_task_status_id), ''), 'Undefined') as status_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status_key')
            ->get();

        $byPriorityRows = (clone $query)
            ->selectRaw("COALESCE(NULLIF(TRIM(t.crm_task_priority_id), ''), 'Undefined') as priority_key")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('priority_key')
            ->get();

        $rows = $query
            ->select([
                't.crm_task_id',
                't.name',
                't.crm_deal_name',
                't.crm_company_name',
                't.crm_person_full_name',
                't.crm_task_status_id',
                't.crm_task_priority_id',
                't.crm_task_category_name',
                't.crm_team_name',
                't.due_date',
                't.reminder_date',
                't.user_full_name',
                't.created_at_qontak',
                't.updated_at_qontak',
            ])
            ->orderByDesc('t.updated_at_qontak')
            ->limit(100)
            ->get();

        return [
            'summary' => [
                'total_tasks' => $totalTasks,
                'by_status' => $this->sortMapDesc(
                    $byStatusRows->mapWithKeys(fn($row) => [(string) $row->status_key => (int) $row->total])->all()
                ),
                'by_priority' => $this->sortMapDesc(
                    $byPriorityRows->mapWithKeys(fn($row) => [(string) $row->priority_key => (int) $row->total])->all()
                ),
            ],
            'items' => $rows->map(fn($row) => [
                'crm_task_id' => (int) $row->crm_task_id,
                'name' => $row->name,
                'deal_name' => $row->crm_deal_name,
                'company_name' => $row->crm_company_name,
                'contact_name' => $row->crm_person_full_name,
                'status_id' => $row->crm_task_status_id,
                'priority_id' => $row->crm_task_priority_id,
                'category_name' => $row->crm_task_category_name,
                'team_name' => $row->crm_team_name,
                'due_date' => $row->due_date,
                'reminder_date' => $row->reminder_date,
                'owner_name' => $row->user_full_name,
            ])->values()->all(),
        ];
    }

    private function weightedAverageDealsByStage(array $filters): array
    {
        if (!Schema::hasTable('qontak_deals') || !Schema::hasTable('qontak_pipeline_stages')) {
            return [
                'pipeline' => null,
                'items' => [],
            ];
        }

        $effectiveFilters = $filters;
        if (empty($effectiveFilters['pipeline_id']) && empty($effectiveFilters['pipeline_name'])) {
            $effectiveFilters['pipeline_name'] = 'Public Training';
        }

        $query = DB::table('qontak_deals as d')
            ->join('qontak_pipeline_stages as s', function ($join) {
                $join->on('s.crm_pipeline_id', '=', 'd.crm_pipeline_id')
                    ->on('s.crm_stage_id', '=', 'd.crm_stage_id');
            })
            ->whereBetween('d.created_at_qontak', [
                $effectiveFilters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                $effectiveFilters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ]);

        $this->applyPipelineFilter($query, $effectiveFilters, 'd');
        $this->applyTeamFilter($query, $effectiveFilters, 'd');

        $rows = $query
            ->select([
                'd.crm_pipeline_id',
                'd.crm_pipeline_name',
                'd.crm_stage_id',
                'd.crm_stage_name',
                's.stage_order',
                's.win_probability',
                DB::raw('COUNT(*) as total_deals'),
                DB::raw('SUM(d.amount) as total_amount'),
            ])
            ->groupBy(
                'd.crm_pipeline_id',
                'd.crm_pipeline_name',
                'd.crm_stage_id',
                'd.crm_stage_name',
                's.stage_order',
                's.win_probability'
            )
            ->orderBy('s.stage_order')
            ->get();

        $items = $rows
            ->map(function ($row) {
                $amount = (float) ($row->total_amount ?? 0);
                $probability = (float) ($row->win_probability ?? 0);
                $weighted = $amount * ($probability / 100);

                return [
                    'stage_id' => $row->crm_stage_id,
                    'stage_name' => $row->crm_stage_name,
                    'stage_order' => $row->stage_order,
                    'win_probability' => round($probability, 2),
                    'total_deals' => (int) $row->total_deals,
                    'total_amount' => round($amount, 2),
                    'weighted_value' => round($weighted, 2),
                ];
            })
            ->filter(fn($row) => (float) $row['weighted_value'] > 0)
            ->values()
            ->all();

        return [
            'pipeline' => [
                'id' => $effectiveFilters['pipeline_id'],
                'name' => $effectiveFilters['pipeline_name'],
            ],
            'items' => $items,
        ];
    }

    private function dealsPipelineConversion(array $filters): array
    {
        if (!Schema::hasTable('qontak_deal_stage_histories') || !Schema::hasTable('qontak_deals')) {
            return [
                'nodes' => [],
                'links' => [],
            ];
        }

        $query = DB::table('qontak_deal_stage_histories as h')
            ->join('qontak_deals as d', 'd.deal_id', '=', 'h.crm_deal_id')
            ->whereNotNull('h.prev_stage_name')
            ->whereNotNull('h.current_stage_name')
            ->whereBetween('h.moved_date', [
                $filters['start']->copy()->startOfDay()->format('Y-m-d H:i:s'),
                $filters['end']->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ]);

        $this->applyPipelineFilter($query, $filters, 'd');
        $this->applyTeamFilter($query, $filters, 'd');

        $rows = $query
            ->select([
                'h.prev_stage_name',
                'h.current_stage_name',
                DB::raw('COUNT(*) as total_transitions'),
            ])
            ->groupBy('h.prev_stage_name', 'h.current_stage_name')
            ->orderByDesc('total_transitions')
            ->get();

        $totalByPrev = [];
        foreach ($rows as $row) {
            $prev = (string) $row->prev_stage_name;
            $totalByPrev[$prev] = ($totalByPrev[$prev] ?? 0) + (int) $row->total_transitions;
        }

        $nodes = [];
        $links = [];
        foreach ($rows as $row) {
            $source = (string) $row->prev_stage_name;
            $target = (string) $row->current_stage_name;
            $value = (int) $row->total_transitions;

            $nodes[$source] = ['name' => $source];
            $nodes[$target] = ['name' => $target];

            $links[] = [
                'source' => $source,
                'target' => $target,
                'value' => $value,
                'conversion_rate' => round(
                    ($value / max(1, (int) ($totalByPrev[$source] ?? 1))) * 100,
                    2
                ),
            ];
        }

        return [
            'nodes' => array_values($nodes),
            'links' => $links,
        ];
    }

    private function cumulativeDailySalesPerformanceByMonth(array $filters): array
    {
        if (!Schema::hasTable('qontak_deal_stage_histories') || !Schema::hasTable('qontak_deals')) {
            return [
                'month' => $filters['month'],
                'items' => [],
            ];
        }

        $monthStart = Carbon::createFromFormat('Y-m', $filters['month'], 'Asia/Jakarta')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $sub = DB::table('qontak_deal_stage_histories as h')
            ->join('qontak_deals as d', 'd.deal_id', '=', 'h.crm_deal_id')
            ->where('h.current_stage_name', 'Won')
            ->whereNotNull('h.moved_date')
            ->whereBetween('h.moved_date', [
                $monthStart->copy()->startOfDay()->format('Y-m-d H:i:s'),
                $monthEnd->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ]);

        $this->applyPipelineFilter($sub, $filters, 'd');
        $this->applyTeamFilter($sub, $filters, 'd');

        $sub
            ->select('h.crm_deal_id', DB::raw('MIN(h.moved_date) as won_at'))
            ->groupBy('h.crm_deal_id');

        $rows = DB::table('qontak_deals as d')
            ->joinSub($sub, 'w', function ($join) {
                $join->on('d.deal_id', '=', 'w.crm_deal_id');
            })
            ->selectRaw('DATE(w.won_at) as won_date')
            ->selectRaw('COUNT(*) as total_qty')
            ->selectRaw('SUM(d.amount) as total_amount')
            ->groupBy(DB::raw('DATE(w.won_at)'))
            ->orderBy(DB::raw('DATE(w.won_at)'))
            ->get();

        $dailyMap = [];
        foreach ($rows as $row) {
            $dailyMap[(string) $row->won_date] = $filters['metric'] === 'qty'
                ? (float) $row->total_qty
                : (float) $row->total_amount;
        }

        $items = [];
        $cumulative = 0.0;
        $cursor = $monthStart->copy();
        while ($cursor->lte($monthEnd)) {
            $dateKey = $cursor->format('Y-m-d');
            $value = (float) ($dailyMap[$dateKey] ?? 0);
            $cumulative += $value;

            $items[] = [
                'date' => $dateKey,
                'daily_total' => round($value, 2),
                'cumulative_total' => round($cumulative, 2),
            ];

            $cursor->addDay();
        }

        return [
            'month' => $filters['month'],
            'metric' => $filters['metric'],
            'items' => $items,
        ];
    }

    private function sortMapDesc(array $map, ?int $limit = null): array
    {
        $result = collect($map)
            ->map(fn($total, $name) => [
                'name' => (string) $name,
                'total' => (int) $total,
            ])
            ->sortByDesc('total')
            ->values();

        if ($limit !== null && $limit > 0) {
            $result = $result->take($limit)->values();
        }

        return $result->all();
    }
}
