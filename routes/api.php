<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PricesController;
use App\Http\Controllers\RequestsController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// rota de autenticação simples
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

// rotas protegidas por sanctum
Route::middleware('auth:sanctum')->group(function () {

    // informações do usuário logado
    Route::get('/user', fn(Request $request) => $request->user());
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // atualizar usuário
    Route::put('/users/{id}', [UsersController::class, 'update']);
    Route::post('/users/{id}/make-admin', [UsersController::class, 'makeAdmin']);

    // transações e saldo
    Route::get('/transactions', [TransactionsController::class, 'index']);
    Route::post('/add-balance', [TransactionsController::class, 'addBalance']);
    Route::post('/users/{id}/add-balance', [TransactionsController::class, 'addBalanceToUser']);

    // preços - acesso restrito a administradores
    Route::middleware('admin')->apiResource('prices', PricesController::class);

    // consultas padronizadas → RequestsController::default
    Route::controller(RequestsController::class)->group(function () {
        Route::post('/vehicles/fipe', 'placaFipe');

        Route::any('/consult/{name}', 'default')->name('request_default');

        Route::prefix('whatsapp')->group(function () {
            Route::post('{action}', fn(Request $req, $action) => app(RequestsController::class)->default($req, "whatsapp/$action"));
        });

        Route::post('/correios/{name}', 'default');

        Route::prefix('geolocation')->group(function () {
            Route::post('{action}', fn(Request $req, $action) => app(RequestsController::class)->default($req, "geolocation/$action"));
        });

        Route::prefix('weather')->group(function () {
            Route::post('{action}', fn(Request $req, $action) => app(RequestsController::class)->default($req, "weather/$action"));
        });

        Route::any(
            '/cep/{action?}',
            fn(Request $req, $action = null) => app(RequestsController::class)->default($req, $action ? 'cep/' . trim($action, '/') : 'cep')
        )->where('action', '.*');

        Route::post('/geomatrix', fn(Request $req) => app(RequestsController::class)->default($req, 'geomatrix/distance'));

        Route::any(
            '/translate/{action?}',
            fn(Request $req, $action = null) => app(RequestsController::class)->default($req, $action ? 'translate/' . trim($action, '/') : 'translate')
        )->where('action', '.*');

        Route::any(
            '/ddd/{action?}',
            fn(Request $req, $action = null) => app(RequestsController::class)->default($req, $action ? 'ddd/' . trim($action, '/') : 'ddd')
        )->where('action', '.*');

        Route::any(
            '/database/{action?}',
            fn(Request $req, $action = null) => app(RequestsController::class)->default($req, $action ? 'database/' . trim($action, '/') : 'database')
        )->where('action', '.*');
    });
});
