<?php

namespace App\Http\Controllers;

use App\Models\Prices;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PricesController extends Controller
{
    // Lista todos os registros de preços, com suporte a paginação e busca por nome
    public function index(Request $request)
    {
        $query = Prices::query();

        // Se o parâmetro "search" for enviado, aplica filtro pelo campo "name"
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%"); // busca parcial e case-insensitive
        }

        // Define quantos registros por página (default = 10)
        $perPage = $request->get('per_page', 10);
        $prices = $query->paginate($perPage);

        // Retorna lista paginada em JSON
        return response()->json($prices);
    }

    // Cria um novo registro de preço
    public function store(Request $request)
    {
        // Valida dados obrigatórios
        $validated = $request->validate([
            'name'        => 'required|string|max:255', // nome do serviço
            'value_buy'   => 'required|numeric|min:0',  // valor de compra
            'value_sell'  => 'required|numeric|min:0',  // valor de venda
        ]);

        // Cria o registro no banco
        $price = Prices::create($validated);

        // Retorna resposta JSON com status 201 (Created)
        return response()->json($price, 201);
    }

    // Exibe os detalhes de um preço específico
    public function show($id)
    {
        $price = Prices::findOrFail($id); // retorna 404 caso não encontre
        return response()->json($price);
    }

    // Atualiza um preço existente
    public function update(Request $request, $id)
    {
        $price = Prices::findOrFail($id);

        // Valida apenas os campos enviados (não obrigatórios)
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'value_buy'   => 'sometimes|numeric|min:0',
            'value_sell'  => 'sometimes|numeric|min:0',
        ]);

        // Atualiza registro no banco
        $price->update($validated);

        // Retorna objeto atualizado
        return response()->json($price);
    }

    // Remove um registro de preço
    public function destroy($id)
    {
        $price = Prices::findOrFail($id);
        $price->delete();

        return response()->json(['message' => 'Registro deletado com sucesso']);
    }
}
