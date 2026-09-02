<?php

use App\Http\Controllers\Analytics\DatasetCatalogController;
use App\Http\Controllers\Analytics\ReportBuilderController;
use App\Http\Controllers\Analytics\ReportPreviewController;
use App\Http\Controllers\Analytics\SavedReportController;
use App\Http\Controllers\Analytics\SavedReportStoreController;
use App\Http\Controllers\Analytics\SavedReportUpdateController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware(['auth', 'active.employee'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/branches', [BranchController::class, 'index'])
        ->name('branches.index');
    Route::get('/branches/{branch}', [BranchController::class, 'show'])
        ->whereNumber('branch')
        ->name('branches.show');

    Route::get('/profile', [EmployeeController::class, 'profile'])
        ->name('profile');
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->name('employees.index');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
        ->whereNumber('employee')
        ->name('employees.show');

    Route::prefix('analytics')
        ->name('analytics.')
        ->group(function (): void {
            Route::get('/report-builder/{savedReport?}', ReportBuilderController::class)
                ->whereNumber('savedReport')
                ->name('report-builder');
            Route::post('/report-preview', ReportPreviewController::class)
                ->name('report-preview');

            Route::get('/saved-reports', [SavedReportController::class, 'index'])
                ->name('saved-reports.index');
            Route::post('/saved-reports', SavedReportStoreController::class)
                ->name('saved-reports.store');
            Route::put('/saved-reports/{savedReport}', SavedReportUpdateController::class)
                ->whereNumber('savedReport')
                ->name('saved-reports.update');

            Route::get('/datasets', [DatasetCatalogController::class, 'index'])
                ->name('datasets.index');
            Route::get('/datasets/{dataset}', [DatasetCatalogController::class, 'show'])
                ->where('dataset', '[a-z_]+')
                ->name('datasets.show');
        });
});
