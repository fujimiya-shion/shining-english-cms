<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Lesson>
     */
    protected $model = Lesson::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'name' => $title,
            'slug' => null,
            'course_id' => Course::factory(),
            'group_name' => fake()->randomElement([
                'Fundamentals of English',
                'Grammar in Depth',
                'Vocabulary Expansion',
            ]),
            'group_order' => fake()->numberBetween(1, 5),
            'lesson_order' => fake()->numberBetween(1, 20),
            'video_url' => fake()->url(),
            'description' => fake()->paragraph(),
            'duration_minutes' => fake()->numberBetween(8, 40),
            'star_reward_video' => fake()->numberBetween(0, 5),
            'star_reward_quiz' => fake()->numberBetween(0, 5),
            'has_quiz' => fake()->boolean(35),
        ];
    }
}
