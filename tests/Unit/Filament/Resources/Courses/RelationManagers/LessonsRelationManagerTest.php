<?php

use App\Filament\Resources\Courses\RelationManagers\LessonsRelationManager;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeLessonsRelationManagerForOwner(int $courseId = 1): LessonsRelationManager
{
    return new class($courseId) extends LessonsRelationManager
    {
        public function __construct(private int $courseId) {}

        public function getOwnerRecord(): Course
        {
            $course = new Course;
            $course->id = $this->courseId;

            return $course;
        }
    };
}

test('lessons relation manager defines form components', function (): void {
    $manager = makeLessonsRelationManagerForOwner();

    $schema = $manager->form(makeSchema()->model(Lesson::class));
    $components = schemaComponentMap($schema);

    expect($components)->toHaveKeys([
        'name',
        'slug',
        'lesson_group_id',
        'lesson_order',
        'video_url',
        'duration_minutes',
    ]);
});

test('lessons relation manager defines table configuration', function (): void {
    $manager = makeLessonsRelationManagerForOwner();

    $table = $manager->table(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'lesson_order',
        'name',
        'lessonGroup.name',
        'duration_minutes',
        'has_quiz',
        'created_at',
        'deleted_at',
    ]);
});

test('lessons relation manager registers filters and actions', function (): void {
    $manager = makeLessonsRelationManagerForOwner();

    $table = $manager->table(makeTable());

    $filters = array_values($table->getFilters());
    expect($filters)->toHaveCount(2);
    expect($filters[0])->toBeInstanceOf(TernaryFilter::class);
    expect($filters[1])->toBeInstanceOf(TrashedFilter::class);

    $headerActions = $table->getHeaderActions();
    expect(actionClassList($headerActions))->toEqual([CreateAction::class]);

    $rowActions = $table->getActions();
    expect(actionClassList($rowActions))->toEqual([
        EditAction::class,
        DeleteAction::class,
        RestoreAction::class,
        ForceDeleteAction::class,
    ]);
});

test('lessons relation manager reorders records and mutates create/edit data', function (): void {
    $course = Course::factory()->create();
    $groupA = LessonGroup::query()->create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 10]);
    $groupB = LessonGroup::query()->create(['course_id' => $course->id, 'name' => 'B', 'sort_order' => 20]);

    $lessonA = Lesson::factory()->create([
        'course_id' => $course->id,
        'lesson_group_id' => $groupA->id,
        'group_order' => 0,
        'lesson_order' => 0,
    ]);
    $lessonB = Lesson::factory()->create([
        'course_id' => $course->id,
        'lesson_group_id' => $groupA->id,
        'group_order' => 0,
        'lesson_order' => 0,
    ]);
    $lessonC = Lesson::factory()->create([
        'course_id' => $course->id,
        'lesson_group_id' => $groupB->id,
        'group_order' => 0,
        'lesson_order' => 0,
    ]);

    $manager = makeLessonsRelationManagerForOwner($course->id);
    $table = $manager->table(makeTable());

    $table->callAfterReordering([]);
    $table->callAfterReordering([999999]);
    $table->callAfterReordering([$lessonB->id, $lessonA->id, $lessonC->id]);

    expect($lessonB->fresh()->group_order)->toBe(10);
    expect($lessonB->fresh()->lesson_order)->toBe(1);
    expect($lessonA->fresh()->lesson_order)->toBe(2);
    expect($lessonC->fresh()->group_order)->toBe(20);
    expect($lessonC->fresh()->lesson_order)->toBe(1);

    $createAction = $table->getHeaderActions()[0];
    expect($createAction)->toBeInstanceOf(CreateAction::class);

    $mutatedCreate = $createAction->data([
        'name' => 'New lesson',
        'lesson_group_id' => $groupA->id,
    ])->getData();
    expect($mutatedCreate['course_id'])->toBe($course->id);
    expect($mutatedCreate['group_order'])->toBe(10);
    expect($mutatedCreate['lesson_order'])->toBeGreaterThan(0);

    $rowActions = $table->getActions();
    $editAction = $rowActions[0];
    expect($editAction)->toBeInstanceOf(EditAction::class);

    $mutatedEdit = $editAction
        ->record($lessonA)
        ->data([
            'lesson_group_id' => $groupB->id,
            'lesson_order' => 0,
        ])
        ->getData();

    expect($mutatedEdit['group_order'])->toBe(20);
    expect($mutatedEdit['lesson_order'])->toBe(1);
});
