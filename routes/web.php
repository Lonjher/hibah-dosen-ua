<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('admin.manage-periods', 'admin/manage-periods')->name('admin.manage-periods');
    Route::livewire('admin.manage-schemes', 'admin/manage-schemes')->name('admin.manage-schemes');
    Route::livewire('admin.manage-admins', 'admin/manage-admins')->name('admin.manage-admins');
    Route::livewire('admin.manage-reviewers', 'admin/manage-reviewers')->name('admin.manage-reviewers');
    Route::livewire('admin.manage-users', 'admin/manage-users')->name('admin.manage-users');
});

require __DIR__ . '/settings.php';
