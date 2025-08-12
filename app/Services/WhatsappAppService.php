<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;

class WhatsAppService
{
    protected $client;
    protected $apiUrl = 'https://gateway.apibrasil.io/api/v2/whatsapp/sendText';

    public function __construct()
    {
        $this->client = new Client();
    }

    public function sendText($userId, $mensagem)
    {
        $user = User::findOrFail($userId);

        if (!$user->bearer_apibrasil) {
            throw new \Exception("Usuário não possui token configurado.");
        }

        $response = $this->client->post($this->apiUrl, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => "Bearer {$user->bearer_apibrasil}",
            ],
            'json' => [
                'number' => $user->cellphone,
                'text'   => $mensagem
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
