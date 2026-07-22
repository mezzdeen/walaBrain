<?php

use Illuminate\Support\Facades\Route;

// Everything the application itself serves. A module contributes its own
// routes from its own provider, so nothing here names one — and nothing here
// relies on a middleware alias a module registers.
Route::inertia('/', 'welcome')->name('home');
