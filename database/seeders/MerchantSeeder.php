<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    /**
     * Creates a couple of sample merchants (each auto-gets a wallet
     * via the Merchant model's `created` event) so you can hit the
     * API immediately after `php artisan migrate --seed`.
     */
    public function run(): void
    {
        $merchants = [
            [
                'name' => 'Demo Store Pvt Ltd',
                'email' => 'demo-store@example.com',
                'phone' => '9999999999',
                'api_key' => 'test_api_key_demo_store_123456',
                'status' => 'active',
            ],
            [
                'name' => 'Second Merchant Co',
                'email' => 'second-merchant@example.com',
                'phone' => '8888888888',
                'api_key' => 'test_api_key_second_merchant_654321',
                'status' => 'active',
            ],
        ];

        foreach ($merchants as $data) {
            $merchant = Merchant::firstOrCreate(['email' => $data['email']], $data);
            $merchant->wallet()->update(['balance' => 10000]);
        }
    }
}
