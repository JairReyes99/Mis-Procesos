<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Core\CmsAccountController;
use App\Http\Controllers\Core\CmsMenuController;
use App\Http\Controllers\Core\CmsSubMenuController;
use App\Http\Controllers\Core\CmsRoleController;
use App\Http\Controllers\Core\CmsCompanyController;
use App\Http\Controllers\Admin\CompanyCreditController;
use App\Http\Controllers\Admin\CompanyUserController;
use App\Http\Controllers\Core\ProfileController;
use App\Http\Controllers\Core\CmsSettingsController;
use App\Http\Controllers\Admin\TestSmsController;
use App\Http\Controllers\Admin\DashboardController;

Route::middleware(['auth'])->group(function () {

    // Home
    Route::get('/home', [TestSmsController::class, 'home'])->name('home');

    // SMS de prueba
    Route::post('/sms/test-send', [TestSmsController::class, 'send'])->name('sms.test.send');

    // Dashboard
    Route::middleware('module.perms:dashboard')->prefix('dashboard')->name('dashboard')->group(function () {
        Route::get('/',      [DashboardController::class, 'index'])->middleware('permission:ver.dashboard')->name('');
        Route::get('/data',  [DashboardController::class, 'data']) ->middleware('permission:ver.dashboard')->name('.data');
    });

    // Perfil de usuario
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'changePassword'])->name('password');
    });

    // Panel de Administración
    Route::prefix('management')->name('management.')->group(function () {

        // ── Usuarios ─────────────────────────────────────────────────────
        Route::middleware('module.perms:usuarios')->prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/',               [CmsAccountController::class, 'index'])  ->middleware('permission:ver.usuarios')     ->name('index');
            Route::get('/create',         [CmsAccountController::class, 'create']) ->middleware('permission:crear.usuarios')   ->name('create');
            Route::post('/',              [CmsAccountController::class, 'store'])  ->middleware('permission:crear.usuarios')   ->name('store');
            Route::get('/{account}/edit', [CmsAccountController::class, 'edit'])   ->middleware('permission:editar.usuarios')  ->name('edit');
            Route::put('/{account}',      [CmsAccountController::class, 'update']) ->middleware('permission:editar.usuarios')  ->name('update');
            Route::delete('/{account}',   [CmsAccountController::class, 'destroy'])->middleware('permission:eliminar.usuarios')->name('destroy');

            Route::post('/{account}/reset-password',    [CmsAccountController::class, 'resetPassword'])       ->middleware('permission:editar.usuarios')->name('reset_password');
            Route::post('/{account}/permission',        [CmsAccountController::class, 'updateUserPermission']) ->middleware('permission:editar.usuarios')->name('update_permission');
            Route::post('/{account}/reset-permissions', [CmsAccountController::class, 'resetUserPermissions']) ->middleware('permission:editar.usuarios')->name('reset_permissions');
        });

        // ── Menús ─────────────────────────────────────────────────────────
        Route::middleware('module.perms:menus')->prefix('menus')->name('menus.')->group(function () {
            Route::get('/',            [CmsMenuController::class, 'index'])  ->middleware('permission:ver.menus')     ->name('index');
            Route::post('/',           [CmsMenuController::class, 'store'])  ->middleware('permission:crear.menus')   ->name('store');
            Route::get('/{menu}/edit', [CmsMenuController::class, 'edit'])   ->middleware('permission:editar.menus')  ->name('edit');
            Route::put('/{menu}',      [CmsMenuController::class, 'update']) ->middleware('permission:editar.menus')  ->name('update');
            Route::delete('/{menu}',   [CmsMenuController::class, 'destroy'])->middleware('permission:eliminar.menus')->name('destroy');
        });

        // ── Submenús ──────────────────────────────────────────────────────
        Route::middleware('module.perms:submenus')->prefix('submenus')->name('submenus.')->group(function () {
            Route::get('/',               [CmsSubMenuController::class, 'index'])  ->middleware('permission:ver.submenus')     ->name('index');
            Route::post('/',              [CmsSubMenuController::class, 'store'])  ->middleware('permission:crear.submenus')   ->name('store');
            Route::get('/{submenu}/edit', [CmsSubMenuController::class, 'edit'])   ->middleware('permission:editar.submenus')  ->name('edit');
            Route::put('/{submenu}',      [CmsSubMenuController::class, 'update']) ->middleware('permission:editar.submenus')  ->name('update');
            Route::delete('/{submenu}',   [CmsSubMenuController::class, 'destroy'])->middleware('permission:eliminar.submenus')->name('destroy');
        });

        // ── Empresas ──────────────────────────────────────────────────────
        Route::middleware('module.perms:empresas')->prefix('companies')->name('companies.')->group(function () {
            Route::get('/',                        [CmsCompanyController::class, 'index'])       ->middleware('permission:ver.empresas')     ->name('index');
            Route::get('/create',                  [CmsCompanyController::class, 'create'])      ->middleware('permission:crear.empresas')   ->name('create');
            Route::post('/',                       [CmsCompanyController::class, 'store'])       ->middleware('permission:crear.empresas')   ->name('store');
            Route::get('/{company}/edit',          [CmsCompanyController::class, 'edit'])        ->middleware('permission:editar.empresas')  ->name('edit');
            Route::put('/{company}',               [CmsCompanyController::class, 'update'])      ->middleware('permission:editar.empresas')  ->name('update');
            Route::patch('/{company}/toggle',      [CmsCompanyController::class, 'toggleStatus'])->middleware('permission:editar.empresas')  ->name('toggle');
            Route::delete('/{company}',            [CmsCompanyController::class, 'destroy'])     ->middleware('permission:eliminar.empresas')->name('destroy');

            // Créditos
            Route::get('/{company}/credits',  [CompanyCreditController::class, 'index'])->middleware('permission:editar.empresas')->name('credits.index');
            Route::post('/{company}/credits', [CompanyCreditController::class, 'store'])->middleware('permission:editar.empresas')->name('credits.store');

            // Usuarios de la empresa
            Route::get('/{company}/users',                 [CompanyUserController::class, 'index'])       ->middleware('permission:editar.empresas')->name('users.index');
            Route::post('/{company}/users',                [CompanyUserController::class, 'store'])       ->middleware('permission:editar.empresas')->name('users.store');
            Route::patch('/{company}/users/{user}/toggle', [CompanyUserController::class, 'toggleStatus'])->middleware('permission:editar.empresas')->name('users.toggle');
        });

        // ── Roles ─────────────────────────────────────────────────────────
        Route::middleware('module.perms:roles')->prefix('roles')->name('roles.')->group(function () {
            Route::post('/permission',  [CmsRoleController::class, 'updatePermission'])->middleware('permission:editar.roles')->name('update_permission');
            Route::get('/',             [CmsRoleController::class, 'index'])  ->middleware('permission:ver.roles')     ->name('index');
            Route::get('/create',       [CmsRoleController::class, 'create']) ->middleware('permission:crear.roles')   ->name('create');
            Route::post('/',            [CmsRoleController::class, 'store'])  ->middleware('permission:crear.roles')   ->name('store');
            Route::get('/{role}/edit',  [CmsRoleController::class, 'edit'])   ->middleware('permission:editar.roles')  ->name('edit');
            Route::put('/{role}',       [CmsRoleController::class, 'update']) ->middleware('permission:editar.roles')  ->name('update');
            Route::delete('/{role}',    [CmsRoleController::class, 'destroy'])->middleware('permission:eliminar.roles')->name('destroy');
        });

        // ── Configuración del sistema ─────────────────────────────────────
        Route::middleware('permission:editar.configuracion')->prefix('settings')->name('settings.')->group(function () {
            Route::get('/',  [CmsSettingsController::class, 'index']) ->name('index');
            Route::post('/', [CmsSettingsController::class, 'update'])->name('update');
        });

    });

});
