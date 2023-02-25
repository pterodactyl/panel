<?php

namespace Database\Seeders\Seeds;

use App\Models\ShopProduct;
use Illuminate\Database\Seeder;

class ShopProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ShopProduct::create([
            'type' => 'Credits',
            'display' => '350',
            'description' => 'Adds 350 credits to your account',
            'quantity' => '350',
            'currency_code' => 'EUR',
            'price' => 2.00,
            'disabled' => false,
        ]);

        ShopProduct::create([
            'type' => 'Credits',
            'display' => '875 + 125',
            'description' => 'Adds 1000 credits to your account',
            'quantity' => '1000',
            'currency_code' => 'EUR',
            'price' => 5.00,
            'disabled' => false,
        ]);

        ShopProduct::create([
            'type' => 'Credits',
            'display' => '1750 + 250',
            'description' => 'Adds 2000 credits to your account',
            'quantity' => '2000',
            'currency_code' => 'EUR',
            'price' => 10.00,
            'disabled' => false,
        ]);

        ShopProduct::create([
            'type' => 'Credits',
            'display' => '3500 + 500',
            'description' => 'Adds 4000 credits to your account',
            'quantity' => '4000',
            'currency_code' => 'EUR',
            'price' => 20.00,
            'disabled' => false,
        ]);

        ShopProduct::create([
            'type' => 'Server slots',
            'display' => '+2 Server slots',
            'description' => 'You will be able to create 2 more servers',
            'quantity' => '2',
            'currency_code' => 'EUR',
            'price' => 5.00,
            'disabled' => false,
        ]);
    }
}
