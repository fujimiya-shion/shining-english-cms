<?php

use App\Filament\Resources\Courses\RelationManagers\LessonGroupsRelationManager;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonGroup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeLessonGroupsRelationManagerForOwner(int $courseId): LessonGroupsRelationManager
{
    return new class($courseId) extends LessonGroupsRelationManager
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

test('lesson groups relation manager defines form components', function (): void {
    $manager = new LessonGroupsRelationManager;

    $schema = $manager->form(makeSchema());
    $components = schemaComponentMap($schema);

    expect($components['name'])->toBeInstanceOf(TextInput::class);
    expect($components['name']->isRequired())->toBeTrue();
});

test('lesson groups relation manager defines table configuration', function (): void {
    $manager = new LessonGroupsRelationManager;

    $table = $manager->table(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'sort_order',
        'name',
        'lessons_count',
    ]);
});

test('lesson groups relation manager registers actions', function (): void {
    $manager = new LessonGroupsRelationManager;

    $table = $manager->table(makeTable());

    $headerActions = $table->getHeaderActions();
    expect(actionClassList($headerActions))->toEqual([CreateAction::class]);

    $rowActions = $table->getActions();
    expect(actionClassList($rowActions))->toEqual([
        Action::class,
        EditAction::class,
        DeleteAction::class,
    ]);
});

test('lesson groups relation manager mutates create payload and updates lesson order callbacks', function (): void {
    $course = Course::factory()->create();
    $groupA = LessonGroup::query()->create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 1]);
    $groupB = LessonGroup::query()->create(['course_id' => $course->id, 'name' => 'B', 'sort_order' => 2]);
    $lessonA = Lesson::factory()->create([
        'course_id' => $course->id,
        'lesson_group_id' => $groupA->id,
        'group_order' => 99,
        'lesson_order' => 3,
    ]);
    $lessonB = Lesson::factory()->create([
        'course_id' => $course->id,
        'lesson_group_id' => $groupA->id,
        'group_order' => 99,
        'lesson_order' => 4,
    ]);

    $manager = makeLessonGroupsRelationManagerForOwner($course->id);
    $table = $manager->table(makeTable());

    $table->callAfterReordering([$groupB->id, $groupA->id]);
    expect($lessonA->fresh()->group_order)->toBe(2);
    expect($lessonB->fresh()->group_order)->toBe(2);

    $createAction = $table->getHeaderActions()[0];
    expect($createAction)->toBeInstanceOf(CreateAction::class);
    $mutated = $createAction->data(['name' => 'C'])->getData();
    expect($mutated['course_id'])->toBe($course->id);
    expect($mutated['sort_order'])->toBe(3);

    $reorderAction = $table->getActions()[0];
    expect($reorderAction)->toBeInstanceOf(Action::class);

    $fillForm = getProtectedPropertyValue($reorderAction, 'mountUsing');
    expect($fillForm)->toBeInstanceOf(Closure::class);
    $reorderAction->record($groupA);
    $fillForm($reorderAction, makeSchema()->model(Lesson::class));

    $reorderAction->call([
        'data' => [
            'lessons' => [
                ['id' => 0],
                ['id' => $lessonB->id],
                ['id' => $lessonA->id],
            ],
        ],
    ]);

    expect($lessonB->fresh()->lesson_order)->toBe(2);
    expect($lessonA->fresh()->lesson_order)->toBe(3);
    expect($lessonA->fresh()->group_order)->toBe($groupA->sort_order);
});

test('lesson groups relation manager disables delete when group has lessons', function (): void {
    $course = Course::factory()->create();
    $group = LessonGroup::query()->create(['course_id' => $course->id, 'name' => 'A', 'sort_order' => 1]);
    Lesson::factory()->create([
        'course_id' => $course->id,
        'lesson_group_id' => $group->id,
    ]);

    $manager = makeLessonGroupsRelationManagerForOwner($course->id);
    $table = $manager->table(makeTable());
    $deleteAction = $table->getActions()[2];
    expect($deleteAction)->toBeInstanceOf(DeleteAction::class);

    $deleteAction->record($group);
    expect($deleteAction->isDisabled())->toBeTrue();
});
