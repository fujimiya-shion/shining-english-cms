<?php

use App\Filament\Resources\Lessons\Schemas\LessonForm;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonGroup;
use App\Util\Video\VideoMetadataReader;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('lesson form defines expected components', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));

    $components = schemaComponentMap($schema);

    expect($components)->toHaveKeys([
        'name',
        'slug',
        'course_id',
        'lesson_group_id',
        'lesson_order',
        'video_url',
        'documents',
        'document_names',
        'duration_minutes',
        'description',
        'star_reward_video',
        'star_reward_quiz',
        'has_quiz',
        'pass_percent',
    ]);

    expect($components['name'])->toBeInstanceOf(TextInput::class);
    expect($components['slug'])->toBeInstanceOf(TextInput::class);
    expect($components['course_id'])->toBeInstanceOf(Select::class);
    expect($components['video_url'])->toBeInstanceOf(FileUpload::class);
    expect($components['documents'])->toBeInstanceOf(FileUpload::class);
    expect($components['document_names'])->toBeInstanceOf(KeyValue::class);
    expect($components['star_reward_video'])->toBeInstanceOf(TextInput::class);
    expect($components['star_reward_quiz'])->toBeInstanceOf(TextInput::class);
    expect($components['has_quiz'])->toBeInstanceOf(Toggle::class);
});

test('lesson form marks required fields', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));

    $components = schemaComponentMap($schema);

    expect($components['name']->isRequired())->toBeTrue();
    expect($components['course_id']->isRequired())->toBeTrue();
    expect($components['video_url']->isRequired())->toBeTrue();
    expect($components['pass_percent']->isRequired())->toBeTrue();
});

test('lesson form configures numeric star inputs', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));

    $components = schemaComponentMap($schema);

    expect($components['star_reward_video']->isNumeric())->toBeTrue();
    expect($components['star_reward_quiz']->isNumeric())->toBeTrue();
});

test('lesson form clears duration when video state is empty', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));
    $components = schemaComponentMap($schema);
    /** @var \Filament\Forms\Components\FileUpload $fileUpload */
    $fileUpload = $components['video_url'];

    $hooks = getProtectedPropertyValue($fileUpload, 'afterStateUpdated');
    $hook = $hooks[0] ?? null;

    expect($hook)->toBeInstanceOf(Closure::class);

    $state = [];
    $set = new class($fileUpload, $state) extends Set
    {
        public function __construct(Component $component, public array &$state)
        {
            parent::__construct($component);
        }

        public function __invoke(string | Component $path, mixed $value, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
        {
            $this->state[$path] = $value;
            return $value;
        }
    };

    $hook($set, null);

    expect($state['duration_minutes'])->toBeNull();
});

test('lesson form sets duration from relative video path', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));
    $components = schemaComponentMap($schema);
    /** @var \Filament\Forms\Components\FileUpload $fileUpload */
    $fileUpload = $components['video_url'];

    $hooks = getProtectedPropertyValue($fileUpload, 'afterStateUpdated');
    $hook = $hooks[0] ?? null;

    expect($hook)->toBeInstanceOf(Closure::class);

    $reader = \Mockery::mock(VideoMetadataReader::class);
    $reader->shouldReceive('detectDurationMinutes')
        ->once()
        ->with('lessons/video.mp4', 'local')
        ->andReturn(9);
    app()->instance(VideoMetadataReader::class, $reader);

    $state = [];
    $set = new class($fileUpload, $state) extends Set
    {
        public function __construct(Component $component, public array &$state)
        {
            parent::__construct($component);
        }

        public function __invoke(string | Component $path, mixed $value, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
        {
            $this->state[$path] = $value;
            return $value;
        }
    };

    $hook($set, 'lessons/video.mp4');

    expect($state['duration_minutes'])->toBe(9);
});

test('lesson form resolves array state and sets duration', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));
    $components = schemaComponentMap($schema);
    /** @var \Filament\Forms\Components\FileUpload $fileUpload */
    $fileUpload = $components['video_url'];

    $hooks = getProtectedPropertyValue($fileUpload, 'afterStateUpdated');
    $hook = $hooks[0] ?? null;

    expect($hook)->toBeInstanceOf(Closure::class);

    $reader = \Mockery::mock(VideoMetadataReader::class);
    $reader->shouldReceive('detectDurationMinutes')
        ->once()
        ->with('lessons/first.mp4', 'local')
        ->andReturn(3);
    app()->instance(VideoMetadataReader::class, $reader);

    $state = [];
    $set = new class($fileUpload, $state) extends Set
    {
        public function __construct(Component $component, public array &$state)
        {
            parent::__construct($component);
        }

        public function __invoke(string | Component $path, mixed $value, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
        {
            $this->state[$path] = $value;
            return $value;
        }
    };

    $hook($set, ['lessons/first.mp4', 'lessons/second.mp4']);

    expect($state['duration_minutes'])->toBe(3);
});

test('lesson form sets duration from uploaded temp file object', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));
    $components = schemaComponentMap($schema);
    /** @var \Filament\Forms\Components\FileUpload $fileUpload */
    $fileUpload = $components['video_url'];

    $hooks = getProtectedPropertyValue($fileUpload, 'afterStateUpdated');
    $hook = $hooks[0] ?? null;

    expect($hook)->toBeInstanceOf(Closure::class);

    $reader = \Mockery::mock(VideoMetadataReader::class);
    $reader->shouldReceive('detectDurationMinutesFromAbsolutePath')
        ->once()
        ->with('/tmp/fake-upload.mp4')
        ->andReturn(5);
    app()->instance(VideoMetadataReader::class, $reader);

    $uploadLike = new class
    {
        public function getRealPath(): string
        {
            return '/tmp/fake-upload.mp4';
        }
    };

    $state = [];
    $set = new class($fileUpload, $state) extends Set
    {
        public function __construct(Component $component, public array &$state)
        {
            parent::__construct($component);
        }

        public function __invoke(string | Component $path, mixed $value, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
        {
            $this->state[$path] = $value;
            return $value;
        }
    };

    $hook($set, $uploadLike);

    expect($state['duration_minutes'])->toBe(5);
});

test('lesson form toggles quiz state when has_quiz changes', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));

    $components = schemaComponentMap($schema);
    /** @var \Filament\Forms\Components\Toggle $toggle */
    $toggle = $components['has_quiz'];

    $rules = getProtectedPropertyValue($toggle, 'afterStateUpdated');
    $hook = $rules[0] ?? null;

    expect($hook)->toBeInstanceOf(Closure::class);

    $state = [];
    $set = new class($toggle, $state) extends Set
    {
        public function __construct(Component $component, public array &$state)
        {
            parent::__construct($component);
        }

        public function __invoke(string | Component $path, mixed $value, bool $isAbsolute = false, bool $shouldCallUpdatedHooks = false): mixed
        {
            $this->state[$path] = $value;
            return $value;
        }
    };

    $hook($set, true);
    expect($state['quiz.pass_percent'])->toBe(80);

    $hook($set, false);
    expect($state['quiz'])->toBeNull();
});

test('lesson form resolves lesson group options and create option behavior', function (): void {
    $course = Course::factory()->create();
    $existingGroup = LessonGroup::query()->create([
        'course_id' => $course->id,
        'name' => 'Existing',
        'sort_order' => 1,
    ]);

    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));
    $components = schemaComponentMap($schema);
    /** @var Select $groupSelect */
    $groupSelect = $components['lesson_group_id'];

    $get = new class($groupSelect, $course) extends Get
    {
        public function __construct(Component $component, private Course $course)
        {
            parent::__construct($component);
        }

        public function __invoke(string | Component $path = '', bool $isAbsolute = false): mixed
        {
            return $path === 'course_id' ? $this->course->id : null;
        }
    };

    $optionsResolver = getProtectedPropertyValue($groupSelect, 'options');
    expect($optionsResolver)->toBeInstanceOf(Closure::class);

    $options = $optionsResolver($get);
    expect($options)->toHaveKey((string) $existingGroup->id);

    $createOptionUsing = $groupSelect->getCreateOptionUsing();
    expect($createOptionUsing)->toBeInstanceOf(Closure::class);

    $sameId = $createOptionUsing(['name' => 'Existing'], $get);
    expect($sameId)->toBe($existingGroup->id);

    $newId = $createOptionUsing(['name' => 'New Group'], $get);
    expect($newId)->toBeInt();
    expect($newId)->not->toBe($existingGroup->id);
});

test('lesson form returns empty group options when course is missing', function (): void {
    $schema = LessonForm::configure(makeSchema()->model(Lesson::class));
    $components = schemaComponentMap($schema);
    /** @var Select $groupSelect */
    $groupSelect = $components['lesson_group_id'];

    $get = new class($groupSelect) extends Get
    {
        public function __construct(Component $component)
        {
            parent::__construct($component);
        }

        public function __invoke(string | Component $path = '', bool $isAbsolute = false): mixed
        {
            return null;
        }
    };

    $optionsResolver = getProtectedPropertyValue($groupSelect, 'options');
    $options = $optionsResolver($get);

    expect($options)->toBe([]);
    expect($groupSelect->getCreateOptionUsing()(['name' => 'Any'], $get))->toBe(0);
});
