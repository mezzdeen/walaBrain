<?php

use App\Modules\Forms\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Forms Routes
|--------------------------------------------------------------------------
|
| Intake. Any member of the business line may open a published form —
| access to the board behind it is a separate question the form
| deliberately does not ask.
|
*/

Route::middleware(['web', 'auth:web', 'verified', 'organization'])->group(function () {
    Route::get('forms/{form}', [FormController::class, 'show'])->name('forms.show');
    Route::post('forms/{form}', [FormController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('forms.store');
});
