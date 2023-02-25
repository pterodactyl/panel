<?php

namespace Database\Seeders\Seeds;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::create([
            'name' => 'Starter',
            'description' => '64MB Ram, 1GB Disk, 1 Database, 140 credits hourly',
            'price' => 140,
            'memory' => 64,
            'disk' => 1000,
            'databases' => 1,
            'billing_period' => 'hourly'
        ]);

        Product::create([
            'name' => 'Standard',
            'description' => '128MB Ram, 2GB Disk, 2 Database,  210 credits hourly',
            'price' => 210,
            'memory' => 128,
            'disk' => 2000,
            'databases' => 2,
            'billing_period' => 'hourly'
        ]);

        Product::create([
            'name' => 'Advanced',
            'description' => '256MB Ram, 5GB Disk, 5 Database,  280 credits hourly',
            'price' => 280,
            'memory' => 256,
            'disk' => 5000,
            'databases' => 5,
            'billing_period' => 'hourly'
        ]);
    }
}
