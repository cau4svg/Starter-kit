<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Prices;

class PriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prices = [
            ['name' => 'sendText', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'whatsapp/sendFile', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'whatsapp/sendFile64', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'whatsapp/sendVideo', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'whatsapp/sendImages', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'cep', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'cidades', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'geomatrix', 'value_buy' => 1.6, 'value_sell' => 2.1],
            ['name' => 'ddd', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'translate', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'database', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'cnpj', 'value_buy' => 1.5, 'value_sell' => 2],
            ['name' => 'cpf', 'value_buy' => 1.6, 'value_sell' => 2.1],
        ];

        foreach ($prices as $price) {
            Prices::firstOrCreate(
                ['name' => $price['name']],
                ['value_buy' => $price['value_buy'], 'value_sell' => $price['value_sell']]
            );
        }
    }
}

