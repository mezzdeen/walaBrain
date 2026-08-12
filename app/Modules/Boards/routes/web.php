<?php

use App\Modules\Boards\Http\Controllers\BoardController;
use App\Modules\Boards\Http\Controllers\MyWorkController;
use App\Modules\Boards\Http\Controllers\NodeController;
use App\Modules\Boards\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Boards Routes
|--------------------------------------------------------------------------
|
| Module route files are loaded outside the framework's own routing group, so
| the `web` middleware is declared here rather than inherited.
|
| `organization` throughout: work belongs to a business line, and there is
| nothing to show somebody who is not in one.
|
*/

Route::middleware(['web', 'auth:web', 'verified', 'organization'])->group(function () {
    Route::get('my-work', [MyWorkController::class, 'index'])->name('my-work.index');
    Route::post('my-work', [MyWorkController::class, 'store'])->name('my-work.store');
    Route::patch('my-work/{node}/complete', [MyWorkController::class, 'complete'])->name('my-work.complete');

    Route::get('spaces', [SpaceController::class, 'index'])->name('spaces.index');
    Route::get('boards/{board}', [BoardController::class, 'show'])->name('boards.show');
    Route::get('nodes/{node}', [NodeController::class, 'show'])->name('nodes.show');
});
