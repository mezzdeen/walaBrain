<?php

use App\Modules\Flows\Http\Controllers\ApprovalController;
use App\Modules\Flows\Http\Controllers\ResubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Flows Routes
|--------------------------------------------------------------------------
|
| Deciding an approval, and sending a revised request back. Both land on
| screens reached from My Work or an email deep link.
|
*/

Route::middleware(['web', 'auth:web', 'verified', 'organization'])->group(function () {
    Route::get('approvals/{approval}', [ApprovalController::class, 'show'])->name('approvals.show');
    Route::post('approvals/{approval}', [ApprovalController::class, 'store'])->name('approvals.store');

    Route::post('nodes/{node}/resubmit', [ResubmissionController::class, 'store'])->name('nodes.resubmit');
});
