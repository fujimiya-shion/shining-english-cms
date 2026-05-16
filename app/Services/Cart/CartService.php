<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Repositories\Cart\ICartRepository;
use App\Repositories\Course\ICourseRepository;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\Service;
use Illuminate\Support\Collection;
use RuntimeException;

class CartService extends Service implements ICartService
{
    protected ICartRepository $cartRepository;

    protected ICourseRepository $courseRepository;

    protected IEnrollmentService $enrollmentService;

    public function __construct(
        ICartRepository $repository,
        ICourseRepository $courseRepository,
        IEnrollmentService $enrollmentService,
    )
    {
        parent::__construct($repository);
        $this->cartRepository = $repository;
        $this->courseRepository = $courseRepository;
        $this->enrollmentService = $enrollmentService;
    }

    public function addCourse(int $userId, int $courseId, int $quantity = 1): Cart
    {
        $course = $this->courseRepository->getById($courseId);

        if (! $course || ! $course->status) {
            throw new RuntimeException('Course not found');
        }

        if ($this->enrollmentService->isEnrolled($userId, $courseId)) {
            throw new RuntimeException('Course already purchased');
        }

        return $this->cartRepository->addCourse($userId, $courseId, $quantity);
    }

    public function itemsByUserId(int $userId): Collection
    {
        return $this->cartRepository->itemsByUserId($userId);
    }

    public function countByUserId(int $userId): array
    {
        return $this->cartRepository->countByUserId($userId);
    }

    public function clearByUserId(int $userId): void
    {
        $this->cartRepository->clearByUserId($userId);
    }

    public function hasCourse(int $userId, int $courseId): bool
    {
        return $this->cartRepository->findByUserAndCourse($userId, $courseId) !== null;
    }
}
