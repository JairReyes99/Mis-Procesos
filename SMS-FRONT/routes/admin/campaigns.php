<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CampaignController;

Route::middleware(['auth'])->prefix('sms')->name('sms.')->group(function () {

    Route::middleware('module.perms:campanas')->prefix('campaigns')->name('campaigns.')->group(function () {

        // ── AJAX endpoints (antes de las rutas con {uuid} para evitar conflictos) ──
        Route::post('/ajax/upload-file', [CampaignController::class, 'uploadFile'])
            ->middleware('permission:crear.campanas')
            ->name('upload_file');

        Route::post('/ajax/preview', [CampaignController::class, 'preview'])
            ->middleware('permission:crear.campanas')
            ->name('preview');

        Route::post('/ajax/segments', [CampaignController::class, 'calculateSegments'])
            ->middleware('permission:crear.campanas')
            ->name('segments');

        Route::post('/ajax/count-phones', [CampaignController::class, 'countPhones'])
            ->middleware('permission:crear.campanas')
            ->name('count_phones');

        // ── Rutas CRUD ────────────────────────────────────────────────────────────
        Route::get('/', [CampaignController::class, 'index'])
            ->middleware('permission:ver.campanas')
            ->name('index');

        Route::get('/create', [CampaignController::class, 'create'])
            ->middleware('permission:crear.campanas')
            ->name('create');

        Route::post('/', [CampaignController::class, 'store'])
            ->middleware('permission:crear.campanas')
            ->name('store');

        Route::get('/{uuid}', [CampaignController::class, 'show'])
            ->middleware('permission:ver.campanas')
            ->name('show');

        Route::delete('/{uuid}', [CampaignController::class, 'destroy'])
            ->middleware('permission:eliminar.campanas')
            ->name('destroy');

        Route::patch('/{uuid}/pause', [CampaignController::class, 'pause'])
            ->middleware('permission:editar.campanas')
            ->name('pause');

        Route::patch('/{uuid}/resume', [CampaignController::class, 'resume'])
            ->middleware('permission:editar.campanas')
            ->name('resume');
    });
});
