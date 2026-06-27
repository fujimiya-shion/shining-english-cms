<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\LessonGroup;
use App\Util\Php\PhpUploadLimit;
use App\Util\Video\VideoMetadataReader;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LessonForm
{
    public static function configure(
        Schema $schema,
        bool $withCourseField = true,
        ?int $fixedCourseId = null
    ): Schema {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Thông tin bài học')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        Toggle::make('is_preview_free')
                            ->label('Học thử miễn phí')
                            ->helperText('Cho phép học viên chưa mua xem video bài học này.')
                            ->inline(false)
                            ->columnSpanFull()
                            ->default(false),

                        TextInput::make('name')
                            ->label('Tên bài học')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(8),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn (Get $get): bool => ! (bool) $get('customize_slug'))
                            ->helperText('Mặc định tự động tạo từ tên bài học. Bật toggle để tự nhập.')
                            ->columnSpan(4),
                        Toggle::make('customize_slug')
                            ->label('Tự chỉnh sửa slug')
                            ->default(false)
                            ->live()
                            ->dehydrated(false)
                            ->columnSpan(4)
                            ->columnStart(9),
                        ...($withCourseField ? [
                            Select::make('course_id')
                                ->label('Khóa học')
                                ->relationship('course', 'name')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(fn (Set $set): mixed => $set('lesson_group_id', null))
                                ->columnSpan(6),
                        ] : []),
                        Select::make('lesson_group_id')
                            ->label('Nhóm bài học')
                            ->disabled(fn (Get $get): bool => ($fixedCourseId ?? (int) ($get('course_id') ?? 0)) <= 0)
                            ->options(function (Get $get) use ($fixedCourseId): array {
                                $courseId = $fixedCourseId ?? (int) ($get('course_id') ?? 0);
                                if ($courseId <= 0) {
                                    return [];
                                }

                                return LessonGroup::query()
                                    ->where('course_id', $courseId)
                                    ->orderBy('sort_order')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Group Name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data, Get $get) use ($fixedCourseId): int {
                                $courseId = $fixedCourseId ?? (int) ($get('course_id') ?? 0);

                                if ($courseId <= 0) {
                                    return 0;
                                }

                                $name = trim((string) ($data['name'] ?? ''));

                                $existing = LessonGroup::query()
                                    ->where('course_id', $courseId)
                                    ->where('name', $name)
                                    ->first();

                                if ($existing) {
                                    return (int) $existing->id;
                                }

                                $group = LessonGroup::query()->create([
                                    'course_id' => $courseId,
                                    'name' => $name,
                                    'sort_order' => ((int) LessonGroup::query()
                                        ->where('course_id', $courseId)
                                        ->max('sort_order')) + 1,
                                ]);

                                return (int) $group->id;
                            })
                            ->helperText('Quản lý nhóm trong mục Lesson Groups của khóa học.')
                            ->columnSpan($withCourseField ? 4 : 8),
                        TextInput::make('lesson_order')
                            ->label('Thứ tự bài học')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Để trống để tự động sắp xếp.')
                            ->columnSpan($withCourseField ? 2 : 4),
                        Textarea::make('description')
                            ->label('Mô tả')
                            ->rows(4)
                            ->columnSpan(12),
                    ]),
                Section::make('Video & Tài liệu')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        FileUpload::make('video_url')
                            ->key('lesson-video-upload')
                            ->label('Video')
                            ->required()
                            ->acceptedFileTypes([
                                'video/mp4',
                                'video/quicktime',
                                'video/x-msvideo',
                                'video/x-ms-wmv',
                                'video/x-matroska',
                                'video/webm',
                            ])
                            ->maxSize(PhpUploadLimit::maxKilobytes())
                            ->disk('local')
                            ->directory('lessons')
                            ->extraAttributes(['class' => 'lesson-video-upload'])
                            ->columnSpan(8)
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if (! $state) {
                                    $set('duration_minutes', null);

                                    return;
                                }

                                $videoMetadataReader = app(VideoMetadataReader::class);
                                $minutes = null;

                                $resolvedState = is_array($state) ? reset($state) : $state;

                                if (is_object($resolvedState) && method_exists($resolvedState, 'getRealPath')) {
                                    $minutes = $videoMetadataReader
                                        ->detectDurationMinutesFromAbsolutePath($resolvedState->getRealPath());
                                } elseif (is_string($resolvedState)) {
                                    $minutes = $videoMetadataReader->detectDurationMinutes($resolvedState, 'local');
                                }

                                $set('duration_minutes', $minutes);
                            }),
                        FileUpload::make('documents')
                            ->key('lesson-documents-upload')
                            ->label('Tài liệu bài học')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'text/plain',
                                'application/zip',
                                'application/x-zip-compressed',
                            ])
                            ->rules([
                                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/plain,application/zip,application/x-zip-compressed',
                            ])
                            ->maxSize(PhpUploadLimit::maxKilobytes())
                            ->disk('local')
                            ->directory('lesson-documents')
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->downloadable()
                            ->openable()
                            ->storeFileNamesIn('document_names')
                            ->helperText('Upload tài liệu đính kèm. Có thể kéo thả để đổi thứ tự.')
                            ->columnSpan(8),
                        KeyValue::make('document_names')
                            ->label('Tên hiển thị tài liệu')
                            ->keyLabel('File đã upload')
                            ->valueLabel('Tên hiển thị')
                            ->editableKeys(false)
                            ->editableValues(true)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->helperText('Mặc định dùng tên file gốc. Có thể sửa tên hiển thị tại đây.')
                            ->columnSpan(4),
                        TextInput::make('duration_minutes')
                            ->label('Thời lượng (phút)')
                            ->numeric()
                            ->minValue(1)
                            ->readOnly()
                            ->helperText('Tự động lấy từ metadata video khi upload.')
                            ->columnSpan(4),
                    ]),
                Section::make('Sao & Quiz')
                    ->compact()
                    ->columns(12)
                    ->schema([
                        TextInput::make('star_reward_video')
                            ->label('Thưởng sao khi xem video')
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->default(0)
                            ->columnSpan(4),
                        TextInput::make('star_reward_quiz')
                            ->label('Thưởng sao khi làm quiz')
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->default(0)
                            ->columnSpan(4),
                        Toggle::make('has_quiz')
                            ->label('Có quiz')
                            ->inline(false)
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?bool $state): void {
                                if ($state) {
                                    $set('quiz.pass_percent', 80);
                                } else {
                                    $set('quiz', null);
                                }
                            })
                            ->columnSpan(4),
                    ]),
                Section::make('Quiz')
                    ->relationship('quiz')
                    ->visible(fn (Get $get): bool => (bool) $get('has_quiz'))
                    ->compact()
                    ->columns(12)
                    ->schema([
                        TextInput::make('pass_percent')
                            ->label('Điểm đạt (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(1)
                            ->default(80)
                            ->required()
                            ->columnSpan(4),
                    ]),
            ]);
    }
}
