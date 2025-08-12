<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestsController extends Controller
{
    protected $default_api;

    public function __construct()
    {
        $this->default_api = 'https://gateway.apibrasil.io/api/v2/';
    }

    public function default(Request $request, $name)
    {
        try {
            // Se for WhatsApp, chama direto o sendText()
            if ($name === 'whatsapp') {
                return $this->sendText($request);
            }

            $urlRequest = $this->getTypeResquest($name);
            $data = $request->all();

            return $this->defaultRequest($urlRequest, $data);
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    public function defaultRequest(String $urlRequest, array $data)
    {
        try {
            $bearerAPIBrasil = Auth::user()->bearer_apibrasil ?? env('APIBRASIL_BEARER');

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $urlRequest,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer {$bearerAPIBrasil}"
                ],
                CURLOPT_POSTFIELDS => json_encode($data)
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                return response()->json([
                    "message" => "Erro cURL",
                    "error" => $error
                ], 500);
            }

            $callback = json_decode($response);
            if (!$callback) {
                return response()->json([
                    "message" => "Erro ao decodificar resposta da APIBrasil",
                    "raw" => $response
                ], 500);
            }

            return response()->json($callback);
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    public function getTypeResquest($name)
    {
        switch ($name) {
            case 'cpf':
                return "{$this->default_api}dados/cpf/credits";
            case 'vehicles':
                return "{$this->default_api}vehicles/base/001/consulta";
            case 'vehicles-dados':
                return "{$this->default_api}vehicles/base/000/dados";
            case 'sms':
                return "{$this->default_api}sms/send/credits";
            case 'cnpj':
                return "{$this->default_api}dados/cnpj/credits";
            case 'whatsapp':
                return "{$this->default_api}whatsapp/sendText";
        }
    }

    public function sendText(Request $request)
    {
        $bearerAPIBrasil = Auth::user()->bearer_apibrasil ?? env('APIBRASIL_BEARER');
        
        $payload = json_encode([
            'number' => $request->input('number'),
            'text' => $request->input('text'),
            'time_typing' => $request->input('time_typing', 1000)
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->default_api . 'whatsapp/sendText',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'DeviceToken: ' . env('APIBRASIL_DEVICE_TOKEN'),
                "Content-Type: application/json",
                "Authorization: Bearer {$bearerAPIBrasil}",
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return response()->json([
                'success' => false,
                'error' => $error
            ], 500);
        }

        return response()->json([
            'success' => true,
            'api_response' => json_decode($response, true)
        ]);
    }
}
