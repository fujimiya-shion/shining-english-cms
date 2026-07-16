<?php

use App\Filament\Resources\Lessons\Schemas\LessonForm;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('create option using handles existing soft deleted quiz', function (): void {
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'video_url' => 'https://example.com/video',
    ]);

    $quiz = Quiz::query()->create([
        'lesson_id' => $lesson->id,
        'pass_percent' => 80,
        'name' => 'Old Quiz',
    ]);
    $quiz->delete();

    DB::statement('UPDATE lessons SET quiz_id = NULL WHERE id = ?', [$lesson->id]);

    $schema = LessonForm::configure(makeSchema()->model($lesson));
    $components = schemaComponentMap($schema);
    $select = $components['quiz_id'] ?? null;

    if (! $select) {
        $this->markTestSkipped('quiz_id field not visible in test schema context');
    }

    $createOptionUsing = $select->getCreateOptionUsing();
    $get = new class($select, $lesson) extends \Filament\Schemas\Components\Utilities\Get
    {
        public function __construct($component, private Lesson $lesson)
        {
            parent::__construct($component);
        }

        public function __invoke(string|\Filament\Schemas\Components\Component $path = '', bool $isAbsolute = false): mixed
        {
            return $path === 'id' ? $this->lesson->id : null;
        }
    };

    $state = [];
    $set = new class($select, $state) extends \Filament\Schemas\Components\Utilities\Set
    {
        public function __construct($component, public array &$capturedState)
        {
            parent::__construct($component);
        }

        public function __invoke(string|\Filament\Schemas\Components\Component $path, mixed $value, ...$args): mixed
        {
            $this->capturedState[$path] = $value;

            return $value;
        }
    };

    $resultId = $createOptionUsing([
        'name' => 'Updated Quiz',
        'pass_percent' => 85,
        'questions' => json_encode([[
            'content' => 'Q1',
            'sort_order' => 0,
            'answers' => [
                ['content' => 'A1', 'is_correct' => true, 'sort_order' => 0],
            ],
        ]]),
    ], $get, $set);

    expect($resultId)->toBe($quiz->id);
    $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'name' => 'Updated Quiz', 'pass_percent' => 85]);
    $this->assertDatabaseHas('quiz_questions', ['quiz_id' => $quiz->id, 'content' => 'Q1']);
    $this->assertDatabaseHas('quiz_answers', ['content' => 'A1', 'is_correct' => true]);
});

it('create option using creates new quiz when none exists', function (): void {
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->create([
        'course_id' => $course->id,
        'video_url' => 'https://example.com/video',
    ]);

    $schema = LessonForm::configure(makeSchema()->model($lesson));
    $components = schemaComponentMap($schema);
    $select = $components['quiz_id'] ?? null;

    if (! $select) {
        $this->markTestSkipped('quiz_id field not visible in test schema context');
    }

    $createOptionUsing = $select->getCreateOptionUsing();
    $get = new class($select, $lesson) extends \Filament\Schemas\Components\Utilities\Get
    {
        public function __construct($component, private Lesson $lesson)
        {
            parent::__construct($component);
        }

        public function __invoke(string|\Filament\Schemas\Components\Component $path = '', bool $isAbsolute = false): mixed
        {
            return $path === 'id' ? $this->lesson->id : null;
        }
    };

    $state = [];
    $set = new class($select, $state) extends \Filament\Schemas\Components\Utilities\Set
    {
        public function __construct($component, public array &$capturedState)
        {
            parent::__construct($component);
        }

        public function __invoke(string|\Filament\Schemas\Components\Component $path, mixed $value, ...$args): mixed
        {
            $this->capturedState[$path] = $value;

            return $value;
        }
    };

    $result = $createOptionUsing([
        'name' => 'Fresh Quiz',
        'pass_percent' => 70,
        'questions' => '[]',
    ], $get, $set);

    expect($result)->toBeGreaterThan(0);
    $this->assertDatabaseHas('quizzes', ['id' => $result, 'name' => 'Fresh Quiz']);
});
