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

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard (semua role)
    |--------------------------------------------------------------------------
    */
    Route::view('dashboard', 'dashboard')->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Super Admin Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:superadmin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('manage-admins', 'admin.manage-admins')
            ->name('manage-admins');

        Route::livewire('admins/add', 'admin.admins.add-admin')
            ->name('admins.add');

        Route::livewire('admins/edit', 'admin.admins.edit-admin')
            ->name('admins.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin & Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:superadminOrAdmin')->prefix('admin')->name('admin.')->group(function () {

        // ─── Resource Management ───
        Route::livewire('manage-periods', 'admin.manage-periods')
            ->name('manage-periods');

        Route::livewire('manage-schemes', 'admin.manage-schemes')
            ->name('manage-schemes');

        Route::livewire('manage-reviewers', 'admin.manage-reviewers')
            ->name('manage-reviewers');

        Route::livewire('manage-users', 'admin.manage-users')
            ->name('manage-users');

        // ─── Internal: Researches & Dedications ───
        Route::livewire('internal/manage-researches', 'admin.internal.manage-researches')
            ->name('internal.manage-researches');

        Route::livewire('internal/manage-dedications', 'admin.internal.manage-dedications')
            ->name('internal.manage-dedications');
    });

    /*
    |--------------------------------------------------------------------------
    | Reviewer Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:reviewer')->prefix('reviewer')->name('reviewer.')->group(function () {
        Route::livewire('review-proposal', 'reviewers.review-proposal')
            ->name('review-proposal');

        Route::livewire('review-progress-report', 'reviewers.researches.review-progress-report')
            ->name('review-progress-report');
    });

    /*
    |--------------------------------------------------------------------------
    | User (Dosen) Only
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:user')->prefix('user')->name('user.')->group(function () {
        Route::livewire('internal/manage-researches', 'user.internal.manage-researches')
            ->name('internal.manage-researches');

        Route::livewire('internal/manage-dedications', 'user.internal.manage-dedications')
            ->name('internal.manage-dedications');
    });
});

require __DIR__.'/settings.php';
