<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Http\Client\PendingRequest;
use App\Sync\MobileWorker\ProjectSync;

class MobileWorkerService
{
    protected string $base;
    protected string $apiKey;
    protected string $whBase;
    protected string $whVersion;


    public function __construct()
    {
        $this->base   = rtrim(config('services.mobileworker.url'), '/');
        $this->apiKey = (string) config('services.mobileworker.key', '');
        $this->whBase    = rtrim(config('services.mobileworker_webhooks.url', 'https://webhooks.mworker.com'), '/');
        $this->whVersion = (string) config('services.mobileworker_webhooks.version', 'v1');


        if (blank($this->base) || blank($this->apiKey)) {
            throw new \RuntimeException('MobileWorker URL or API key is missing in config/services.php');
        }
    }

    /** Shared HTTP client with the official header */
    protected function client(): PendingRequest
    {
        return Http::timeout(30)
            ->acceptJson()                    // sets Accept: application/json
            ->withHeaders([
                'mw-api-key' => $this->apiKey // OFFICIAL HEADER
            ])
            ->baseUrl('https://webhooks.mworker.com');
    }

    protected function wh(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(30)
            ->acceptJson()
            ->asJson() 
            ->withHeaders(['mw-api-key' => $this->apiKey])
            ->baseUrl($this->whBase);
    }

    public function createSubscription(array $payload): array
    {
        $resp = $this->wh()->post("/api/{$this->whVersion}/Subscriptions", $payload);
        return $this->wrap($resp);
    }

    public function listSubscriptions(): array
    {
        $resp = $this->wh()->get("/api/{$this->whVersion}/Subscriptions");
        return $this->wrap($resp);
    }

    public function getConfiguration(): array
    {
        $resp = $this->client()
            ->get("{$this->base}/public/v2/Configuration");

        return $this->wrap($resp);
    }

    /** Fetch orders -> local projects (idempotent, keeps your local bucket) */
    public function fetchOrdersToProjects(): array
    {
        $resp = $this->client()->get("{$this->base}/public/v2/Orders");

        if ($resp->failed()) {
            return $this->wrap($resp);
        }

        $items = $resp->json();
        if (!is_array($items)) {
            return ['success' => false, 'note' => 'Unexpected payload'];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $o) {
            $externalId = $o['id'] ?? $o['orderId'] ?? $o['Id'] ?? null;
            $orderKey   = \Illuminate\Support\Arr::get($o, 'OrderKey') ?? \Illuminate\Support\Arr::get($o, 'orderKey');

            if (!$externalId && !$orderKey) {
                $skipped++;
                continue;
            }

            // check if the project already exists 
            $exists = \App\Models\Project::query()
                ->when($orderKey, fn($q) => $q->orWhere('external_number', (string)$orderKey))
                ->when($externalId, fn($q) => $q->orWhere('external_id', (string)$externalId))
                ->exists();

            \App\Sync\MobileWorker\ProjectSync::upsertFromOrder($o);

            if ($exists) {
                $updated++;
            } else {
                $created++;
            }
        }

        return [
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }


    /* ------------ helpers ------------- */
    protected function wrap(\Illuminate\Http\Client\Response $resp): array
    {
        return [
            'success' => $resp->ok(),
            'status'  => $resp->status(),
            'body'    => $resp->json() ?: $resp->body(),
        ];
    }
}
