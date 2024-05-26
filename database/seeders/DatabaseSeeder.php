<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $user = \App\Models\User::factory()->create([
            'name' => 'Tone Ease',
            'email' => 'asd@asd.com',
        ]);

        $store = Store::create([
            'user_id' => $user->id,
            'merchant_name' => 'Beyzanaa',
            'supplier_id' => 638780,
            'token' => 'TkRCdGZ2WVFCSm1xeTRsVXN0SnU6Z3hNamJ5bjU3TkhjM1I2VDJQQmc=',
            'is_default' => 1,
        ]);
    }
}
