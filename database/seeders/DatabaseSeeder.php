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
                'name' => 'Akdag Home',
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
                'merchant_name' => 'Akdag home',
                'supplier_id' => 258984,
                'token' => 'WDBha1pBMXowMnJoa0ViRUIwNm86SWYwb2pyNnQwOUJmTUpsVE05TUM=',
                'is_default' => 0,
                'order_fetched_at' => null,
                'created_at' => '2024-04-04 19:25:08',
                'updated_at' => '2024-04-20 12:06:57',
                'api_key' => 'SujpauhIsfthioFj7LB3',
                'api_secret' => '7SM7QIGFl92BlQxAcPsm',
                'kolaygelsin_customer_id' => env('KOLAYGELSIN_CUSTOMER_ID'),
                'kolaygelsin_username' => env('KOLAYGELSIN_MUSTERI'),
                'kolaygelsin_password' => env('KOLAYGELSIN_SIFRE'),
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'merchant_name' => 'Beyzanaa',
                'supplier_id' => 638780,
                'token' => 'TkRCdGZ2WVFCSm1xeTRsVXN0SnU6Z3hNamJ5bjU3TkhjM1I2VDJQQmc=',
                'is_default' => 1,
                'order_fetched_at' => null,
                'created_at' => '2024-04-04 19:46:06',
                'updated_at' => '2024-04-20 12:06:57',
                'api_key' => '',
                'api_secret' => '',
                'kolaygelsin_customer_id' => '',
                'kolaygelsin_username' => '',
                'kolaygelsin_password' => '',
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'merchant_name' => 'CLNK HOME',
                'supplier_id' => 627474,
                'token' => 'aFVSOUpsV1J1SjBZRXJPTVFsbXA6U09BWDNnc2pVb3JIZkhRZDJZYXo=',
                'is_default' => 1,
                'order_fetched_at' => null,
                'created_at' => '2024-05-01 15:52:16',
                'updated_at' => '2024-05-28 07:52:23',
                'api_key' => '',
                'api_secret' => '',
                'kolaygelsin_customer_id' => '',
                'kolaygelsin_username' => '',
                'kolaygelsin_password' => '',
            ],
        ]);
    }
}
