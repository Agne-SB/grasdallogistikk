<?php

namespace App\Sync\MobileWorker;

use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ProjectSync
{
    /**
     * Upsert a Project from a Mobile Worker "Order" shaped array.
     * Safe to call from pull sync and from webhooks.
     */
    public static function upsertFromOrder(array $o): Project
    {
        // --- identifiers ---
        $externalId = $o['id'] ?? $o['orderId'] ?? $o['Id'] ?? null;
        $orderKey   = Arr::get($o, 'OrderKey') ?? Arr::get($o, 'orderKey');

        // If orderKey missing but externalId is non-numeric, treat it as number-like key
        if (!$orderKey && $externalId && !is_numeric($externalId)) {
            $orderKey = (string) $externalId;
        }

        // Find existing
        $project = null;
        if ($orderKey)             $project = Project::where('external_number', (string) $orderKey)->first();
        if (!$project && $externalId && is_numeric($externalId)) {
            $project = Project::where('external_id', (int) $externalId)->first();
        }
        if (!$project) {
            $project = new Project();
            $project->bucket = 'prosjekter';
        }

        // --- map normalized/vendor fields ---
        if ($externalId !== null && is_numeric($externalId)) {
            $project->external_id = (int) $externalId;       // ✅ only numeric into INT column
        }

        if ($orderKey) {
            $project->external_number = (string) $orderKey;  // ✅ alphanumeric belongs here
        }

        $project->title = $o['title'] ?? $o['subject'] ?? $o['name'] ?? $project->title ?? 'Uten tittel';

        $customer = is_array($o['customer'] ?? null) ? $o['customer'] : [];
        $project->customer_name = $customer['name']
            ?? $o['customerName']
            ?? Arr::get($customer, 'Name')
            ?? $project->customer_name;

        $project->address = $o['address']
            ?? Arr::get($o, 'site.address')
            ?? $o['location']
            ?? $project->address;

        $vendorStatus    = $o['workStatus'] ?? $o['projectStatus'] ?? $o['status'] ?? null;
        $vendorUpdatedAt = $o['modifyDate'] ?? $o['updated_at'] ?? $o['lastChanged'] ?? null;
        $project->vendor_status     = $vendorStatus ?? $project->vendor_status;
        $project->vendor_updated_at = $vendorUpdatedAt ? Carbon::parse($vendorUpdatedAt) : ($project->vendor_updated_at ?? now());

        $project->supervisor_name =
            $o['supervisorName']
            ?? $o['projectSupervisor']
            ?? Arr::get($o, 'supervisor.name')
            ?? $project->supervisor_name;

        // Geo
        $lat = Arr::get($o, 'geoLocation.lat') ?? Arr::get($o, 'geoLocation.latitude');
        $lng = Arr::get($o, 'geoLocation.lng') ?? Arr::get($o, 'geoLocation.longitude');
        if ($lat !== null && $lng !== null) {
            $project->geo_lat = (float) $lat;
            $project->geo_lng = (float) $lng;
        }

        // closed flag
        $isClosed = in_array(strtolower((string)($o['projectStatus'] ?? '')), ['closed','inactive'], true)
                || in_array(strtolower((string)($o['workStatus'] ?? '')),    ['closed','completed'], true)
                || in_array(strtolower((string)($vendorStatus ?? '')),       ['closed','completed'], true);
        if ($isClosed && !$project->vendor_closed_at) {
            $project->vendor_closed_at = now();
        }

        // keep raw payload for debugging
        $project->payload = $o;

        $project->save();

        return $project;
    }
}
