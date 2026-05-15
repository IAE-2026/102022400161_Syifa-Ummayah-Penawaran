<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\BidController; 

Route::prefix('v1')->middleware('api.key')->group(function () {
    Route::get('/bids', [BidController::class, 'index']);
    Route::get('/bids/{id}', [BidController::class, 'show']);
    Route::post('/bids', [BidController::class, 'store']);
});