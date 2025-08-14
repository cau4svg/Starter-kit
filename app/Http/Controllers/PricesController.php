<?php

namespace App\Http\Controllers;

use App\Models\Prices;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PricesController extends Controller
{
    // Lista todos os preços com paginação e busca
    public function index(Request $request)
    {
        $query = Prices::query();

        // Filtro por nome (case insensitive)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        // Paginação (default 10 por página)
        $perPage = $request->get('per_page', 10);
        $prices = $query->paginate($perPage);

        return response()->json($prices);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'value_buy'   => 'required|numeric|min:0',
            'value_sell'  => 'required|numeric|min:0',
        ]);

        $price = Prices::create($validated);
        return response()->json($price, 201);
    }

    public function show($id)
    {
        $price = Prices::findOrFail($id);
        return response()->json($price);
    }

    public function update(Request $request, $id)
    {
        $price = Prices::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'value_buy'   => 'sometimes|numeric|min:0',
            'value_sell'  => 'sometimes|numeric|min:0',
        ]);

        $price->update($validated);
        return response()->json($price);
    }

    public function destroy($id)
    {
        $price = Prices::findOrFail($id);
        $price->delete();

        return response()->json(['message' => 'Registro deletado com sucesso']);
    }


}
