<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// Master Data Controllers
use App\Http\Controllers\Master\DepartmentController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Master\KpiTemplateController;
use App\Http\Controllers\Master\KpiMonthlyTargetController;
use App\Http\Controllers\Master\CapaAreaController;

// Reporting Controllers
use App\Http\Controllers\KpiEntryController;
use App\Http\Controllers\CapaController;
use App\Http\Controllers\CapaProblemController;
use App\Http\Controllers\CapaCauseController;
use App\Http\Controllers\CapaActionPlanController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', 'login');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data Routes
    Route::prefix('master')->name('master.')->group(function () {
        // Department Management
        Route::resource('departments', DepartmentController::class);
        
        // User Management
        Route::resource('users', UserController::class);
        
        // KPI Template Management
        Route::resource('kpi-templates', KpiTemplateController::class);
        
        // KPI Monthly Targets
        Route::get('kpi-monthly-targets/get-target', [KpiMonthlyTargetController::class, 'getTarget'])
            ->name('kpi-monthly-targets.get-target');
        Route::resource('kpi-monthly-targets', KpiMonthlyTargetController::class);
        
        // CAPA Area Management
        Route::resource('capa-areas', CapaAreaController::class);
    });

    // KPI Entry Routes
    Route::prefix('kpi')->name('kpi.')->group(function () {
        Route::resource('entries', KpiEntryController::class);
    });

    // CAPA Routes
    Route::prefix('capa')->name('capa.')->group(function () {
        // Comprehensive CAPA Creation (all-in-one form)
        Route::get('create-comprehensive', [CapaController::class, 'create'])->name('create-comprehensive');
        Route::post('store-comprehensive', [CapaController::class, 'store'])->name('store-comprehensive');
        
        // CAPA Problems
        Route::resource('problems', CapaProblemController::class);
        
        // CAPA Causes
        Route::resource('causes', CapaCauseController::class)->except(['index']);
        
        // CAPA Action Plans
        Route::resource('action-plans', CapaActionPlanController::class)->except(['index']);
    });

    Route::fallback(function() {
        return view('pages/utility/404');
    });    
});
