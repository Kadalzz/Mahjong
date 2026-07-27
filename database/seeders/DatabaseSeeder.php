<?php

namespace Database\Seeders;

use App\Models\MahjongTable;
use App\Models\Pricing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@mahjong.com',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        // Create viewer user
        User::create([
            'name'     => 'Viewer',
            'email'    => 'viewer@mahjong.com',
            'password' => Hash::make('viewer123'),
            'role'     => 'viewer',
        ]);

        // Create 6 mahjong tables with pricing
        $tables = [
            ['name' => 'Meja 1 - Reguler',    'price' => 50000],
            ['name' => 'Meja 2 - Reguler',    'price' => 50000],
            ['name' => 'Meja 3 - Reguler',    'price' => 50000],
            ['name' => 'Meja 4 - VIP',        'price' => 75000],
            ['name' => 'Meja 5 - VIP',        'price' => 75000],
            ['name' => 'Meja 6 - VVIP Suite', 'price' => 100000],
        ];

        foreach ($tables as $tableData) {
            $table = MahjongTable::create([
                'name'        => $tableData['name'],
                'capacity'    => 4,
                'status'      => 'available',
                'description' => 'Meja Mahjong ' . ($tableData['price'] >= 100000 ? 'Suite Premium' : ($tableData['price'] >= 75000 ? 'VIP dengan layanan khusus' : 'standar nyaman')),
            ]);

            Pricing::create([
                'mahjong_table_id' => $table->id,
                'price_per_hour'   => $tableData['price'],
                'is_active'        => true,
            ]);
        }
    }
}
