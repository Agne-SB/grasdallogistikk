<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Project;

class MobileWorkerService
{
    protected string $base;
    protected string $apiKey;

    public function __construct()
    {
        $this->base = rtrim(config('services.mobileworker.url'), '/');
        $this->apiKey = (string) config('services.mobileworker.key', '');
    }

    /** Example: GET /public/v2/Configuration */
    public function getConfiguration(): array
    {
        $resp = Http::timeout(20)
            ->withHeaders([
                'Accept'     => 'application/json',
                'mw-api-key' => $this->apiKey,   // ✅ official header
            ])
            ->get("{$this->base}/public/v2/Configuration");

        return $this->wrap($resp);
    }

    /** Example: fetch Orders into local projects table */
    public function fetchOrdersToProjects(): array
{
    $resp = Http::timeout(30)
        ->withHeaders([
            'Accept'     => 'application/json',
            'mw-api-key' => $this->apiKey,   // ✅ official header
        ])
        ->get("{$this->base}/public/v2/Orders");

    if ($resp->failed()) {
        return $this->wrap($resp);
    }

    $items = $resp->json();
    if (!is_array($items)) {
        return ['success' => false, 'note' => 'Unexpected payload'];
    }

    $count = 0;
    $activeExternalIds = [];

    foreach ($items as $o) {
        // 🔑 Check for closed orders
        $projectStatus = strtolower($o['projectStatus'] ?? '');
        $workStatus    = strtolower($o['workStatus'] ?? '');

        if ($projectStatus === 'closed' || $workStatus === 'closed') {
            continue; // skip closed orders
        }

        $externalId = $o['id'] ?? $o['orderId'] ?? $o['Id'] ?? null;
        if (!$externalId) {
            continue;
        }

        $activeExternalIds[] = $externalId;

        Project::updateOrCreate(
            ['external_id' => $externalId],
            [
                'title'               => $o['title'] ?? $o['subject'] ?? $o['name'] ?? 'Uten tittel',
                'customer_name'       => $o['customer']['name'] ?? $o['customerName'] ?? ($o['customer']['Name'] ?? null),
                'address'             => $o['address'] ?? ($o['site']['address'] ?? $o['location'] ?? null),
                'status'              => $o['status'] ?? $o['workStatus'] ?? $o['projectStatus'] ?? null,
                'updated_at_from_api' => now(),
            ]
        );

        $count++;
    }

    // ⚠️ Cleanup: remove any local projects no longer active
    if (!empty($activeExternalIds)) {
        Project::whereNotIn('external_id', $activeExternalIds)->delete();
    }

    return ['success' => true, 'imported' => $count];
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
