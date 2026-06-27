<?php

namespace App\Enums;

enum StarTransactionType: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
    case RegistrationBonus = 'registration_bonus';
    case DailyCheckin = 'daily_checkin';
    case ReviewReward = 'review_reward';
    case CourseComplete = 'course_complete';
    case LessonRewardVideo = 'lesson_reward_video';
    case LessonRewardQuiz = 'lesson_reward_quiz';
    case StarPayment = 'star_payment';

    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases()
        );
    }
}
