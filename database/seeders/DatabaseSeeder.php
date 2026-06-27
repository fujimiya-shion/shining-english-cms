<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Blog;
use App\Models\BlogTag;
use App\Models\Category;
use App\Models\City;
use App\Models\Contact;
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
        $isStaging = app()->environment('staging');
        $courseSeeder = $isStaging
            ? StagingCourseSeeder::class
            : CourseSeeder::class;

        $seeders = [
            AdminSeeder::class,
            AdminPermissionSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            LevelSeeder::class,
            $courseSeeder,
            BlogSeeder::class,
            DeveloperSeeder::class,
        ];

        if (! $isStaging) {
            $seeders[] = LessonSeeder::class;
            $seeders[] = CourseReviewSeeder::class;
            $seeders[] = LessonCommentSeeder::class;
        }

        $this->call($seeders);
    }

    private function truncateSeededTables(): void
    {
        Schema::disableForeignKeyConstraints();

        LessonComment::query()->truncate();
        Blog::query()->truncate();
        BlogTag::query()->truncate();
        CourseReview::query()->truncate();
        Contact::query()->truncate();
        Lesson::query()->truncate();
        Course::query()->truncate();
        Level::query()->truncate();
        Category::query()->truncate();
        City::query()->truncate();
        Admin::query()->truncate();

        Schema::enableForeignKeyConstraints();
    }
}
