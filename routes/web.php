<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\ItemController;

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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');


//Projects ---------------------------------- STARTS -------------------------------
Route::get('projects', [ProjectController::class, 'index'])
    ->name('projects')
    ->middleware('auth');

Route::get('projects/create', [ProjectController::class, 'create'])
    ->name('projects.create')
    ->middleware('auth');

Route::get('projects/{project}', [ProjectController::class, 'show'])
    ->name('projects.show')
    ->middleware('auth');

Route::post('projects', [ProjectController::class, 'store'])
    ->name('projects.store')
    ->middleware('auth');

Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])
    ->name('projects.edit')
    ->middleware('auth');

Route::put('projects/{project}', [ProjectController::class, 'update'])
    ->name('projects.update')
    ->middleware('auth');

Route::delete('projects/{project}', [ProjectController::class, 'destroy'])
    ->name('projects.destroy')
    ->middleware('auth');

Route::get('projects/projch/{id}', [ProjectController::class, 'projch'])
    ->name('projects.projch');
//Projects ---------------------------------- ENDS -------------------------------

//Excel Sheets ----------------------------------  STARTS -------------------------------

// Route::get('excel', [ProjectController::class, 'excel'])
//     ->name('excel')
//     ->middleware('auth');
Route::get('excel/{proj_id}', [ProjectController::class, 'excel'])
    ->name('projects.excel')
    ->middleware('auth');

    


//Trades ---------------------------------- ENDS -------------------------------

//Trades ---------------------------------- STARTS -------------------------------
Route::get('trades', [TradeController::class, 'index'])
    ->name('trades')
    ->middleware('auth');

Route::get('trades/create', [TradeController::class, 'create'])
    ->name('trades.create')
    ->middleware('auth');

Route::get('trades/{trade}', [TradeController::class, 'show'])
    ->name('trades.show')
    ->middleware('auth');

Route::post('trades', [TradeController::class, 'store'])
    ->name('trades.store')
    ->middleware('auth');

Route::get('trades/{trade}/edit', [TradeController::class, 'edit'])
    ->name('trades.edit')
    ->middleware('auth');

Route::put('trades/{trade}', [TradeController::class, 'update'])
    ->name('trades.update')
    ->middleware('auth');

Route::delete('trades/{trade}', [TradeController::class, 'destroy'])
    ->name('trades.destroy')
    ->middleware('auth');
//Trades ---------------------------------- ENDS -------------------------------


//Items ---------------------------------- STARTS -------------------------------
Route::get('items', [ItemController::class, 'index'])
    ->name('items')
    ->middleware('auth');

Route::get('actual_items', [ItemController::class, 'actual_index'])
    ->name('actual_items')
    ->middleware('auth');

Route::get('items/create', [ItemController::class, 'create'])
    ->name('items.create')
    ->middleware('auth');

Route::get('items/{item}', [ItemController::class, 'show'])
    ->name('items.show')
    ->middleware('auth');

Route::post('items', [ItemController::class, 'store'])
    ->name('items.store')
    ->middleware('auth');

Route::get('items/{item}/edit', [ItemController::class, 'edit'])
    ->name('items.edit')
    ->middleware('auth');

Route::get('items/{item}/actual', [ItemController::class, 'actual_create'])
    ->name('items.actual.create')
    ->middleware('auth');

Route::put('items/{item}', [ItemController::class, 'update'])
    ->name('items.update')
    ->middleware('auth');

Route::delete('items/{item}', [ItemController::class, 'destroy'])
    ->name('items.destroy')
    ->middleware('auth');
//Items ---------------------------------- ENDS -------------------------------
