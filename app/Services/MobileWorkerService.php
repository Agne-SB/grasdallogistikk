<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Http\Client\PendingRequest;

class MobileWorkerService
{
    protected string $base;
    protected string $apiKey;

    public function __construct()
    {
        $this->base   = rtrim(config('services.mobileworker.url'), '/');
        $this->apiKey = (string) config('services.mobileworker.key', '');

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
            ]);
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

        $created = 0; $updated = 0; $skipped = 0;

        foreach ($items as $o) {
            // --- identifiers ---
            $externalId = $o['id'] ?? $o['orderId'] ?? $o['Id'] ?? null;
            $orderKey   = Arr::get($o, 'OrderKey') ?? Arr::get($o, 'orderKey'); // <- canonical number

            if (!$externalId && !$orderKey) { $skipped++; continue; }

            // --- find existing project (prefer OrderKey to avoid dupes) ---
            $project = null;
            if ($orderKey)   $project = Project::where('external_number', (string) $orderKey)->first();
            if (!$project && $externalId) $project = Project::where('external_id', (string) $externalId)->first();
            if (!$project) {
                $project = new Project();
                $project->bucket = 'prosjekter'; // default only on create
                $created++;
            } else {
                $updated++;
            }

            // --- map normalized/vendor fields (safe to overwrite) ---
            $project->external_id     = $externalId ? (string) $externalId : $project->external_id;
            $project->external_number = $orderKey   ? (string) $orderKey   : $project->external_number;

            $project->title        = $o['title'] ?? $o['subject'] ?? $o['name'] ?? 'Uten tittel';
            $customer              = is_array($o['customer'] ?? null) ? $o['customer'] : [];
            $project->customer_name= $customer['name'] ?? $o['customerName'] ?? Arr::get($customer, 'Name');
            $project->address      = $o['address'] ?? Arr::get($o, 'site.address') ?? $o['location'] ?? null;

            // vendor status / timestamps
            $vendorStatus    = $o['workStatus'] ?? $o['projectStatus'] ?? $o['status'] ?? null;
            $vendorUpdatedAt = $o['modifyDate'] ?? $o['updated_at'] ?? $o['lastChanged'] ?? null;
            $project->vendor_status      = $vendorStatus;
            $project->vendor_updated_at  = $vendorUpdatedAt ? Carbon::parse($vendorUpdatedAt) : now();

            // supervisor (name only)
            $project->supervisor_name =
                $o['supervisorName']
                ?? $o['projectSupervisor']
                ?? Arr::get($o, 'supervisor.name') 
                ?? $project->supervisor_name;

            // keep raw payload for debugging
            $project->payload = $o;

            // closed flag
            $isClosed = in_array(strtolower((string)($o['projectStatus'] ?? '')), ['closed','inactive'], true)
                    || in_array(strtolower((string)($o['workStatus'] ?? '')),    ['closed','completed'], true)
                    || in_array(strtolower((string)($vendorStatus ?? '')),       ['closed','completed'], true);

            if ($isClosed && !$project->vendor_closed_at) {
                $project->vendor_closed_at = now();
            }

            $project->save();
        }

        $lat = Arr::get($o, 'geoLocation.lat') ?? Arr::get($o, 'geoLocation.latitude');
        $lng = Arr::get($o, 'geoLocation.lng') ?? Arr::get($o, 'geoLocation.longitude');
        if (!is_null($lat) && !is_null($lng)) {
            $project->geo_lat = (float) $lat;
            $project->geo_lng = (float) $lng;
        }

        return ['success' => true, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped];
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
