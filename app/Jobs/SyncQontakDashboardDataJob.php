<?php

namespace App\Jobs;

use App\Repositories\QontakCompanyRepository;
use App\Repositories\QontakContactRepository;
use App\Repositories\QontakDealRepository;
use App\Repositories\QontakDealStageHistoryRepository;
use App\Repositories\QontakEntityTimelineRepository;
use App\Repositories\QontakPipelineRepository;
use App\Repositories\QontakPipelineStageRepository;
use App\Repositories\QontakProductRepository;
use App\Repositories\QontakTaskRepository;
use App\Services\Qontak\QontakService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncQontakDashboardDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;

    public function __construct(
        public bool $syncTimelines = true,
        public ?int $entityLimit = null,
        public bool $withProgress = false,
    ) {}

    public function handle(
        QontakService $service,
        QontakProductRepository $productRepo,
        QontakContactRepository $contactRepo,
        QontakCompanyRepository $companyRepo,
        QontakDealRepository $dealRepo,
        QontakPipelineRepository $pipelineRepo,
        QontakPipelineStageRepository $pipelineStageRepo,
        QontakTaskRepository $taskRepo,
        QontakDealStageHistoryRepository $stageHistoryRepo,
        QontakEntityTimelineRepository $timelineRepo,
    ): void {
        $this->progress('Mulai sinkronisasi Dashboard Qontak 2');

        // 1) Master datasets for dashboard calculation
        $this->syncPaginated(
            'pipelines',
            fn(int $page) => $service->getPipelines($page),
            fn(array $items) => $pipelineRepo->upsertMany($items)
        );

        $pipelineIds = DB::table('qontak_pipelines')
            ->pluck('crm_pipeline_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();

        $totalPipelines = count($pipelineIds);
        foreach ($pipelineIds as $index => $pipelineId) {
            $this->progress('Pipeline stages ' . ($index + 1) . '/' . max(1, $totalPipelines) . ' (pipeline_id=' . $pipelineId . ')');
            $this->syncPaginated(
                'pipeline_stages:' . $pipelineId,
                fn(int $page) => $service->getPipelineStages($pipelineId, $page, 100),
                fn(array $items) => $pipelineStageRepo->upsertMany($pipelineId, $items)
            );
        }

        $this->syncPaginated(
            'products',
            fn(int $page) => $service->getProducts($page),
            fn(array $items) => $productRepo->upsertMany($items)
        );

        $this->syncPaginated(
            'contacts',
            fn(int $page) => $service->getContacts($page),
            fn(array $items) => $contactRepo->upsertMany($items)
        );

        $this->syncPaginated(
            'companies',
            fn(int $page) => $service->getCompanies($page),
            fn(array $items) => $companyRepo->upsertMany($items)
        );

        $this->syncPaginated(
            'deals',
            fn(int $page) => $service->getDeals($page),
            fn(array $items) => $dealRepo->upsertMany($items)
        );

        $this->syncPaginated(
            'tasks',
            fn(int $page) => $service->getTasks($page, 100),
            fn(array $items) => $taskRepo->upsertMany($items)
        );

        // 2) Deal stage transitions for conversion and weighted reports
        $dealIds = $this->getEntityIds('qontak_deals', 'deal_id');
        $totalDeals = count($dealIds);
        $this->progress('Deal stage history: total deal ' . $totalDeals);

        foreach ($dealIds as $index => $dealId) {
            try {
                $response = $service->getDealStageHistory((string) $dealId);
                $stageHistoryRepo->upsertMany((int) $dealId, $response['response'] ?? []);
            } catch (Throwable $e) {
                logger()->warning('Skip deal stage_history sync', [
                    'crm_deal_id' => $dealId,
                    'error' => $e->getMessage(),
                ]);
            }

            $processed = $index + 1;
            if ($processed === 1 || $processed % 25 === 0 || $processed === $totalDeals) {
                $this->progress('Deal stage history progress: ' . $processed . '/' . max(1, $totalDeals));
            }

            usleep(100000);
        }

        // 3) Timeline activity for summary report
        if ($this->syncTimelines) {
            $this->syncEntityTimelines('deal', 'qontak_deals', 'deal_id', fn($id) => $service->getDealTimeline($id), $timelineRepo);
            $this->syncEntityTimelines('contact', 'qontak_contacts', 'crm_contact_id', fn($id) => $service->getContactTimeline($id), $timelineRepo);
            $this->syncEntityTimelines('company', 'qontak_companies', 'crm_company_id', fn($id) => $service->getCompanyTimeline($id), $timelineRepo);
        }

        $this->progress('Sinkronisasi Dashboard Qontak 2 selesai');
    }

    private function syncPaginated(string $dataset, callable $fetcher, callable $handler): void
    {
        $page = 1;
        $lastDigest = null;
        $maxPages = 500;
        $totalItems = 0;
        $processedPages = 0;

        $this->progress('Mulai sync dataset: ' . $dataset);

        while ($page <= $maxPages) {
            $result = $fetcher($page);
            $items = $result['response'] ?? [];

            if (empty($items)) {
                break;
            }

            $digest = sha1(json_encode($items));
            if ($digest === $lastDigest) {
                break;
            }
            $lastDigest = $digest;

            $handler($items);
            $processedPages++;
            $totalItems += count($items);
            $this->progress('[' . $dataset . '] page ' . $page . ' imported ' . count($items) . ' item (total ' . $totalItems . ')');

            $totalPage = (int) ($result['total_page'] ?? ($result['meta']['total_page'] ?? 0));
            if ($totalPage > 0 && $page >= $totalPage) {
                break;
            }

            $returnedPage = (int) ($result['page'] ?? ($result['meta']['page'] ?? $page));
            if ($page > 1 && $returnedPage < $page) {
                break;
            }

            $page++;
        }

        $this->progress('Selesai dataset: ' . $dataset . ' (' . $totalItems . ' item, ' . $processedPages . ' halaman)');
    }

    private function getEntityIds(string $table, string $column): array
    {
        $query = DB::table($table)
            ->whereNotNull($column)
            ->orderByDesc('updated_at')
            ->select($column);

        if ($this->entityLimit !== null && $this->entityLimit > 0) {
            $query->limit($this->entityLimit);
        }

        return $query
            ->pluck($column)
            ->map(fn($id) => (string) $id)
            ->filter()
            ->values()
            ->all();
    }

    private function syncEntityTimelines(
        string $entityType,
        string $table,
        string $column,
        callable $fetcher,
        QontakEntityTimelineRepository $timelineRepo,
    ): void {
        $ids = $this->getEntityIds($table, $column);
        $endpointUnsupported = false;
        $total = count($ids);

        $this->progress('Timeline ' . $entityType . ': total entity ' . $total);

        foreach ($ids as $index => $crmId) {
            if ($endpointUnsupported) {
                break;
            }

            try {
                $response = $fetcher((string) $crmId);
                $timelineRepo->upsertMany($entityType, (string) $crmId, $response['response'] ?? []);
            } catch (Throwable $e) {
                logger()->warning('Skip timeline sync', [
                    'entity_type' => $entityType,
                    'entity_id' => $crmId,
                    'error' => $e->getMessage(),
                ]);

                if (str_contains($e->getMessage(), 'failed: 405')) {
                    $endpointUnsupported = true;
                    logger()->warning('Stop timeline sync: endpoint not supported for entity type', [
                        'entity_type' => $entityType,
                    ]);
                    $this->progress('Timeline ' . $entityType . ': endpoint tidak didukung (405), stop.');
                }
            }

            $processed = $index + 1;
            if ($processed === 1 || $processed % 25 === 0 || $processed === $total) {
                $this->progress('Timeline ' . $entityType . ' progress: ' . $processed . '/' . max(1, $total));
            }

            usleep(100000);
        }
    }

    private function progress(string $message): void
    {
        if (!$this->withProgress || !app()->runningInConsole()) {
            return;
        }

        $time = now('Asia/Jakarta')->format('H:i:s');
        fwrite(STDOUT, '[' . $time . '] ' . $message . PHP_EOL);
    }
}
