<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class LessonCommentSeeder extends Seeder
{
    public function run(): void
    {
        if (Lesson::query()->doesntExist()) {
            $this->call(LessonSeeder::class);
        }

        if (User::query()->count() < 10) {
            User::factory()->count(10)->create();
        }

        $userIds = User::query()->pluck('id')->all();

        Lesson::query()
            ->select(['id'])
            ->chunkById(100, function ($lessons) use ($userIds): void {
                foreach ($lessons as $lesson) {
                    $count = fake()->numberBetween(1, 4);
                    for ($index = 1; $index <= $count; $index++) {
                        LessonComment::query()->create([
                            'lesson_id' => $lesson->id,
                            'user_id' => fake()->randomElement($userIds),
                            'content' => fake()->sentence(14),
                        ]);
                    }
                }
            });
    }
}
