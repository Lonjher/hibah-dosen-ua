<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::middleware('can:superadminOrAdmin')->group(function(){
        Route::livewire('admin/manage-periods', 'admin.manage-periods')->name('admin.manage-periods');
        Route::livewire('admin/manage-schemes', 'admin.manage-schemes')->name('admin.manage-schemes');
        Route::livewire('admin/manage-admins', 'admin.manage-admins')->name('admin.manage-admins');
        Route::livewire('admin/manage-reviewers', 'admin.manage-reviewers')->name('admin.manage-reviewers');
        Route::livewire('admin/manage-users', 'admin.manage-users')->name('admin.manage-users');
        Route::livewire('admin/internal/manage-researches', 'admin.internal.manage-researches')->name('admin.internal.manage-researches');
    });

    Route::middleware('can:reviewer')->group(function(){
        Route::livewire('reviewer/review-proposal', 'reviewers.review-proposal')->name('reviewer.review-proposal');
        Route::livewire('reviewer/review-progress-report', 'reviewers.researches.review-progress-report')->name('reviewer.review-progress-report');
    });

    Route::middleware('can:user')->group(function(){
        Route::livewire('user/internal/manage-researches', 'user.internal.manage-researches')->name('user.internal.manage-researches');
        Route::livewire('user/internal/manage-dedications', 'user.internal.manage-dedications')->name('user.internal.manage-dedications');
    });

});

require __DIR__.'/settings.php';
