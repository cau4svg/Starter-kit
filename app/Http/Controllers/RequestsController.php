<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Prices;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Request as ApiRequest; // evita conflito com Illuminate\Http\Request

class RequestsController extends Controller
{
    protected $default_api;

    // Construtor da classe: define a URL base da API Brasil
    public function __construct()
    {
        $this->default_api = 'https://gateway.apibrasil.io/api/v2/';
    }

    // Função "default" - chamada genérica para qualquer serviço
    // Recebe o nome do serviço ($name) e os dados da request e repassa para defaultRequest
    public function default(Request $request, $name)
    {
        try {
            $urlRequest = $this->getTypeResquest($name); // obtém a URL do serviço a partir do nome informado

            $data = $request->all();       // captura todos os dados enviados na request
            $headers = $request->header(); // captura todos os headers enviados

            // Repassa para a função central de requisições
            return $this->defaultRequest($urlRequest, $headers, $data, $name);
        } catch (\Throwable $th) {
            return response()->json(["error" => true, "message" => $th->getMessage()]);
        }
    }

    // Função central de envio de requisições para a API Brasil
    public function defaultRequest(String $urlRequest, array $headers, array $data, $serviceName = null)
    {
        try {
            // Recupera usuário autenticado
            $user = User::findOrFail(Auth::user()->id);

            $bearerAPIBrasil = $user->bearer_apibrasil; // token da API Brasil salvo no usuário
            $devicetoken = $user->device_token;         // device token salvo no usuário

            // Define headers básicos da requisição

            $headers = [
                "Content-Type: application/json",
                "Authorization: Bearer {$bearerAPIBrasil}",
            ];
            // --- capturar e normalizar o DeviceToken recebido ---
            $devicetoken = request()->header('DeviceToken');   // string|array|null

            // Se vier como array (pode acontecer), pegue o primeiro
            if (is_array($devicetoken)) {
                $devicetoken = $devicetoken[0] ?? null;
            }

            // Normaliza
            $devicetoken = $devicetoken ? trim($devicetoken) : null;

            // Se existir, adiciona no header de saída
            if ($devicetoken) {
                $headers[] = 'DeviceToken: ' . $devicetoken;
            }
            $devicetoken = $devicetoken ? trim($devicetoken) : null;


            // Se o nome do serviço não foi informado, tenta deduzir pela URL
            if (!$serviceName) {
                $names = Prices::pluck('name')->toArray();
                foreach ($names as $name) {
                    if (stripos($urlRequest, $name) !== false) {
                        $serviceName = $name;
                        break;
                    }
                }
            }

            // Normaliza para bater com o banco
            if (strpos($serviceName, 'whatsapp/') === 0) {
                $serviceName = substr($serviceName, strlen('whatsapp/'));
            }
            if (strpos($serviceName, 'evolution/message/') === 0) {
                $serviceName = substr($serviceName, strlen('evolution/message/'));
            }
            if ($serviceName === 'cep' || strpos($serviceName, 'cep/') === 0) {
                $serviceName = 'cep';
            }

            if ($serviceName === 'geomatrix' || strpos($serviceName, 'geomatrix/') === 0) {
                $serviceName = 'geomatrix';
            }

            if ($serviceName === 'translate' || strpos($serviceName, 'translate/') === 0) {
                $serviceName = 'translate';
            }
            if ($serviceName === 'ddd' || strpos($serviceName, 'ddd/') === 0) {
                $serviceName = 'ddd';
            }
            if ($serviceName === 'database' || strpos($serviceName, 'database/') === 0) {
                $serviceName = 'database';
            }
            if (strpos($serviceName, 'geolocation/') === 0) {
                $serviceName = 'geolocation/';
            }
            if (strpos($serviceName, 'weather/') === 0) {
                $serviceName = 'weather/';
            }
            // Normalização de serviços de veículos
            if (strpos($serviceName, 'vehicles/') === 0) {
                $serviceName = substr($serviceName, strlen('vehicles/'));
            }
            if (strpos($serviceName, 'vehicles.') === 0) {
                $serviceName = substr($serviceName, strlen('vehicles.'));
            }
            if ($serviceName === 'vehicles') {
                $serviceName = $data['service'] ?? $data['servicename'] ?? 'vehicles';
            }

            $serviceName = strtolower($serviceName);
            // dd($serviceName);


            $price = Prices::where('name', $serviceName)->first();



            if (!$price) {
                return response()->json([
                    "error" => true,
                    "message" => "Serviço não encontrado na tabela de preços."
                ], 404);
            }

            // Verifica se o usuário possui saldo suficiente
            if ($user->balance < $price->value_buy) {
                return response()->json([
                    "error" => true,
                    "message" => "Saldo insuficiente para realizar esta consulta."
                ], 403);
            }

            // Inicia transação no banco de dados
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

                $response = curl_exec($curl); // executa requisição
                $error = curl_error($curl);
                curl_close($curl);

                // Caso ocorra erro no cURL
                if ($error) {
                    return response()->json([
                        "message" => "Erro cURL",
                        "error" => $error
                    ], 500);
                }

                // Decodifica resposta da API
                $callback = json_decode($response, true);
                if (!is_array($callback)) {
                    return response()->json([
                        "message" => "Erro ao decodificar resposta da APIBrasil",
                        "raw" => $response
                    ], 500);
                }

                // Caso a API retorne erro direto
                if (isset($callback['error']) && $callback['error'] === true) {
                    throw new \Exception($callback['message'] ?? "Erro retornado pela APIBrasil");
                }

                // Registra a request no banco (histórico)
                ApiRequest::create([
                    'type'      => request()->method(),
                    'ip'        => request()->ip(),
                    'endpoint'  => $urlRequest,
                    'request'   => json_encode($data),
                    'response'  => json_encode($callback),
                    'status'    => 200,
                    'amount'    => $price->value_sell,
                    'price_id'  => $price->id,
                    'user_id'   => $user->id,
                ]);

                // Debita saldo do usuário
                $transaction = $user->transaction('debit', $price->value_sell);

                // Retorna resposta final para o cliente
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

    // Função para mapear o nome de um serviço ($name) para a URL correspondente da API Brasil
    public function getTypeResquest($name)
    {
        $serviceName = $name;
        $url = null;

        switch (true) {
            //APIs por Crédito----------------------------------
            case $name === 'cpf':
                $url = "{$this->default_api}dados/cpf/credits";
                break;

            case $name === 'vehicles':
                $url = "{$this->default_api}vehicles/base/001/consulta";
                break;

            case $name === 'vehicles-dados':
            case $name === 'dados':
                $url = "{$this->default_api}vehicles/base/000/dados";
                break;

            case $name === 'agregado-basica':
                $url = "{$this->default_api}vehicles/agregado/basica";
                break;

            case ($name === 'agregado-propia' || $name === 'agregado-propria'):
                $url = "{$this->default_api}vehicles/agregado/propria";
                break;

            case $name === 'renainf':
                $url = "{$this->default_api}vehicles/renainf";
                break;

            case $name === 'placa':
                $url = "{$this->default_api}vehicles/placa";
                break;

            case $name === 'roubo-furto':
                $url = "{$this->default_api}vehicles/roubo-furto";
                break;

            case $name === 'recall':
                $url = "{$this->default_api}vehicles/recall";
                break;

            case $name === 'leilao':
                $url = "{$this->default_api}vehicles/leilao";
                break;

            case $name === 'sms':
                $url = "{$this->default_api}sms/send/credits";
                break;

            case $name === 'cnpj':
                $url = "{$this->default_api}dados/cnpj/credits";
                break;
            case $name === 'translate':
                $url = "{$this->default_api}translate";
                break;

            // APIs por plano-------------------------------------
            // CEP dinâmico
            case strpos($name, 'cep/') === 0:
                $endpoint = str_replace('cep/', '', $name);
                return "{$this->default_api}cep/{$endpoint}";

            case $name === 'rastreio':
                $url = "{$this->default_api}correios/rastreio";
                break;

            case $name === 'translate':
                $url = "{$this->default_api}translate";
                break;

            case $name === 'fipe':
                $url = "{$this->default_api}vehicles/fipe";
                break;

            // Serviços com prefixo dinâmico (weather, whatsapp, geolocation)
            case strpos($name, 'weather/') === 0:
                $endpoint = str_replace('weather/', '', $name);
                return "{$this->default_api}weather/{$endpoint}";

            case strpos($name, 'whatsapp/') === 0:
                $endpoint = str_replace('whatsapp/', '', $name);
                return "{$this->default_api}whatsapp/{$endpoint}";

            case strpos($name, 'geolocation/') === 0:
                $endpoint = substr($name, strlen('geolocation/'));
                return "{$this->default_api}geolocation/{$endpoint}";

            case strpos($name, 'geomatrix/') === 0:
                $endpoint = str_replace('geomatrix/', '', $name);
                return "{$this->default_api}geomatrix/{$endpoint}";

            case strpos($name, 'translate/') === 0:
                $endpoint = str_replace('translate/', '', $name);
                return "{$this->default_api}translate/{$endpoint}";

            case strpos($name, 'ddd/') === 0:
                $endpoint = str_replace('ddd/', '', $name);
                return "{$this->default_api}ddd/{$endpoint}";

            case strpos($name, 'database/') === 0:
                $endpoint = str_replace('database/', '', $name);
                return "{$this->default_api}database/{$endpoint}";

            default:
                throw new \Exception("Serviço '{$name}' não reconhecido em getTypeResquest");
        }

        return $url;
    }

    // Serviço específico: FIPE (veículos)
    public function placaFipe(Request $request)
    {
        return $this->defaultRequest(
            'https://gateway.apibrasil.io/api/v2/vehicles/fipe',
            [],
            $request->all(),
            'vehicles.fipe'
        );
    }

}
