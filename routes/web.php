<?php

use App\Http\Controllers\OeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [OeeController::class, 'index']);

Route::get('/productions', [OeeController::class, 'getProductions']);

Route::get('/metrics', [OeeController::class, 'getMetrics']);

Route::post('/update-downtime', [OeeController::class, 'updateDowntime']);

Route::post('/toggle-machine-status', [OeeController::class, 'toggleMachineStatus']);

Route::post('/schedule-downtime', [OeeController::class, 'scheduleDowntime'])->name('schedule-downtime');

Route::post('/items', [OeeController::class, 'storeItem'])->name('items.store');

Route::get('/items', [OeeController::class, 'showItems'])->name('items.index');

Route::post('/update-reject', [OeeController::class, 'updateReject'])->name('update.reject');

Route::get('/calculate-oee', [OeeController::class, 'calculateOee']);

Route::post('/machine-start', [OeeController::class, 'machineStartStore'])->name('machine-start.store');

// Route::get('/api/oee-availability', [OeeController::class, 'calculateAvailability']);

// Route::get('/oee-performance', [OeeController::class, 'calculatePerformance']);

// Route::get('/oee-data', [OeeController::class, 'getData'])->name('oee.data');
