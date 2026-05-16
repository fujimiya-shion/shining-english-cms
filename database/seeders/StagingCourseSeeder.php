<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Level;
use Illuminate\Database\Seeder;

class StagingCourseSeeder extends Seeder
{
    /**
     * @var array<int, array{
     *   name:string,
     *   slug:string,
     *   price:int,
     *   status:bool,
     *   thumbnail:?string,
     *   category_slug:string,
     *   level_name:string,
     *   description:string,
     *   rating:float,
     *   learned:int
     * }>
     */
    private const COURSES = [
        [
            'name' => 'Nền Tảng Ngữ Pháp Tiếng Anh A1-A2',
            'slug' => 'nen-tang-ngu-phap-tieng-anh-a1-a2',
            'price' => 199000,
            'status' => true,
            'thumbnail' => null,
            'category_slug' => 'ngu-phap-nen-tang',
            'level_name' => 'Sơ cấp',
            'description' => 'Khóa học giúp bạn nắm chắc ngữ pháp nền tảng để giao tiếp tiếng Anh hằng ngày tự tin hơn.',
            'rating' => 4.6,
            'learned' => 1240,
        ],
        [
            'name' => 'Từ Vựng Thiết Yếu Cho Công Việc và Học Tập',
            'slug' => 'tu-vung-thiet-yeu-cho-cong-viec-va-hoc-tap',
            'price' => 249000,
            'status' => true,
            'thumbnail' => null,
            'category_slug' => 'mo-rong-tu-vung',
            'level_name' => 'Cơ bản',
            'description' => 'Mở rộng vốn từ thực tế theo chủ đề học tập và công việc, dễ áp dụng ngay vào tình huống thực.',
            'rating' => 4.7,
            'learned' => 980,
        ],
        [
            'name' => 'Luyện Nghe Chuyên Sâu Với Hội Thoại Thực Tế',
            'slug' => 'luyen-nghe-chuyen-sau-voi-hoi-thoai-thuc-te',
            'price' => 299000,
            'status' => true,
            'thumbnail' => null,
            'category_slug' => 'luyen-nghe-chuyen-sau',
            'level_name' => 'Trung cấp',
            'description' => 'Rèn kỹ năng nghe qua hội thoại đời sống, đa dạng giọng đọc và ngữ cảnh giao tiếp thường gặp.',
            'rating' => 4.8,
            'learned' => 870,
        ],
        [
            'name' => 'Bootcamp Nói Tiếng Anh Trôi Chảy B1-B2',
            'slug' => 'bootcamp-noi-tieng-anh-troi-chay-b1-b2',
            'price' => 349000,
            'status' => true,
            'thumbnail' => null,
            'category_slug' => 'noi-troi-chay',
            'level_name' => 'Trung cấp nâng cao',
            'description' => 'Tăng phản xạ nói, cải thiện phát âm và xây dựng sự tự tin khi giao tiếp tiếng Anh nâng cao.',
            'rating' => 4.9,
            'learned' => 765,
        ],
        [
            'name' => 'Luyện Viết IELTS Task 1 và Task 2 Chuyên Sâu',
            'slug' => 'luyen-viet-ielts-task-1-va-task-2-chuyen-sau',
            'price' => 399000,
            'status' => true,
            'thumbnail' => null,
            'category_slug' => 'luyen-thi-chung-chi',
            'level_name' => 'Nâng cao',
            'description' => 'Cung cấp chiến lược viết rõ ràng và bài mẫu chất lượng để cải thiện điểm Writing hiệu quả.',
            'rating' => 4.7,
            'learned' => 620,
        ],
    ];

    public function run(): void
    {
        if (Category::query()->doesntExist()) {
            $this->call(CategorySeeder::class);
        }

        if (Level::query()->doesntExist()) {
            $this->call(LevelSeeder::class);
        }

        foreach (self::COURSES as $course) {
            $category = Category::query()
                ->where('slug', $course['category_slug'])
                ->first();
            $level = Level::query()
                ->where('name', $course['level_name'])
                ->first();

            if (! $category || ! $level) {
                continue;
            }

            Course::query()->updateOrCreate(
                ['slug' => $course['slug']],
                [
                    'name' => $course['name'],
                    'price' => $course['price'],
                    'status' => $course['status'],
                    'thumbnail' => $course['thumbnail'],
                    'category_id' => $category->id,
                    'level_id' => $level->id,
                    'description' => $course['description'],
                    'rating' => $course['rating'],
                    'learned' => $course['learned'],
                ]
            );
        }
    }
}
