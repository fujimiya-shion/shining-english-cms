<?php

use App\Filament\Resources\Blogs\Tables\BlogsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Model;

test('blogs table defines expected columns and filters', function (): void {
    $table = BlogsTable::configure(makeTable());

    expect(tableColumnNames($table))->toEqual([
        'thumbnail',
        'title',
        'slug',
        'tag.name',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ]);

    expect(actionClassList(array_values($table->getFilters())))->toEqual([
        TernaryFilter::class,
        SelectFilter::class,
        TrashedFilter::class,
    ]);
});

test('blogs table registers actions and duplicates a record', function (): void {
    $table = BlogsTable::configure(makeTable());
    $actions = $table->getRecordActions();

    expect(actionClassList($actions))->toEqual([
        EditAction::class,
        \Filament\Actions\Action::class,
        DeleteAction::class,
    ]);

    $record = new class extends Model
    {
        public static ?Model $saved = null;

        public $timestamps = false;

        protected $guarded = [];

        public function save(array $options = []): bool
        {
            self::$saved = $this;

            return true;
        }
    };
    $record->title = 'Original';
    $record->slug = 'original';

    $actions[1]->getActionFunction()($record);

    expect($record::$saved?->title)->toBe('Original (Sao chép)');
    expect($record::$saved?->slug)->toBe('original-copy');
});

test('blogs table registers bulk action group', function (): void {
    $table = BlogsTable::configure(makeTable());
    $toolbarActions = $table->getToolbarActions();

    expect($toolbarActions)->toHaveCount(1);
    expect($toolbarActions[0])->toBeInstanceOf(BulkActionGroup::class);
    expect(actionClassList($toolbarActions[0]->getActions()))->toEqual([
        DeleteBulkAction::class,
        ForceDeleteBulkAction::class,
        RestoreBulkAction::class,
    ]);
});
