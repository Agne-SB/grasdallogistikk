<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use App\Sync\MobileWorker\ProjectSync;

class ProcessMobileWorkerEvent implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public string $register,
        public array $payload,
        public ?string $eventId = null
    ) {}

    public function handle(): void
    {

        \Log::info('ProcessMobileWorkerEvent start', [
            'register' => $this->register,
            'eventId'  => $this->eventId,
    ]);

        $reg = strtolower($this->register);

        switch ($reg) {
            case 'order':
            case 'orders':
                $order = $this->extractOrder($this->payload);
                if (!$order) {
                    Log::warning('MW webhook: Order register but no order payload found', [
                        'eventId' => $this->eventId,
                        'keys' => array_keys($this->payload),
                    ]);
                    return;
                }

                // Reuse  existing mapper 
                ProjectSync::upsertFromOrder($order);
                Log::info('MW webhook: Order processed', [
                    'eventId' => $this->eventId,
                    'orderId' => $order['orderId'] ?? $order['id'] ?? $order['Id'] ?? null,
                    'orderKey'=> $order['OrderKey'] ?? $order['orderKey'] ?? null,
                ]);
                break;

            // Add more later (Customer, etc.)
            default:
                Log::info('MW webhook: unhandled register', ['register' => $this->register]);
        }

            \Log::info('ProcessMobileWorkerEvent done', [
                'register' => $this->register,
                'eventId'  => $this->eventId,
        ]);


    }

    /** Pull the order object from common shapes */
    private function extractOrder(array $p): ?array
    {
        if ($this->looksLikeOrder($p)) return $p;
        if (isset($p['data']) && is_array($p['data']) && $this->looksLikeOrder($p['data'])) return $p['data'];
        foreach (['order','Order','payload','Payload'] as $k) {
            if (isset($p[$k]) && is_array($p[$k]) && $this->looksLikeOrder($p[$k])) return $p[$k];
        }
        return null;
    }

    private function looksLikeOrder(array $o): bool
    {
        return isset($o['orderId']) || isset($o['OrderKey']) || isset($o['orderKey']) || isset($o['id']) || isset($o['Id']);
    }
}
