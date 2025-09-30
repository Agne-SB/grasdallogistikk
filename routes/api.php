<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MobileWorkerWebhookController;

Route::post('/webhooks/mobile-worker', [MobileWorkerWebhookController::class, 'handle'])
    ->name('webhooks.mobile-worker');