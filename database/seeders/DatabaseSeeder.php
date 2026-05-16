<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\City;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\Level;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->truncateSeededTables();

        $this->call([
            AdminSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            LevelSeeder::class,
            CourseSeeder::class,
            LessonSeeder::class,
            CourseReviewSeeder::class,
            LessonCommentSeeder::class,
            DeveloperSeeder::class,
        ]);
    }

    private function truncateSeededTables(): void
    {
        Schema::disableForeignKeyConstraints();

        LessonComment::query()->truncate();
        CourseReview::query()->truncate();
        Lesson::query()->truncate();
        Course::query()->truncate();
        Level::query()->truncate();
        Category::query()->truncate();
        City::query()->truncate();
        Admin::query()->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
