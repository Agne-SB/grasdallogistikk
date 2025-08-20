<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MobileWorkerService;

class SyncMobileWorkerOrders extends Command
{
    protected $signature = 'mw:sync-orders';
    protected $description = 'Fetch orders from Mobile Worker and save them as projects';

    public function handle(MobileWorkerService $mw)
    {
        $this->info("🔄 Syncing orders from Mobile Worker...");

        $result = $mw->fetchOrdersToProjects();

        if ($result['success']) {
            $this->info("✅ Imported {$result['imported']} orders into projects table.");
        } else {
            $this->error("❌ Failed: " . json_encode($result));
        }

        return 0;
    }
}
