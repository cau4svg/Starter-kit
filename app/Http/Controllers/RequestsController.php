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
            $headers = $request->header();

            // Passa $name diretamente como serviceName
            return $this->defaultRequest($urlRequest, $headers, $data, $name);
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
            if (strpos($urlRequest, 'whatsapp/') !== false || strpos($urlRequest, '/evolution/message') !== false) {
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
                // dd($price->value_sell);
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
        $serviceName = $name;
        $url = null;

        switch (true) {
            case $name === 'cpf':
                $url = "{$this->default_api}dados/cpf/credits";
                break;

            case $name === 'vehicles':
                $url = "{$this->default_api}vehicles/base/001/consulta";
                break;

            case $name === 'vehicles-dados':
                $url = "{$this->default_api}vehicles/base/000/dados";
                break;

            case $name === 'sms':
                $url = "{$this->default_api}sms/send/credits";
                break;

            case $name === 'cnpj':
                $url = "{$this->default_api}dados/cnpj/credits";
                break;

            case strpos($name, 'whatsapp/') === 0:
                $endpoint = str_replace('whatsapp/', '', $name);
                return "{$this->default_api}whatsapp/{$endpoint}";

            default:
                // Aqui você evita retornar null
                throw new \Exception("Serviço '{$name}' não reconhecido em getTypeResquest");
        }

        // Normaliza para bater com o banco
        if (strpos($serviceName, 'whatsapp/') === 0) {
            $serviceName = substr($serviceName, strlen('whatsapp/'));
        }
        if (strpos($serviceName, 'evolution/message/') === 0) {
            $serviceName = substr($serviceName, strlen('evolution/message/'));
        }

        $price = Prices::where('name', $serviceName)->first();

        return $url;
    }
}
