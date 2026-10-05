<?php

use Illuminate\Support\Facades\Route;
use Platform\Regimen\Http\Controllers\Api\PlanController;

/**
 * Regimen API Routes
 *
 * Prefix `/api/regimen`, Middleware `['api', 'api.auth']` (Bearer-Token via Passport)
 * werden von ModuleRouter::apiGroup('regimen', ...) im ServiceProvider gesetzt.
 *
 * Kurskatalog für die öffentliche Website. Team ergibt sich aus dem Token-User.
 */
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/health', [PlanController::class, 'health']);
Route::get('/plans/{uuid}', [PlanController::class, 'show']);
