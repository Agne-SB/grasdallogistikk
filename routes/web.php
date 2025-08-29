<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectsController;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\MobileWorkerService;

/* --- App pages --- */
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/prosjekter', [ProjectsController::class, 'index'])
    ->name('projects.index');

Route::get('/montering', [ProjectsController::class, 'montering'])
    ->name('montering.index');

Route::get('/henting', [ProjectsController::class, 'henting'])
->name('henting.index');

/* Set status MP/HO and return to list */
Route::post('/prosjekter/{project}/status', [ProjectsController::class, 'setStatus'])
    ->name('projects.status');

Route::patch('/prosjekter/{project}', [ProjectsController::class, 'update'])
    ->name('projects.update');

/* Move between pages */
Route::patch('/projects/{project}/bucket', [ProjectsController::class, 'moveBucket'])
    ->name('projects.moveBucket');


