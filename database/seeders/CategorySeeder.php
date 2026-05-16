<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::truncate();
        $categories = [
            ['name' => 'Ngữ pháp nền tảng', 'slug' => 'ngu-phap-nen-tang'],
            ['name' => 'Mở rộng từ vựng', 'slug' => 'mo-rong-tu-vung'],
            ['name' => 'Luyện nghe chuyên sâu', 'slug' => 'luyen-nghe-chuyen-sau'],
            ['name' => 'Nói trôi chảy', 'slug' => 'noi-troi-chay'],
            ['name' => 'Luyện viết thực hành', 'slug' => 'luyen-viet-thuc-hanh'],
            ['name' => 'Luyện thi chứng chỉ', 'slug' => 'luyen-thi-chung-chi'],
        ];

        foreach ($categories as $data) {
            Category::query()->firstOrCreate(
                ['slug' => $data['slug']],
                array_merge(['parent_id' => null], $data)
            );
        }
    }
}
