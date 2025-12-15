<?php

namespace App\Jobs;

use App\Services\Qontak\QontakService;
use App\Repositories\QontakDealRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncQontakDealsJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;

    public function handle(QontakService $service, QontakDealRepository $repo)
    {
        $page = 1;

        while (true) {
            $res = $service->getDeals($page);

            $items = $res['response'] ?? [];

            if (count($items) === 0) {
                break;
            }

            foreach ($items as $deal) {
                $repo->upsertDeal($deal);
            }

            $page++;
        }
    }
}
