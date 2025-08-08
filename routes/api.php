<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


//ISSO AQUI É O ORIGINAL O DE CIMA EU Q FIZ
//Route::get('/user', function (Request $request) {
  //  return $request->user();
//})->middleware('auth:sanctum');



?>