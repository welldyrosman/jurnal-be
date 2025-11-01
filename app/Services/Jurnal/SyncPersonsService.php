<?php

namespace App\Services\Jurnal;

use App\Models\JurnalPerson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncPersonsService extends JurnalBaseService
{
    public function sync()
    {
        $page = 1;
        $totalSynced = 0;

        do {
            $response = $this->get('contacts', ['page' => $page, 'per_page' => 100]);
            $data = $response['contacts'] ?? [];
            $count = count($data);

            foreach ($data as $contact) {
                $person = JurnalPerson::updateOrCreate(
                    ['jurnal_id' => $contact['id']],
                    [
                        'display_name' => $contact['display_name'],
                        'email' => $contact['email'],
                        'phone' => $contact['phone'],
                        'address' => $contact['address'],
                        'billing_address' => $contact['billing_address'] ?? null,
                        'created_at_jurnal' => $contact['created_at'] ?? null,
                        'updated_at_jurnal' => $contact['updated_at'] ?? null,
                        'synced_at' => now(),
                    ]
                );
                $totalSynced++;
            }

            $page++;
        } while ($count > 0);

        Log::info("Synced {$totalSynced} persons from Jurnal");
        return $totalSynced;
    }
}
