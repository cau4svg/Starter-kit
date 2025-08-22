<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DevicesController extends Controller
{
    protected string $baseUrl = 'https://gateway.apibrasil.io/api/v2';

    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->bearer_apibrasil) {
            return response()->json([
                'error' => true,
                'message' => 'Token API Brasil não configurado',
            ], 400);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $user->bearer_apibrasil,
        ])->get("{$this->baseUrl}/devices");

        return response()->json($response->json(), $response->status());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $secretKey = request()->header('SecretKey'); // string|array|null

        // Se vier como array (pode acontecer), pegue o primeiro
        if (is_array($secretKey)) {
            $secretKey = $secretKey[0] ?? null;
        }
        // Normaliza
        $secretKey = $secretKey ? trim($secretKey) : null;

        // Se existir, adiciona no header de saída
        if ($secretKey) {
            $headers[] = 'SecretKey: ' . $secretKey;
        }

        if (!$user || !$user->bearer_apibrasil) {
            return response()->json([
                'error' => true,
                'message' => 'Token API Brasil não configurado',
            ], 400);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $user->bearer_apibrasil,
            'SecretKey' => $secretKey,
        ])->post("{$this->baseUrl}/devices/store", $request->all());

        return response()->json($response->json(), $response->status());
    }

    public function search(Request $request, string $device)
    {
        $user = Auth::user();
        $secretKey = request()->header('SecretKey'); // string|array|null

        if (is_array($secretKey)) {
            $secretKey = $secretKey[0] ?? null;
        }
        $secretKey = $secretKey ? trim($secretKey) : null;

        if ($secretKey) {
            $headers[] = 'SecretKey: ' . $secretKey;
        }

        if (!$user || !$user->bearer_apibrasil) {
            return response()->json([
                'error' => true,
                'message' => 'Token API Brasil não configurado',
            ], 400);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $user->bearer_apibrasil,
            'SecretKey' => $secretKey,
        ])->post("{$this->baseUrl}/devices/{$device}/search", $request->all());

        return response()->json($response->json(), $response->status());
    }
}
