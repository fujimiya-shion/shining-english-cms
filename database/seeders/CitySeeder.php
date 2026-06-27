<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        City::truncate();

        $cities = [
            ['name' => 'Hồ Chí Minh', 'sort_order' => 1],
            ['name' => 'Hà Nội', 'sort_order' => 2],
            ['name' => 'Đà Nẵng', 'sort_order' => 3],
            ['name' => 'Hải Phòng', 'sort_order' => 4],
            ['name' => 'Cần Thơ', 'sort_order' => 5],
            ['name' => 'Huế', 'sort_order' => 6],
            ['name' => 'Tuyên Quang', 'sort_order' => 7],
            ['name' => 'Lào Cai', 'sort_order' => 8],
            ['name' => 'Thái Nguyên', 'sort_order' => 9],
            ['name' => 'Phú Thọ', 'sort_order' => 10],
            ['name' => 'Bắc Ninh', 'sort_order' => 11],
            ['name' => 'Hưng Yên', 'sort_order' => 12],
            ['name' => 'Ninh Bình', 'sort_order' => 13],
            ['name' => 'Tỉnh Quảng Trị', 'sort_order' => 14],
            ['name' => 'Quảng Ngãi', 'sort_order' => 15],
            ['name' => 'Gia Lai', 'sort_order' => 16],
            ['name' => 'Khánh Hòa', 'sort_order' => 17],
            ['name' => 'Lâm Đồng', 'sort_order' => 18],
            ['name' => 'Đắk Lắk', 'sort_order' => 19],
            ['name' => 'Đồng Nai', 'sort_order' => 20],
            ['name' => 'Tây Ninh', 'sort_order' => 21],
            ['name' => 'Vĩnh Long', 'sort_order' => 22],
            ['name' => 'Đồng Tháp', 'sort_order' => 23],
            ['name' => 'Cà Mau', 'sort_order' => 24],
            ['name' => 'An Giang', 'sort_order' => 25],
            ['name' => 'Lai Châu', 'sort_order' => 26],
            ['name' => 'Điện Biên', 'sort_order' => 27],
            ['name' => 'Sơn La', 'sort_order' => 28],
            ['name' => 'Lạng Sơn', 'sort_order' => 29],
            ['name' => 'Quảng Ninh', 'sort_order' => 30],
            ['name' => 'Thanh Hóa', 'sort_order' => 31],
            ['name' => 'Nghệ An', 'sort_order' => 32],
            ['name' => 'Hà Tĩnh', 'sort_order' => 33],
            ['name' => 'Cao Bằng', 'sort_order' => 34],
        ];

        foreach ($cities as $cityData) {
            City::create($cityData);
        }
    }
}
