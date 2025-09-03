<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\DeviationController;

/* Pages */
Route::get('/',               [HomeController::class, 'index'])->name('home');
Route::get('/prosjekter',     [ProjectsController::class, 'index'])->name('projects.index');
Route::get('/henting',        [ProjectsController::class, 'henting'])->name('henting.index');
Route::get('/montering',      [ProjectsController::class, 'montering'])->name('montering.index');
Route::get('/planlegging',    [ProjectsController::class, 'planlegging'])->name('planlegging.index');

/* Prosjekter: row edits & move */
Route::post ('/prosjekter/{project}/status', [ProjectsController::class, 'setStatus'])->name('projects.status');
Route::patch('/prosjekter/{project}',        [ProjectsController::class, 'update'])->name('projects.update');
Route::patch('/projects/{project}/bucket',   [ProjectsController::class, 'moveBucket'])->name('projects.moveBucket');

/* Henting flow */
Route::patch('/projects/{project}/delivered',        [ProjectsController::class, 'markDelivered'])->name('projects.delivered');
Route::patch('/projects/{project}/ready',            [ProjectsController::class, 'markReady'])->name('projects.ready');
Route::patch('/projects/{project}/schedule-pickup',  [ProjectsController::class, 'schedulePickup'])->name('projects.schedulePickup');
Route::patch('/projects/{project}/collected',        [ProjectsController::class, 'markCollected'])->name('projects.collected');
Route::patch('/projects/{project}/contacted',        [ProjectsController::class, 'markContacted'])->name('projects.contacted');

/* Montering flow */
Route::patch('/projects/{project}/mount-start', [ProjectsController::class, 'markMountStart'])->name('projects.mountStart');
Route::patch('/projects/{project}/mount-done',  [ProjectsController::class, 'markMountDone'])->name('projects.mountDone');

/* Avvik */
Route::get  ('/avvik',                          [DeviationController::class,  'index'])->name('avvik.index');
Route::post ('/avvik',                          [DeviationController::class,  'store'])->name('avvik.store');
Route::patch('/avvik/{deviation}/resolve',      [DeviationController::class,  'resolve'])->name('avvik.resolve');
Route::patch('/avvik/{deviation}/resolve-route',[DeviationController::class,  'resolveRoute'])->name('avvik.resolveRoute');
