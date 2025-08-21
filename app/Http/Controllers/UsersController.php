<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return User::all();
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
        try {

            $user = User::findOrFail($id);

            $authUser = $request->user();

            if (! $authUser->is_admin && $authUser->id !== $user->id) {
                return response()->json([
                    'message' => 'Você só pode atualizar seu próprio perfil',
                ], 403);
            }

            if ($request->has('is_admin') && ! $authUser->is_admin) {
                return response()->json(['error' => 'Apenas administradores podem alterar o campo is_admin'], 403);
            }

            $data = $request->only(['name', 'email', 'cellphone', 'password', 'bearer_apibrasil']);

            // Se veio senha, criptografa
            if (! empty($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);
            $user->refresh();

            return response()->json([
                'message' => 'Usuário atualizado com sucesso',
                'user' => $user->makeHidden(['password']),
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error ao  atualizar úsuario',
                'error' => $th->getMessage(),
            ]);
        }
    }

    public function makeAdmin(Request $request, string $id)
    {
        if (! auth()->user()->is_admin) {
            return response()->json([
                'message' => 'Apenas administradores podem realizar essa ação',
            ], 403);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        if ($user->is_admin) {
            return response()->json([
                'message' => 'Usuário já é administrador',
            ], 400);
        }

        $user->is_admin = true;
        $user->save();
        $user->refresh();

        return response()->json([
            'message' => 'Usuário promovido a administrador com sucesso',
            'user' => $user->makeHidden(['password']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (! auth()->user()->is_admin) {
            return response()->json([
                'message' => 'Apenas administradores podem realizar essa ação',
            ], 403);
        }

        if (auth()->id() === $id) {
            return response()->json([
                'message' => 'Administradores não podem deletar a si mesmos',
            ], 400);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuário deletado com sucesso',
        ]);
    }
}
