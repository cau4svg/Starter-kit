<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    /**
     * Return list of servers from API Brasil gateway.
     */
    public function servers(Request $request)
    {
        $user = Auth::user();

        $headers = [
            'Authorization' => 'Bearer ' . $user->bearer_apibrasil,
            'Content-Type' => 'application/json',
        ];

        $deviceToken = $request->header('DeviceToken');
        if ($deviceToken) {
            $headers['DeviceToken'] = $deviceToken;
        }

        $response = Http::withHeaders($headers)
            ->get('https://gateway.apibrasil.io/api/v2/servers');

        return response()->json($response->json(), $response->status());
    }

    /**
     * Return list of APIs associated with the authenticated user.
     */
    public function apis(Request $request)
    {
        $user = Auth::user();

        $headers = [
            'Authorization' => 'Bearer ' . $user->bearer_apibrasil,
            'Content-Type' => 'application/json',
        ];

        $deviceToken = $request->header('DeviceToken');
        if ($deviceToken) {
            $headers['DeviceToken'] = $deviceToken;
        }

        $response = Http::withHeaders($headers)
            ->get('https://gateway.apibrasil.io/api/v2/apis/list');

        return response()->json($response->json(), $response->status());
    }
}