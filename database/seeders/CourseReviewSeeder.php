<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseReviewSeeder extends Seeder
{
    public function run(): void
    {
        if (Course::query()->doesntExist()) {
            $this->call(CourseSeeder::class);
        }

        if (User::query()->count() < 10) {
            User::factory()->count(10)->create();
        }

        $userIds = User::query()->pluck('id')->all();

        Course::query()
            ->select(['id'])
            ->chunkById(50, function ($courses) use ($userIds): void {
                foreach ($courses as $course) {
                    $count = fake()->numberBetween(2, 6);
                    for ($index = 1; $index <= $count; $index++) {
                        CourseReview::query()->create([
                            'course_id' => $course->id,
                            'user_id' => fake()->randomElement($userIds),
                            'rating' => fake()->numberBetween(3, 5),
                            'content' => fake()->sentence(18),
                        ]);
                    }
                }
            });
    }
}
