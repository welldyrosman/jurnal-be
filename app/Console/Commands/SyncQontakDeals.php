<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Qontak\QontakService;
use App\Repositories\{
    QontakDealRepository,
    QontakProductRepository,
    QontakContactRepository,
    QontakCompanyRepository,
    QontakProductAssociationRepository,
};
use Throwable;

class SyncQontakDeals extends Command
{
    protected $signature = 'qontak:sync-deals';
    protected $description = 'Sync products, contacts, companies, and deals from Qontak';

    public function handle(
        QontakService $service,
        QontakProductRepository $productRepo,
        QontakContactRepository $contactRepo,
        QontakCompanyRepository $companyRepo,
        QontakDealRepository $dealRepo,
        QontakProductAssociationRepository $qontakDealProductAssociationRepo
    ) {
        try {
            $this->info('🔄 Syncing Products...');
            $this->syncEntity(
                fn($page) => $service->getProducts($page),
                fn($items) => $productRepo->upsertMany($items)
            );

            $this->info('🔄 Syncing Contacts...');
            $this->syncEntity(
                fn($page) => $service->getContacts($page),
                fn($items) => $contactRepo->upsertMany($items)
            );

            $this->info('🔄 Syncing Companies...');
            $this->syncEntity(
                fn($page) => $service->getCompanies($page),
                fn($items) => $companyRepo->upsertMany($items)
            );

            $this->info('🚀 Syncing Deals...');
            $this->syncEntity(
                fn($page) => $service->getDeals($page),
                fn($items) => $dealRepo->upsertMany($items)
            );

            $this->info('🚀 Syncing Product Association...');
            $this->syncEntity(
                fn($page) => $service->getProductAssociation($page),
                fn($items) => $qontakDealProductAssociationRepo->upsertMany($items)
            );

            $this->info('✅ All Qontak data synced successfully');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            report($e);
            return self::FAILURE;
        }
    }

    /**
     * Generic paginated sync handler
     */
    private function syncEntity(callable $fetcher, callable $handler): void
    {
        $page = 1;

        while (true) {
            $res   = $fetcher($page);
            $items = $res['response'] ?? [];

            if (empty($items)) {
                break;
            }

            $handler($items);

            $this->line("  ✔ Page {$page} synced (" . count($items) . " items)");
            $page++;
        }
    }
}
