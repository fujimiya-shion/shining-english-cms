<?php

use App\DTO\User\Page\Home\HomeBannerResponse;
use App\DTO\User\Page\Home\HomeCourseListingResponse;
use App\DTO\User\Page\Home\HomeCTAResponse;
use App\DTO\User\Page\Home\HomeFeatureResponse;
use App\DTO\User\Page\Home\HomeHeroResponse;
use App\DTO\User\Page\Home\HomeProcessResponse;
use App\DTO\User\Page\Home\HomeResponse;
use App\DTO\User\Page\Home\HomeStatisticResponse;
use App\DTO\User\Page\Home\HomeTestimonialResponse;
use Tests\TestCase;

uses(TestCase::class);

it('HomeResponse constructs and serializes', function (): void {
    $payloads = [];
    $dto = new HomeResponse(payloads: $payloads);

    expect($dto->toArray())->toBe(['payloads' => []]);
});

it('HomeBannerResponse constructs and serializes', function (): void {
    $dto = new HomeBannerResponse(
        bannerLogo: 'logo.svg',
        bannerEyebrow: 'Học tiếng Anh',
        bannerTitle: 'Chào mừng',
        bannerDescription: 'Mô tả',
        bannerActionButtons: [],
        bannerHighlights: [],
    );

    expect($dto->type())->toBe('banner');
    $data = $dto->data();
    expect($data['banner_title'])->toBe('Chào mừng');

    $array = $dto->toArray();
    expect($array['type'])->toBe('banner');
    expect($array['data']['banner_logo'])->toBe('logo.svg');
});

it('HomeCourseListingResponse constructs and serializes', function (): void {
    $dto = new HomeCourseListingResponse(
        title: 'Khóa học nổi bật',
        slug: 'noi-bat',
        courses: [],
        hexBgColors: ['#ff0'],
    );

    expect($dto->type())->toBe('course_listing');
    expect($dto->data()['title'])->toBe('Khóa học nổi bật');
    expect($dto->toArray()['data']['hex_bg_colors'])->toBe(['#ff0']);
});

it('HomeCTAResponse constructs and serializes', function (): void {
    $dto = new HomeCTAResponse(
        title: 'Đăng ký ngay',
        description: 'Bắt đầu học',
        actionButtons: [],
    );

    expect($dto->type())->toBe('cta');
    expect($dto->data()['title'])->toBe('Đăng ký ngay');
    expect($dto->toArray()['data']['action_buttons'])->toBe([]);
});

it('HomeFeatureResponse constructs and serializes', function (): void {
    $dto = new HomeFeatureResponse(
        eyebrow: 'Tính năng',
        title: 'Học mọi lúc',
        description: 'Mô tả tính năng',
        items: [],
    );

    expect($dto->type())->toBe('feature');
    expect($dto->data()['eyebrow'])->toBe('Tính năng');
    expect($dto->toArray()['data']['items'])->toBe([]);
});

it('HomeHeroResponse constructs and serializes', function (): void {
    $dto = new HomeHeroResponse(
        title: 'Hero Title',
        htmlTitle: '<strong>Hero</strong>',
        description: 'Hero description',
        actions: [],
        ctas: [],
        imageTags: [],
    );

    expect($dto->type())->toBe('hero');
    expect($dto->data()['title'])->toBe('Hero Title');
    expect($dto->toArray()['data']['html_title'])->toBe('<strong>Hero</strong>');
});

it('HomeProcessResponse constructs and serializes', function (): void {
    $dto = new HomeProcessResponse(
        title: 'Quy trình',
        description: '3 bước',
        steps: [],
        tags: ['tag1'],
    );

    expect($dto->type())->toBe('process');
    expect($dto->data()['title'])->toBe('Quy trình');
    expect($dto->toArray()['data']['tags'])->toBe(['tag1']);
});

it('HomeStatisticResponse constructs and serializes', function (): void {
    $dto = new HomeStatisticResponse(items: []);

    expect($dto->type())->toBe('statistic');
    expect($dto->data()['items'])->toBe([]);
    expect($dto->toArray()['data']['items'])->toBe([]);
});

it('HomeTestimonialResponse constructs and serializes', function (): void {
    $dto = new HomeTestimonialResponse(
        title: 'Cảm nhận',
        description: 'Học viên nói gì',
        reviews: [],
    );

    expect($dto->type())->toBe('testimonial');
    expect($dto->data()['title'])->toBe('Cảm nhận');
    expect($dto->toArray()['data']['reviews'])->toBe([]);
});
