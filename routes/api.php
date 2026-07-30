<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Offline sync endpoints (called by school/office installs)
|--------------------------------------------------------------------------
*/
Route::middleware('sync.token')->prefix('sync')->group(function () {
    Route::post('push', [SyncController::class, 'push'])->name('api.sync.push');
    Route::get('pull', [SyncController::class, 'pull'])->name('api.sync.pull');
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
