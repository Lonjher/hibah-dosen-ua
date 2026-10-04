<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'must.verify.email'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::middleware('can:superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('manage-admins', 'admin.manage-admins')
            ->name('manage-admins');

        Route::livewire('admins/add', 'admin.admins.add-admin')
            ->name('admins.add');

        Route::livewire('admins/edit', 'admin.admins.edit-admin')
            ->name('admins.edit');
    });
    Route::middleware('can:superadminOrAdmin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('manage-periods', 'admin.manage-periods')
            ->name('manage-periods');
        Route::livewire('manage-schemes', 'admin.manage-schemes')
            ->name('manage-schemes');
        Route::livewire('manage-reviewers', 'admin.manage-reviewers')
            ->name('manage-reviewers');
        Route::livewire('manage-users', 'admin.manage-users')
            ->name('manage-users');
        // Internal Data
        Route::livewire('internal/manage-researches', 'admin.internal.manage-researches')
            ->name('internal.manage-researches');
        Route::livewire('internal/manage-dedications', 'admin.internal.manage-dedications')
            ->name('internal.manage-dedications');
        // External Data
        Route::livewire('external/manage-researches', 'admin.external.manage-researches')
            ->name('external.manage-researches');
        Route::livewire('external/manage-dedications', 'admin.external.manage-dedications')
            ->name('external.manage-dedications');
        // Information and Download
        Route::livewire('information/manage-information', 'admin.manage-information')
            ->name('manage-information');
        Route::livewire('download/manage-download', 'admin.manage-download')
            ->name('manage-download');
    });
    Route::middleware('can:reviewer')->prefix('reviewer')->name('reviewer.')->group(function () {
        Route::livewire('review-proposal', 'reviewers.review-proposal')
            ->name('review-proposal');

        Route::livewire('review-progress-report', 'reviewers.researches.review-progress-report')
            ->name('review-progress-report');
    });
    Route::middleware('can:user')->prefix('user')->name('user.')->group(function () {
        // Internal Data
        Route::livewire('internal/manage-researches', 'user.internal.manage-researches')
            ->name('internal.manage-researches');
        Route::livewire('internal/manage-community-service', 'user.internal.manage-dedications')
            ->name('internal.manage-dedications');
        // External Data
        Route::livewire('external/manage-researches', 'user.external.manage-researches')
            ->name('external.manage-researches');
        Route::livewire('external/manage-community-service', 'user.external.manage-dedications')
            ->name('external.manage-dedications');
    });
});

require __DIR__.'/settings.php';
