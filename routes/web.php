<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// `organization`: the dashboard is the tenant home and the page every switch
// lands on, so it has nothing to show a user who belongs to no organization.
Route::middleware(['auth:web', 'verified', 'organization'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});
