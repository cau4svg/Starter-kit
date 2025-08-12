<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index() {}

    public function login(Request $request)

    {
        try {
            $credentials = $request->validate([

                "email" => "required|email",

                "password" => "required"
            ]);

            $user = User::where("email", $credentials["email"])->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {

                return response()->json(['message' => 'Credenciais inválidas'], 401);
            }

            $token = $user->createToken('api')->plainTextToken;

            // lógica de autenticação aqui

            return response()->json(["error" => false, "user" => $user, "token" => $token]);
        } catch (\Throwable $th) {

            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        // Apaga o token atual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não autenticado'
            ], 401);
        }

        return response()->json([
            'name'       => $user->name,
            'email'      => $user->email,
            'cellphone'  => $user->cellphone,
            'balance'    => $user->balance,
        ]);
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
