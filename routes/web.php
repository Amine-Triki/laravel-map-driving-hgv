<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DistanceController;

Route::get('/', function () {
    return view('welcome');
});


//show distance blade
Route::get('/showdistance/{id}', [DistanceController::class, 'showDistance']);
