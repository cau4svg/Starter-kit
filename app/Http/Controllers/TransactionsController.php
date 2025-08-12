<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionsController extends Controller
{

    public function addBalance(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $request->user();

        //if Auth::user()->is_admin != true return somente admin pode realizar uma recarga;
        if(!Auth::user()->is_admin){
            return response()->json([
                'message' => "Somente administradores podem realizar recarga",
            ]);
        }

        // Atualiza o saldo
        $user->balance += $request->amount;
        $user->save();

        // Registra a transação
        Transactions::create([
            'amount' => $request->amount,
            'type'   => 'credit', // pode mudar para 'debit' se for retirada
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Saldo adicionado com sucesso',
            'balance' => $user->balance
        ]);
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = Transactions::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($transactions);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
