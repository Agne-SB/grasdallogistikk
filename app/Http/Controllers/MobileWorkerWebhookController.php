<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessMobileWorkerEvent;

class MobileWorkerWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $inboundKey = config('services.mobileworker_webhooks.inbound_key');

        // Verify auth
        if (!hash_equals((string)$request->header('mw-api-key'), (string)$inboundKey)) {
            return response('Unauthorized', 401);
        }

        // Parse JSON safely
        $raw = $request->getContent();
        $json = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('MW webhook non-JSON', ['raw' => substr($raw, 0, 1000)]);
            return response()->noContent();
        }

        // Avoid duplicates
        $eventId = $request->header('X-Event-Id') ?? $json['id'] ?? null;
        $cacheKey = 'mw:webhook:' . ($eventId ?: hash('sha256', $raw));

        if (cache()->add($cacheKey, 1, now()->addMinutes(30)) === false) {
            return response()->noContent(); // already processed
        }

        // Extract event type/register
        $register = $json['register'] ?? $json['type'] ?? $json['event'] ?? 'Unknown';

        // Log briefly for debugging
        Log::info('MW webhook received', [
            'eventId'  => $eventId,
            'register' => $register,
            'headers'  => $request->headers->all(),
        ]);

        // Queue the processing job
        \App\Jobs\ProcessMobileWorkerEvent::dispatch(
            $register,
            $json,
            $eventId
        );

        return response()->noContent();
    }

}
