<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\DTO\User\Page\Home\HomeBannerActionButton;
use App\DTO\User\Page\Home\HomeBannerActionButtonTypes;
use App\DTO\User\Page\Home\HomeBannerHighlight;
use App\DTO\User\Page\Home\HomeBannerResponse;
use App\DTO\User\Page\Home\HomeCourseListingRenderBackgroundTypes;
use App\DTO\User\Page\Home\HomeCourseListingResponse;
use App\DTO\User\Page\Home\HomeCTAActionButton;
use App\DTO\User\Page\Home\HomeCTAActionButtonType;
use App\DTO\User\Page\Home\HomeCTAResponse;
use App\DTO\User\Page\Home\HomeFeatureCard;
use App\DTO\User\Page\Home\HomeFeatureResponse;
use App\DTO\User\Page\Home\HomeHeroActionButton;
use App\DTO\User\Page\Home\HomeHeroCTA;
use App\DTO\User\Page\Home\HomeHeroImageCTA;
use App\DTO\User\Page\Home\HomeHeroImageTag;
use App\DTO\User\Page\Home\HomeHeroResponse;
use App\DTO\User\Page\Home\HomeProcessResponse;
use App\DTO\User\Page\Home\HomeProcessStep;
use App\DTO\User\Page\Home\HomeResponse;
use App\DTO\User\Page\Home\HomeStatisticItem;
use App\DTO\User\Page\Home\HomeStatisticResponse;
use App\DTO\User\Page\Home\HomeTestimonialResponse;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class UserHomeRepository implements IUserHomeRepository
{
    public function getUserHomeData(?string $token): HomeResponse
    {
        $metrics = $this->resolveHomeMetrics();

        return new HomeResponse([
            $this->makeBannerPayload(),
            $this->makeHeroPayload($metrics),
            $this->makeCourseListingPayload($token),
            $this->makeFeaturePayload(),
            $this->makeProcessPayload(),
            $this->makeTestimonialPayload(),
            $this->makeStatisticPayload($metrics),
            $this->makeCtaPayload(),
        ]);
    }

    private function makeBannerPayload(): HomeBannerResponse
    {
        return new HomeBannerResponse(
            bannerLogo: '/images/app_logo.svg',
            bannerEyebrow: 'More Than English',
            bannerTitle: 'More Than English. Find Your Shine.',
            bannerDescription: 'Change the way you see English — and yourself.',
            bannerActionButtons: [
                new HomeBannerActionButton(
                    title: 'Trải nghiệm miễn phí',
                    action: '/blogs',
                    type: HomeBannerActionButtonTypes::PRIMARY,
                ),
                new HomeBannerActionButton(
                    title: 'Khám phá khóa học',
                    action: '/courses',
                    type: HomeBannerActionButtonTypes::SECONDARY,
                ),
            ],
            bannerHighlights: [
                new HomeBannerHighlight(
                    text: 'Xây dựng sự tự tin từ gốc.',
                    iconPath: null,
                    iconType: 'book-open',
                ),
                new HomeBannerHighlight(
                    text: '30 phút mỗi ngày.',
                    iconPath: null,
                    iconType: 'clock',
                ),
                new HomeBannerHighlight(
                    text: 'Để bạn dùng được tiếng Anh trong đời sống.',
                    iconPath: null,
                    iconType: 'award',
                ),
            ],
        );
    }

    /**
     * @param  array{learner_count:int, content_count:int, average_rating:float}  $metrics
     */
    private function makeHeroPayload(array $metrics): HomeHeroResponse
    {
        return new HomeHeroResponse(
            title: null,
            htmlTitle: 'More Than English.<br><span>Find Your Shine.</span>',
            description: 'Thay đổi cách bạn nhìn về tiếng Anh — và về chính mình.',
            actions: [
                new HomeHeroActionButton(
                    title: 'Trải nghiệm miễn phí',
                    action: '/blogs',
                    type: 'primary',
                ),
                new HomeHeroActionButton(
                    title: 'Khám phá khóa học',
                    action: '/courses',
                    type: 'secondary',
                ),
            ],
            ctas: [
                new HomeHeroCTA(
                    title: $this->formatCompactNumber($metrics['learner_count']),
                    description: 'Người Học Đã Theo',
                ),
                new HomeHeroCTA(
                    title: $this->formatRating($metrics['average_rating']),
                    description: 'Đánh Giá Thật',
                ),
            ],
            image: '/images/home/hero.png',
            imageTags: [
                new HomeHeroImageTag(
                    text: '15 phút/bài',
                    hexBgColor: '#FFFFFF',
                    hexTextColor: '#172B4D',
                ),
                new HomeHeroImageTag(
                    text: 'Bài mới hằng tuần',
                    hexBgColor: '#F5A400',
                    hexTextColor: '#FFFFFF',
                ),
            ],
            imageCTA: new HomeHeroImageCTA(
                icon: 'rocket',
                title: 'Học trực tuyến cùng người dạy',
                description: 'Mình trực tiếp phản hồi & cập nhật bài mới',
            ),
        );
    }

    private function makeCourseListingPayload(?string $token): HomeCourseListingResponse
    {
        $currentUser = $this->resolveCurrentUser($token);
        $courses = Course::query()
            ->scopes(['active'])
            ->with([
                'category:id,name',
                'level:id,name',
            ])
            ->withCardMetrics()
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        $enrolledCourseIds = [];
        if ($currentUser instanceof User && $courses->isNotEmpty()) {
            $enrolledCourseIds = Enrollment::query()
                ->where('user_id', $currentUser->id)
                ->whereIn('course_id', $courses->pluck('id')->all())
                ->pluck('course_id')
                ->map(static fn (mixed $courseId): int => (int) $courseId)
                ->all();
        }

        $courses->each(function (Course $course) use ($enrolledCourseIds): void {
            $course->setAttribute('enrolled', in_array((int) $course->id, $enrolledCourseIds, true));
        });

        return new HomeCourseListingResponse(
            title: 'Khóa Học Mình Tự Làm',
            description: 'Nội dung tự quay – tự dạy, tập trung vào hiệu quả thực tế',
            courses: $courses->all(),
            renderBackgroundType: HomeCourseListingRenderBackgroundTypes::FRONTEND,
        );
    }

    private function makeFeaturePayload(): HomeFeatureResponse
    {
        return new HomeFeatureResponse(
            eyebrow: 'Học theo phong cách dễ hiểu',
            title: 'Vì Sao Nên Học Ở Đây?',
            description: 'Một người làm – một phong cách dạy, nhất quán và dễ theo',
            items: [
                new HomeFeatureCard(
                    title: 'Lộ trình cá nhân',
                    description: 'Từng bài được sắp xếp rõ ràng để bạn học đều và chắc',
                    iconType: 'book-open',
                    badgeText: 'Nổi bật',
                    tagText: 'Dễ theo – thực tế',
                ),
                new HomeFeatureCard(
                    title: 'Do một người hướng dẫn',
                    description: 'Tôi tự quay, tự dạy và theo sát từng nội dung học',
                    iconType: 'users',
                    badgeText: 'Nổi bật',
                    tagText: 'Dễ theo – thực tế',
                ),
                new HomeFeatureCard(
                    title: 'Bài tập thực chiến',
                    description: 'Bài luyện nói – viết – phản xạ được cập nhật thường xuyên',
                    iconType: 'check-circle',
                    badgeText: 'Nổi bật',
                    tagText: 'Dễ theo – thực tế',
                ),
                new HomeFeatureCard(
                    title: 'Học theo tốc độ của bạn',
                    description: 'Xem video bất cứ lúc nào, tua lại phần khó và học chậm',
                    iconType: 'clock',
                    badgeText: 'Nổi bật',
                    tagText: 'Dễ theo – thực tế',
                ),
                new HomeFeatureCard(
                    title: 'Tiến bộ đo được',
                    description: 'Theo dõi điểm số và kỹ năng bạn cải thiện mỗi tuần',
                    iconType: 'award',
                    badgeText: 'Nổi bật',
                    tagText: 'Dễ theo – thực tế',
                ),
                new HomeFeatureCard(
                    title: 'Hỗ trợ trực tiếp',
                    description: 'Nhắn mình bất cứ lúc nào khi cần gỡ vướng bài học',
                    iconType: 'message-circle',
                    badgeText: 'Nổi bật',
                    tagText: 'Dễ theo – thực tế',
                ),
            ],
        );
    }

    private function makeProcessPayload(): HomeProcessResponse
    {
        return new HomeProcessResponse(
            title: 'Học Kiểu Thực Tế',
            description: 'Chọn khóa, học theo video, luyện tập và nhận phản hồi',
            steps: [
                new HomeProcessStep(
                    label: 'Bước 1',
                    title: 'Chọn Khóa Học',
                    description: 'Lựa chọn khóa học phù hợp với mục tiêu và trình độ của bạn',
                    iconType: 'book-open',
                ),
                new HomeProcessStep(
                    label: 'Bước 2',
                    title: 'Học & Thực Hành',
                    description: 'Xem video, làm bài tập và luyện nói theo bài',
                    iconType: 'check-circle',
                ),
                new HomeProcessStep(
                    label: 'Bước 3',
                    title: 'Nhận Phản Hồi',
                    description: 'Gửi bài, mình xem và góp ý cách học nhanh hơn',
                    iconType: 'message-circle',
                ),
                new HomeProcessStep(
                    label: 'Bước 4',
                    title: 'Ghi Nhận Tiến Bộ',
                    description: 'Theo dõi kỹ năng bạn cải thiện mỗi tuần',
                    iconType: 'award',
                ),
            ],
            tags: [
                'Học linh hoạt mỗi ngày',
                'Bài tập vui, dễ nhớ',
                'Theo dõi tiến bộ rõ ràng',
            ],
        );
    }

    private function makeTestimonialPayload(): HomeTestimonialResponse
    {
        $reviews = CourseReview::query()
            ->with([
                'user:id,name,avatar',
                'course:id,name',
            ])
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->where('rating', '>=', 4)
            ->latest()
            ->limit(3)
            ->get()
            ->all();

        return new HomeTestimonialResponse(
            title: 'Người Học Nói Gì?',
            description: 'Những phản hồi thật từ người học sau khi theo lộ trình',
            reviews: $reviews,
        );
    }

    /**
     * @param  array{learner_count:int, content_count:int, average_rating:float}  $metrics
     */
    private function makeStatisticPayload(array $metrics): HomeStatisticResponse
    {
        return new HomeStatisticResponse(
            items: [
                new HomeStatisticItem(
                    value: $this->formatCompactNumber($metrics['learner_count']),
                    label: 'Người Học Đang Theo',
                ),
                new HomeStatisticItem(
                    value: $this->formatCompactNumber($metrics['content_count']),
                    label: 'Video & Bài Luyện',
                ),
                new HomeStatisticItem(
                    value: $this->formatRating($metrics['average_rating']),
                    label: 'Điểm Đánh Giá',
                ),
                new HomeStatisticItem(
                    value: '24/7',
                    label: 'Phản Hồi Linh Hoạt',
                ),
            ],
        );
    }

    /**
     * @return array{learner_count:int, content_count:int, average_rating:float}
     */
    private function resolveHomeMetrics(): array
    {
        $learnerCount = (int) Course::query()
            ->scopes(['active'])
            ->sum('learned');

        $contentCount = (int) Lesson::query()
            ->whereHas('course', static fn ($query) => $query->active())
            ->count();

        $averageRating = (float) (CourseReview::query()
            ->whereNotNull('rating')
            ->avg('rating') ?? 0);

        return [
            'learner_count' => $learnerCount > 0 ? $learnerCount : 10000,
            'content_count' => $contentCount > 0 ? $contentCount : 50,
            'average_rating' => $averageRating > 0 ? $averageRating : 4.8,
        ];
    }

    private function resolveCurrentUser(?string $token): ?User
    {
        $user = request()->user();
        if ($user instanceof User) {
            return $user;
        }

        if (! is_string($token) || trim($token) === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        return $accessToken?->tokenable instanceof User ? $accessToken->tokenable : null;
    }

    private function makeCtaPayload(): HomeCTAResponse
    {
        return new HomeCTAResponse(
            title: 'Sẵn Sàng Học Theo Cách Dễ Hiểu?',
            description: 'Tự học nhưng không cô đơn – mình sẽ theo sát từng bước',
            actionButtons: [
                new HomeCTAActionButton(
                    title: 'Khám Phá Khóa Học',
                    action: '/courses',
                    type: HomeCTAActionButtonType::PRIMARY,
                ),
                new HomeCTAActionButton(
                    title: 'Xem Câu Hỏi Thường Gặp',
                    action: '/faq',
                    type: HomeCTAActionButtonType::SECONDARY,
                ),
            ],
        );
    }

    private function formatCompactNumber(int|string $number): string
    {
        if (is_string($number)) {
            return $number;
        }

        if ($number >= 1000) {
            return floor($number / 1000).'K+';
        }

        return $number.'+';
    }

    private function formatRating(int|float|null|string $rating): string
    {
        if (is_string($rating)) {
            return $rating;
        }

        $rating = round((float) ($rating ?? 0), 1);

        return $rating.'★';
    }
}
