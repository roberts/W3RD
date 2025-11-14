<?php

namespace Database\Seeders;

use App\Models\Access\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Web Application',
                'api_key' => Str::random(64),
                'platform' => 'web',
                'is_active' => true,
            ],
            [
                'name' => 'iOS Application',
                'api_key' => Str::random(64),
                'platform' => 'ios',
                'is_active' => true,
            ],
            [
                'name' => 'Android Application',
                'api_key' => Str::random(64),
                'platform' => 'android',
                'is_active' => true,
            ],
        ];

        foreach ($clients as $client) {
            Client::updateOrCreate(
                ['name' => $client['name']],
                $client
            );
        }
    }
}
