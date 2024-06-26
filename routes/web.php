<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DistanceController;

Route::get('/', function () {
    return view('welcome');
});


//show distance blade
Route::get('/showdistance/{id}', [DistanceController::class, 'showDistance']);
Route::get('/showMap', [DistanceController::class, 'showMap']);


Route::get("/getDistance/{id}", [DistanceController::class, "GetDistance"]);
