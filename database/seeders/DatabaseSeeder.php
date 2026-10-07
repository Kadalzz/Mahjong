<?php

namespace Database\Seeders;

use App\Models\MahjongTable;
use App\Models\Pricing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('ADMIN_PASSWORD') ?: Str::password(16);

        User::create([
            'name'     => 'Admin',
            'email'    => env('ADMIN_EMAIL', 'admin@mahjong.com'),
            'password' => Hash::make($adminPassword),
            'role'     => 'admin',
        ]);

        if (!env('ADMIN_PASSWORD')) {
            $this->command->warn("Admin password (catat sekarang, tidak ditampilkan lagi): {$adminPassword}");
        }

        
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
