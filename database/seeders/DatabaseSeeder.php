<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Tone Ease',
                'email' => 'asd@asd.com',
                'email_verified_at' => '2024-04-04 19:25:07',
                'password' => '$2y$12$rY3kDQqeH4c3GgcKbiIZFe90fz3HIvFPSJO855EW6FWjYzXcY0kAO',
                'remember_token' => 'tBbCWNC7Pl',
                'created_at' => '2024-04-04 19:25:08',
                'updated_at' => '2024-04-04 19:25:08',
            ],
            [
                'id' => 3,
                'name' => 'ALPEREN ÇELENK',
                'email' => 'clnkalperen52@gmail.com',
                'email_verified_at' => NULL,
                'password' => '$2y$12$6aJrrrhW79Dik9XDCQTBo.epYrLdYd4aoSRyzNEZ9JjdxxIveGJZ6',
                'remember_token' => NULL,
                'created_at' => '2024-05-01 15:43:58',
                'updated_at' => '2024-05-01 15:43:58',
            ],
        ]);

        DB::table('stores')->insert([
            [
                'id' => 1,
                'user_id' => 1,
                'merchant_name' => 'Magaza 1',
                'supplier_id' => 739429,
                'token' => 'QVpEa29XNHllWlJzZjB6dVVRTmQ6cVlPZDhMbnNkNHB6eHRUOWlIcEk=',
                'is_default' => 0,
                'created_at' => '2024-04-04 19:25:08',
                'updated_at' => '2024-04-20 12:06:57',
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'merchant_name' => 'Beyzanaa',
                'supplier_id' => 638780,
                'token' => 'TkRCdGZ2WVFCSm1xeTRsVXN0SnU6Z3hNamJ5bjU3TkhjM1I2VDJQQmc=',
                'is_default' => 1,
                'created_at' => '2024-04-04 19:46:06',
                'updated_at' => '2024-04-20 12:06:57',
            ],
            [
                'id' => 4,
                'user_id' => 3,
                'merchant_name' => 'CLNK HOME',
                'supplier_id' => 627474,
                'token' => 'aFVSOUpsV1J1SjBZRXJPTVFsbXA6U09BWDNnc2pVb3JIZkhRZDJZYXo=',
                'is_default' => 1,
                'created_at' => '2024-05-01 15:52:16',
                'updated_at' => '2024-05-28 07:52:23',
            ],
            [
                'id' => 5,
                'user_id' => 3,
                'merchant_name' => 'DURABLE BOTTLE',
                'supplier_id' => 806097,
                'token' => 'ZEdaUDFJTU9wVm9kRExpa2N3eEc6TDJZajBDNEpPTzl4N1dpT0ZiODY=',
                'is_default' => 0,
                'created_at' => '2024-05-01 15:54:55',
                'updated_at' => '2024-05-28 07:52:23',
            ],
            [
                'id' => 6,
                'user_id' => 3,
                'merchant_name' => 'KİTCHEN TİMES',
                'supplier_id' => 719522,
                'token' => 'bmdWVWlPdG1BVkJjbEVkMm5zMWc6bVp2QmdJdDRIYllsTWJZQWozM0Q=',
                'is_default' => 0,
                'created_at' => '2024-05-01 15:57:28',
                'updated_at' => '2024-05-28 07:52:23',
            ],
            [
                'id' => 7,
                'user_id' => 3,
                'merchant_name' => 'GLAS TİME',
                'supplier_id' => 758800,
                'token' => 'TDFQYjV5bmtEVXpaREtYaUlJbkM6R3FTdHJXVnJjbllCZzNHeVNlSkM=',
                'is_default' => 1,
                'created_at' => '2024-05-01 15:59:49',
                'updated_at' => '2024-05-28 07:52:23',
            ],
        ]);
    }
}
