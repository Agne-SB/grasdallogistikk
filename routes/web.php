<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectsController;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\MobileWorkerService;
use App\Http\Controllers\DeviationController;

/* --- App pages --- */
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/prosjekter', [ProjectsController::class, 'index'])
    ->name('projects.index');

Route::get('/montering', [ProjectsController::class, 'montering'])
    ->name('montering.index');

Route::get('/henting', [ProjectsController::class, 'henting'])
->name('henting.index');

Route::get('/planlegging', [ProjectsController::class, 'planlegging'])
->name('planlegging.index');


/* Set status MP/HO and return to list */
Route::post('/prosjekter/{project}/status', [ProjectsController::class, 'setStatus'])
    ->name('projects.status');

Route::patch('/prosjekter/{project}', [ProjectsController::class, 'update'])
    ->name('projects.update');

/* Move between pages */
Route::patch('/projects/{project}/bucket', [ProjectsController::class, 'moveBucket'])
    ->name('projects.moveBucket');

/* Henting actions */
Route::patch('/projects/{project}/delivered',       [ProjectsController::class, 'markDelivered'])
->name('projects.delivered');
Route::patch('/projects/{project}/ready',           [ProjectsController::class, 'markReady'])
->name('projects.ready');
Route::patch('/projects/{project}/contacted',       [ProjectsController::class, 'markContacted'])
->name('projects.contacted');
Route::patch('/projects/{project}/schedule-pickup', [ProjectsController::class, 'schedulePickup'])
->name('projects.schedulePickup');
Route::patch('/projects/{project}/collected',       [ProjectsController::class, 'markCollected'])
->name('projects.collected');

/* Avvik actions*/
Route::patch('/projects/{project}/ready',     [ProjectsController::class, 'markReady'])
->name('projects.ready');
Route::post ('/avvik',                        [DeviationController::class,  'store'])
->name('avvik.store');
Route::get  ('/avvik',                        [DeviationController::class,  'index'])
->name('avvik.index');
Route::patch('/avvik/{deviation}/resolve', [DeviationController::class, 'resolve'])
    ->name('avvik.resolve');

/* Montering actions */
Route::patch('/projects/{project}/mount-start', [ProjectsController::class, 'markMountStart'])
->name('projects.mountStart');
Route::patch('/projects/{project}/mount-done',  [ProjectsController::class, 'markMountDone'])
->name('projects.mountDone');
