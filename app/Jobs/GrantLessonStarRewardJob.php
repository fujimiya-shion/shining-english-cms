<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Services\Star\IStarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GrantLessonStarRewardJob implements ShouldQueue
{
    use Queueable;

    public const SOURCE_VIDEO = 'video';

    public const SOURCE_QUIZ = 'quiz';

    public function __construct(
        public int $userId,
        public int $courseId,
        public int $lessonId,
        public string $source,
    ) {}

    public function handle(IStarService $starService): void
    {
        $lesson = Lesson::query()->find($this->lessonId);

        if (! $lesson || (int) $lesson->course_id !== $this->courseId) {
            return;
        }

        $amount = $this->resolveRewardAmount($lesson);

        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($lesson, $amount, $starService): void {
            $inserted = DB::table('lesson_star_rewards')->insertOrIgnore([
                'user_id' => $this->userId,
                'course_id' => $this->courseId,
                'lesson_id' => $this->lessonId,
                'source' => $this->source,
                'amount' => $amount,
                'awarded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted !== 1) {
                return;
            }

            $success = $starService->addStarByUserId(
                $amount,
                $this->userId,
                $this->buildTransactionMessage($lesson->name),
            );

            if (! $success) {
                throw new RuntimeException('Unable to grant lesson reward stars.');
            }
        });

        Log::info('Granted lesson star reward.', [
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'lesson_id' => $this->lessonId,
            'source' => $this->source,
            'amount' => $amount,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Failed to grant lesson star reward.', [
            'user_id' => $this->userId,
            'course_id' => $this->courseId,
            'lesson_id' => $this->lessonId,
            'source' => $this->source,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function resolveRewardAmount(Lesson $lesson): int
    {
        return match ($this->source) {
            self::SOURCE_VIDEO => (int) $lesson->star_reward_video,
            self::SOURCE_QUIZ => (int) $lesson->star_reward_quiz,
            default => 0,
        };
    }

    private function buildTransactionMessage(string $lessonName): string
    {
        return match ($this->source) {
            self::SOURCE_VIDEO => __("Bạn nhận sao khi hoàn thành bài học ':lesson'", ['lesson' => $lessonName]),
            self::SOURCE_QUIZ => __("Bạn nhận sao khi hoàn thành bài tập của bài học ':lesson'", ['lesson' => $lessonName]),
            default => __('Bạn nhận sao từ bài học'),
        };
    }
}
