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
            'merchant_name' => 'Tone Ease',
            'supplier_id' => 817139,
            'token' => 'MjhKRjZ4enNkb2RnRkFjcHhmZUQ6endVQUJXMEpiNXBpSjhXaU5PZ00=',
            'is_default' => 1,
        ]);
    }
}
