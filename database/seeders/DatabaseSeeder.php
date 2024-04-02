<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

         \App\Models\User::factory()->create([
             'name' => 'Tone Ease',
             'email' => 'asd@asd.com',
             'supplier_id' => '817139',
             'token' => 'NVFxc3paVnRPV2FORVF5aGJYbW46c3NJMUkzZG1qRG1EZ3hrQXZUQ2E=',
         ]);
    }
}
