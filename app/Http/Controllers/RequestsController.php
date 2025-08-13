<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestsController extends Controller
{
    protected $default_api;

    //uRL "padrão" da apibrasil
    public function __construct()
    {
        $this->default_api = 'https://gateway.apibrasil.io/api/v2/';
    }

    public function default(Request $request, $name)
    {
        try {
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

            // Cabeçalhos padrão
            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer {$bearerAPIBrasil}"
            ];

            // Se for WhatsApp, inclui DeviceToken
            if (strpos($urlRequest, 'whatsapp') !== false) {
                $headers[] = 'DeviceToken: ' . env('APIBRASIL_DEVICE_TOKEN');
            }

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
                CURLOPT_HTTPHEADER => $headers,
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

            $callback = json_decode($response, true);
            if (!$callback) {
                return response()->json([
                    "message" => "Erro ao decodificar resposta da APIBrasil",
                    "raw" => $response
                ], 500);
            }

            return response()->json([
                'success' => true,
                'api_response' => $callback
            ]);
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    //getTypeRequest para realizar as consultas 
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
            }
                // Se começar com 'whatsapp-'
                if (strpos($name, 'whatsapp-') === 0) {
                    // Remove o prefixo e adiciona na URL
                    $endpoint = str_replace('whatsapp-', '', $name);
                    return "{$this->default_api}whatsapp/{$endpoint}";
                }

                // Se não reconhecer, assume que já veio a URL completa
                return $name;
        }
    }

