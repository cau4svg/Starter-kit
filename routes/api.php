<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\TransactionsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//rota de login, que puxa usuario pelo id
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware('auth:sanctum')->put('/users/{id}', [UsersController::class, 'update']);

//agrupamento de middleware que so deixa usuario realizar ações após o login
Route::middleware('auth:sanctum')->group(function () {
    //rotas de autenticação
    Route::middleware('auth:sanctum')->get('/transactions', [TransactionsController::class, 'index']);
    Route::post('/add-balance', [TransactionsController::class, 'addBalance']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    //rotas de consultas
    Route::any('/consult/{name}', [RequestsController::class, 'default'])->name('request_default');

    //Whatsapp Wpp
    Route::post('/whatsapp/{action}', function (Request $request, $action) {

        $name = 'whatsapp-' . $action;
        return app(RequestsController::class)->default($request, $name);
    });

    Route::post('/evolution/{action}', function (Request $request, $action) {
        $name = 'evolution-' . $action;
        return app(RequestsController::class)->default($request, $name);
    });
});
