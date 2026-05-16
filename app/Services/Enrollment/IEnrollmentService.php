<?php

namespace App\Services\Enrollment;

use App\Models\Enrollment;
use App\Services\IService;

interface IEnrollmentService extends IService
{
    public function enroll(int $userId, int $courseId, ?int $orderId = null): Enrollment;

    public function isEnrolled(int $userId, int $courseId): bool;

    public function hasPendingEnrollment(int $userId, int $courseId): bool;

    /**
     * @return array{
     *   course_id:int,
     *   current_lesson_id:int|null,
     *   completed_lesson_ids:list<int>,
     *   total_lessons:int,
     *   progress_percentage:float,
     *   has_reviewed:bool
     * }|null
     */
    public function getLearningProgress(int $userId, int $courseId): ?array;

    /**
     * @return array{
     *   course_id:int,
     *   current_lesson_id:int|null,
     *   completed_lesson_ids:list<int>,
     *   total_lessons:int,
     *   progress_percentage:float,
     *   has_reviewed:bool,
     *   next_lesson:array{id:int,has_quiz:bool}|null
     * }|null
     */
    public function completeLesson(int $userId, int $courseId, int $lessonId): ?array;

    /**
     * @return array{
     *   course_id:int,
     *   current_lesson_id:int|null,
     *   completed_lesson_ids:list<int>,
     *   total_lessons:int,
     *   progress_percentage:float,
     *   has_reviewed:bool
     * }|null
     */
    public function setCurrentLesson(int $userId, int $courseId, int $lessonId): ?array;
}
