<?php

namespace App\Enums;

enum NotificationType: string
{
    case PaymentSuccess = 'payment_success';
    case StarWallet = 'star_wallet';
    case Enrollment = 'enrollment';
    case LessonCompleted = 'lesson_completed';
}
