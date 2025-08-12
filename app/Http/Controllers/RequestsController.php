<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RequestsController extends Controller
{   
    protected $whatsAppService;
    protected $default_api;

    public function __construct()
    {
        $this->default_api = "https://gateway.apibrasil.io/api/v2/";
    }

    public function default(Request $request, $name)
    {
        try {

            $urlRequest = $this->getTypeResquest($name);

            $data = $request->all();
            $response = $this->defaultRequest($urlRequest, $data);

            return $response;
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    public function defaultRequest(String $urlRequest, array $data)
    {
        try {

            $bearerAPIBrasil = Auth::user()->bearer_apibrasil;

            // Usando cURL para fazer a requisição
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

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // garante que o retorno seja string

            $response = curl_exec($curl);
            curl_close($curl);

            // Se deu erro no cURL
            $callback = json_decode($response);

            // Se não conseguir decodificar
            if (!$callback) {
                return response()->json([
                    "message" => "Erro ao decodificar resposta da APIBrasil",
                    "raw" => $response
                ], 500);
            }

            // Verifica se a APIBrasil retornou erro
            if (!isset($callback->error) || $callback->error === true) {
                return response()->json([
                    "message" => $callback->message ?? "Erro retornado pela APIBrasil",
                    "data" => $callback
                ], 400);
            }

            // Se deu tudo certo, retorna os dados
            return response()->json([
                "message" => "Consulta realizada com sucesso!",
                "data" => $callback
            ]);

            // Decodifica o JSON
            $callback = json_decode($response);

            if (!$callback || !isset($callback->response)) {
                return response()->json([
                    "message" => "Erro ao obter resposta da APIBrasil",
                    "data" => $response
                ], 500);
            }

            return response()->json($callback);
            return response()->json([
                "message"  => "Consulta realizada com sucesso!",
                "response" => json_decode($callback->response)
            ]);
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    public function getTypeResquest($name)
    {
        try {

            switch ($name) {
                case 'cpf': //case para consulta de cpf
                    return "{$this->default_api}/dados/cpf/credits";
                case 'vehicles': // Novo case para consulta base 001
                    return "{$this->default_api}vehicles/base/001/consulta";
                case 'vehicles-dados': // Novo case para consulta base 000
                    return "{$this->default_api}vehicles/base/000/dados";
                case 'sms': // Novo case para SMS
                    return "{$this->default_api}sms/send/credits";
                case 'cnpj': // Novo case para CNPJ
                    return "{$this->default_api}dados/cnpj/credits";
                case 'whatsapp': //case whatspp consulta send text
                    return "{$this->default_api}whatsapp/sendText";
            }
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'mensagem' => 'required|string'
        ]);

        $resultado = $this->whatsAppService->sendText(
            $request->input('user_id'),
            $request->input('mensagem')
        );

        return response()->json($resultado);
    }
}

