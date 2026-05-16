<?php

namespace App\Providers;

use App\Integrations\Auth\Strategies\GoogleAuthStrategy;
use App\Models\Lesson;
use App\Observers\LessonObserver;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Cart\ICartRepository;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Category\ICategoryRepository;
use App\Repositories\Course\CourseRepository;
use App\Repositories\Course\ICourseRepository;
use App\Repositories\CourseReview\CourseReviewRepository;
use App\Repositories\CourseReview\ICourseReviewRepository;
use App\Repositories\Dashboard\DashboardRepository;
use App\Repositories\Dashboard\IDashboardRepository;
use App\Repositories\Developer\DeveloperRepository;
use App\Repositories\Developer\IDeveloperRepository;
use App\Repositories\Enrollment\EnrollmentRepository;
use App\Repositories\Enrollment\IEnrollmentRepository;
use App\Repositories\Lesson\ILessonRepository;
use App\Repositories\Lesson\LessonRepository;
use App\Repositories\LessonComment\ILessonCommentRepository;
use App\Repositories\LessonComment\LessonCommentRepository;
use App\Repositories\LessonNote\ILessonNoteRepository;
use App\Repositories\LessonNote\LessonNoteRepository;
use App\Repositories\Order\IOrderRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\OrderItem\IOrderItemRepository;
use App\Repositories\OrderItem\OrderItemRepository;
use App\Repositories\Quiz\IQuizRepository;
use App\Repositories\Quiz\QuizRepository;
use App\Repositories\Star\IStarRepository;
use App\Repositories\Star\StarRepository;
use App\Repositories\StarTransaction\IStarTransactionRepository;
use App\Repositories\StarTransaction\StarTransactionRepository;
use App\Repositories\User\IUserDeviceRepository;
use App\Repositories\User\IUserRepository;
use App\Repositories\User\UserDeviceRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\UserQuizAttempt\IUserQuizAttemptRepository;
use App\Repositories\UserQuizAttempt\UserQuizAttemptRepository;
use App\Services\Cart\CartService;
use App\Services\Cart\ICartService;
use App\Services\Category\CategoryService;
use App\Services\Category\ICategoryService;
use App\Services\Course\CourseService;
use App\Services\Course\ICourseService;
use App\Services\Developer\DeveloperService;
use App\Services\Developer\IDeveloperService;
use App\Services\CourseReview\CourseReviewService;
use App\Services\CourseReview\ICourseReviewService;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\IDashboardService;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\Lesson\ILessonService;
use App\Services\Lesson\LessonService;
use App\Services\LessonComment\ILessonCommentService;
use App\Services\LessonComment\LessonCommentService;
use App\Services\LessonAccess\ILessonAccessService;
use App\Services\LessonAccess\LessonAccessService;
use App\Services\LessonNote\ILessonNoteService;
use App\Services\LessonNote\LessonNoteService;
use App\Services\Order\IOrderService;
use App\Services\Order\OrderService;
use App\Services\OrderItem\IOrderItemService;
use App\Services\OrderItem\OrderItemService;
use App\Services\Quiz\IQuizService;
use App\Services\Quiz\QuizService;
use App\Services\Star\IStarService;
use App\Services\Star\StarService;
use App\Services\StarTransaction\IStarTransactionService;
use App\Services\StarTransaction\StarTransactionService;
use App\Services\User\IUserDeviceService;
use App\Services\User\IUserService;
use App\Services\User\UserDeviceService;
use App\Services\User\UserService;
use App\Services\UserQuizAttempt\IUserQuizAttemptService;
use App\Services\UserQuizAttempt\UserQuizAttemptService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ICartRepository::class, CartRepository::class);
        $this->app->bind(ICategoryRepository::class, CategoryRepository::class);
        $this->app->bind(IEnrollmentRepository::class, EnrollmentRepository::class);
        $this->app->bind(IOrderRepository::class, OrderRepository::class);
        $this->app->bind(IOrderItemRepository::class, OrderItemRepository::class);
        $this->app->bind(ICourseRepository::class, CourseRepository::class);
        $this->app->bind(ICourseReviewRepository::class, CourseReviewRepository::class);
        $this->app->bind(ILessonRepository::class, LessonRepository::class);
        $this->app->bind(ILessonCommentRepository::class, LessonCommentRepository::class);
        $this->app->bind(ILessonNoteRepository::class, LessonNoteRepository::class);
        $this->app->bind(IQuizRepository::class, QuizRepository::class);
        $this->app->bind(IStarRepository::class, StarRepository::class);
        $this->app->bind(IStarTransactionRepository::class, StarTransactionRepository::class);
        $this->app->bind(IUserQuizAttemptRepository::class, UserQuizAttemptRepository::class);
        $this->app->bind(IUserRepository::class, UserRepository::class);
        $this->app->bind(IUserDeviceRepository::class, UserDeviceRepository::class);
        $this->app->bind(IDeveloperRepository::class, DeveloperRepository::class);
        $this->app->bind(IDashboardRepository::class, DashboardRepository::class);

        $this->app->bind(ICartService::class, CartService::class);
        $this->app->bind(ICategoryService::class, CategoryService::class);
        $this->app->bind(IEnrollmentService::class, EnrollmentService::class);
        $this->app->bind(IOrderService::class, OrderService::class);
        $this->app->bind(IOrderItemService::class, OrderItemService::class);
        $this->app->bind(ICourseService::class, CourseService::class);
        $this->app->bind(ICourseReviewService::class, CourseReviewService::class);
        $this->app->bind(ILessonService::class, LessonService::class);
        $this->app->bind(ILessonCommentService::class, LessonCommentService::class);
        $this->app->bind(ILessonAccessService::class, LessonAccessService::class);
        $this->app->bind(ILessonNoteService::class, LessonNoteService::class);
        $this->app->bind(IQuizService::class, QuizService::class);
        $this->app->bind(IStarService::class, StarService::class);
        $this->app->bind(IStarTransactionService::class, StarTransactionService::class);
        $this->app->bind(IUserQuizAttemptService::class, UserQuizAttemptService::class);
        $this->app->bind(IUserService::class, UserService::class);
        $this->app->bind(IUserDeviceService::class, UserDeviceService::class);
        $this->app->bind(IDeveloperService::class, DeveloperService::class);
        $this->app->bind(IDashboardService::class, DashboardService::class);

        $this->app->instance(GoogleAuthStrategy::class, new GoogleAuthStrategy);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Lesson::observe(LessonObserver::class);
    }
}
