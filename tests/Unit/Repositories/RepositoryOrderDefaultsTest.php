<?php

use App\Models\Category;
use App\Models\Contact;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\Quiz;
use App\Models\User;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Contact\ContactRepository;
use App\Repositories\Course\CourseRepository;
use App\Repositories\Lesson\LessonRepository;
use App\Repositories\Order\OrderRepository;
use App\Repositories\Quiz\QuizRepository;
use App\Repositories\User\UserRepository;

it('course repository defaults to order desc', function (): void {
    $repo = new CourseRepository(new Course, Mockery::mock(\App\Repositories\Category\ICategoryRepository::class));
    expect(invokeProtectedMethod($repo, 'getDefaultOrderBy'))->toBe('order');
    expect(invokeProtectedMethod($repo, 'getDefaultOrderDirection'))->toBe('desc');
});

it('lesson repository defaults to order desc', function (): void {
    $repo = new LessonRepository(new Lesson);
    expect(invokeProtectedMethod($repo, 'getDefaultOrderBy'))->toBe('order');
    expect(invokeProtectedMethod($repo, 'getDefaultOrderDirection'))->toBe('desc');
});

it('order repository defaults to order desc', function (): void {
    $repo = new OrderRepository(new Order);
    expect(invokeProtectedMethod($repo, 'getDefaultOrderBy'))->toBe('order');
    expect(invokeProtectedMethod($repo, 'getDefaultOrderDirection'))->toBe('desc');
});

it('quiz repository defaults to order desc', function (): void {
    $repo = new QuizRepository(new Quiz);
    expect(invokeProtectedMethod($repo, 'getDefaultOrderBy'))->toBe('order');
    expect(invokeProtectedMethod($repo, 'getDefaultOrderDirection'))->toBe('desc');
});

it('category repository defaults to order desc', function (): void {
    $repo = new CategoryRepository(new Category);
    expect(invokeProtectedMethod($repo, 'getDefaultOrderBy'))->toBe('order');
    expect(invokeProtectedMethod($repo, 'getDefaultOrderDirection'))->toBe('desc');
});

it('contact repository defaults to order desc', function (): void {
    $repo = new ContactRepository(new Contact);
    expect(invokeProtectedMethod($repo, 'getDefaultOrderBy'))->toBe('order');
    expect(invokeProtectedMethod($repo, 'getDefaultOrderDirection'))->toBe('desc');
});

it('user repository defaults to order desc', function (): void {
    $repo = new UserRepository(new User);
    expect(invokeProtectedMethod($repo, 'getDefaultOrderBy'))->toBe('order');
    expect(invokeProtectedMethod($repo, 'getDefaultOrderDirection'))->toBe('desc');
});
