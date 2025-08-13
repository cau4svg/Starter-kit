<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Prices;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Request as ApiRequest; // evita conflito com Illuminate\Http\Request

class RequestsController extends Controller
{

    protected $default_api;

    public function __construct()
    {
        $this->default_api = "https://gateway.apibrasil.io/api/v2/";
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
            $headers = $request->header();

            return $this->defaultRequest($urlRequest, $headers, $data);
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    public function defaultRequest(String $urlRequest, array $headers, array $data, $serviceName = null)
    {
        try {

            $user = User::findOrFail(Auth::user()->id);

            $bearerAPIBrasil = $user->bearer_apibrasil;
            $devicetoken = $user->device_token;


            // Se vier DeviceToken no header da requisição
            $requestToken = request()->header('DeviceToken');
            if (!empty($requestToken)) {
                $devicetoken = $requestToken;
            }

            // Cabeçalhos padrão
            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer {$bearerAPIBrasil}",
            ];

            // Se a URL for relacionada ao WhatsApp ou Evolution Message
            if (strpos($urlRequest, 'whatsapp') !== false || strpos($urlRequest, '/evolution/message') !== false) {
                $headers[] = 'DeviceToken: ' . $devicetoken;
            }


            // Se não vier o nome do serviço, tenta descobrir pela URL
            if (!$serviceName) {
                $names = Prices::pluck('name')->toArray(); // ["cpf", "cnpj", "consulta", "sendAudio"]

                foreach ($names as $name) {
                    if (stripos($urlRequest, $name) !== false) {
                        $serviceName = $name;
                        break;
                    }
                }
            }


            $price = Prices::where('name', $serviceName)->first();


            if (!$price) {
                return response()->json([
                    "error" => true,
                    "message" => "Serviço não encontrado na tabela de preços."
                ], 404);
            }

            // verifica saldo
            if ($user->balance < $price->value_buy) {
                return response()->json([
                    "error" => true,
                    "message" => "Saldo insuficiente para realizar esta consulta."
                ], 403);
            }

            return DB::transaction(function () use ($urlRequest, $data, $user, $price, $headers) {

                // Faz a requisição cURL
                $bearerAPIBrasil = $user->bearer_apibrasil;

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

                if (isset($callback->error) && $callback->error === true) {
                    throw new \Exception($callback->message ?? "Erro retornado pela APIBrasil");
                }

                // Salva request no banco
                ApiRequest::create([
                    'type'      => request()->method(),
                    'ip'        => request()->ip(),
                    'endpoint'  => $urlRequest,
                    'request'   => json_encode($data),
                    'response'  => json_encode($callback),
                    'status'    => 200,
                    'amount' => $price->value_sell,
                    'price_id' => $price->id, // <--- UUID
                    'user_id'   => $user->id,
                ]);

                // Debita saldo se houver price
                $transaction = $user->transaction('debit', $price->value_sell);

                return response()->json([
                    "message" => "Consulta realizada com sucesso!",
                    "taxa" => $price->value_sell,
                    "debit" => $transaction->amount,
                    "saldo_atual" => $user->balance,
                    "data" => $callback,
                ]);
            });
        } catch (\Throwable $th) {
            return response()->json([
                "error" => true,
                "message" => $th->getMessage()
            ], 500);
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
            }
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
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
