<?php

namespace App\Http\Controllers;

use index;
use App\Models\Transactions;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = Transactions::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10); // 10 itens por página

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

    public function addBalance(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $request->user();

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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
