<?php

use App\Http\Controllers\Api\V1\Blog\BlogController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\City\CityController;
use App\Http\Controllers\Api\V1\Contact\ContactController;
use App\Http\Controllers\Api\V1\Course\CourseController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardController;
use App\Http\Controllers\Api\V1\Developer\DeveloperController;
use App\Http\Controllers\Api\V1\Lesson\LessonController;
use App\Http\Controllers\Api\V1\Lesson\LessonNoteController;
use App\Http\Controllers\Api\V1\QuizAttempt\QuizAttemptController;
use App\Http\Controllers\Api\V1\Transaction\OrderController;
use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Middleware\VerifyDeveloperToken;
use App\Http\Middleware\VerifyUserToken;
use Illuminate\Support\Facades\Route;

Route::prefix('/v1')->group(function () {

    Route::controller(DeveloperController::class)
        ->group(function () {
            Route::post('/access-token', 'accessToken');
        });

    Route::middleware(VerifyDeveloperToken::class)->group(function () {
        Route::controller(CourseController::class)
            ->prefix('/courses')
            ->group(function () {
                Route::match(
                    ['get', 'post'],
                    '/filter',
                    'filter',
                );
                Route::get('/filter-props', 'getFilterProps');
                Route::get('/', 'index');
                Route::get('/slug/{slug}', 'showBySlug');
                Route::get('/{id}', 'show');
            });

        Route::controller(BlogController::class)
            ->prefix('/blogs')
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/slug/{slug}', 'showBySlug');
            });

        Route::controller(CityController::class)
            ->prefix('/cities')
            ->group(function () {
                Route::get('/', 'index');
            });

        Route::controller(ContactController::class)
            ->prefix('/contact')
            ->group(function () {
                Route::post('/', 'store');
            });

        Route::controller(LessonController::class)
            ->prefix('/lessons')
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/{id}/video', 'video');
                Route::get('/{id}/documents/{documentIndex}/download', 'downloadDocument');
                Route::get('/{id}', 'show');
                Route::get('/{id}/quiz', 'quiz');
            });

        Route::controller(AuthController::class)
            ->prefix('/auth')
            ->group(function () {
                Route::post('/register', 'register');
                Route::post('/login', 'login');
                Route::post('/third-party-login', 'thirdPartyLogin');
                Route::post('/forgot-password', 'forgotPassword');
                Route::post('/reset-password', 'resetPassword');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(AuthController::class)
            ->prefix('/auth')
            ->group(function () {
                Route::get('/me', 'me');
                Route::post('/logout', 'logout');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(UserController::class)
            ->prefix('/user')
            ->group(function () {
                Route::post('/update', 'update');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(DashboardController::class)
            ->prefix('/dashboard')
            ->group(function () {
                Route::get('/overview', 'overview');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(CourseController::class)
            ->prefix('/courses')
            ->group(function () {
                Route::get('/{id}/access', 'access');
                Route::get('/{id}/learning-progress', 'learningProgress');
                Route::post('/{id}/lessons/{lessonId}/complete', 'completeLesson');
                Route::post('/{id}/current-lesson', 'setCurrentLesson');
                Route::post('/{id}/reviews', 'storeReview');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(LessonController::class)
            ->prefix('/lessons')
            ->group(function () {
                Route::post('/{id}/comments', 'storeComment');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(LessonNoteController::class)
            ->prefix('/lessons')
            ->group(function () {
                Route::get('/{id}/notes', 'indexByLesson');
                Route::post('/{id}/notes', 'store');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(LessonNoteController::class)
            ->prefix('/notes')
            ->group(function () {
                Route::get('/', 'index');
                Route::delete('/{id}', 'delete');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(QuizAttemptController::class)
            ->prefix('/quizzes/{quizId}/attempts')
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/latest', 'latest');
                Route::post('/', 'store');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(CartController::class)
            ->prefix('/cart')
            ->group(function () {
                Route::post('/items', 'store');
                Route::get('/items', 'items');
                Route::get('/count', 'count');
                Route::delete('/clear', 'clear');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(OrderController::class)
            ->prefix('/orders')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('/{id}', 'show');
                Route::post('/{id}/cancel', 'cancel');
            });

        Route::middleware(VerifyUserToken::class)
            ->controller(BlogController::class)
            ->prefix('/blogs')
            ->group(function () {
                Route::post('/{id}/unlock', 'unlock');
            });
    });

});
