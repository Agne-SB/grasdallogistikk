<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MobileWorkerService;

class SyncMobileWorkerOrders extends Command
{
    /** php artisan mw:sync-orders -n */
    protected $signature = 'mw:sync-orders';
    protected $description = 'Fetch orders from Mobile Worker and save them as projects';

    public function handle(MobileWorkerService $mw)
    {
        $this->info('Syncing orders from Mobile Worker...');

        try {
            $result = $mw->fetchOrdersToProjects();
        } catch (\Throwable $e) {
            $this->error('Exception: '.$e->getMessage());
            return 1;
        }

        if (!($result['success'] ?? false)) {
            $status = $result['status'] ?? '?';
            $body   = $result['body'] ?? $result;
            $this->error('Failed (status '.$status.'): '.(is_string($body) ? $body : json_encode($body)));
            return 1;
        }

        $created  = (int)($result['created'] ?? 0);
        $updated  = (int)($result['updated'] ?? 0);
        $skipped  = (int)($result['skipped'] ?? 0);
        $imported = $created + $updated;

        $this->info("✅ Imported {$imported} (created {$created}, updated {$updated}, skipped {$skipped}).");
        return 0;
    }
}
