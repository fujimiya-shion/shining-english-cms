<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StarTransactionType;
use App\Http\Controllers\Api\ApiController;
use App\Services\Course\ICourseService;
use App\Services\Enrollment\IEnrollmentService;
use App\Services\Order\IOrderService;
use App\Services\Star\IStarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StarController extends ApiController
{
    public function __construct(
        protected IStarService $starService,
        protected IOrderService $orderService,
        protected IEnrollmentService $enrollmentService,
        protected ICourseService $courseService,
    ) {}

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success(data: [
            'balance' => $this->starService->getBalance((int) $user->id),
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $user = $request->user();

        $alreadyCheckedIn = DB::table('daily_check_ins')
            ->where('user_id', $user->id)
            ->whereDate('checked_in_at', Carbon::today())
            ->exists();

        if ($alreadyCheckedIn) {
            return $this->error(message: 'Bạn đã check-in hôm nay rồi.', statusCode: 422);
        }

        $rewardAmount = (int) config('const.star.daily_checkin', 1);

        DB::transaction(function () use ($user, $rewardAmount): void {
            DB::table('daily_check_ins')->insert([
                'user_id' => $user->id,
                'checked_in_at' => now(),
                'reward_amount' => $rewardAmount,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->starService->addStarByUserId(
                $rewardAmount,
                (int) $user->id,
                __('Check-in hàng ngày'),
                StarTransactionType::DailyCheckin,
            );
        });

        return $this->success(
            message: 'Check-in thành công!',
            data: [
                'reward' => $rewardAmount,
                'balance' => $this->starService->getBalance((int) $user->id),
            ],
        );
    }

    public function payForCourse(Request $request, int $courseId): JsonResponse
    {
        $user = $request->user();

        $course = $this->courseService->getById($courseId);

        if (! $course) {
            return $this->notfound();
        }

        if (! (bool) ($course->allow_star_payment ?? false) || (int) ($course->star_price ?? 0) <= 0) {
            return $this->error(message: 'Khóa học này không hỗ trợ thanh toán bằng sao.', statusCode: 422);
        }

        if ($this->enrollmentService->isEnrolled((int) $user->id, $courseId)) {
            return $this->success(message: 'Bạn đã ghi danh khóa học này rồi.', data: [
                'enrolled' => true,
            ]);
        }

        $result = DB::transaction(function () use ($user, $course): array {
            $spent = $this->starService->spendStarByUserId(
                amount: (int) $course->star_price,
                userId: (int) $user->id,
                message: sprintf('Thanh toán khóa học #%d: %s', $course->id, $course->name),
                type: StarTransactionType::StarPayment,
            );

            if (! $spent) {
                return ['success' => false, 'balance' => $this->starService->getBalance((int) $user->id)];
            }

            $this->orderService->createWithStarPayment((int) $user->id, (int) $course->id);

            return ['success' => true, 'balance' => $this->starService->getBalance((int) $user->id)];
        });

        if (! $result['success']) {
            return $this->error(
                message: 'Không đủ sao để mở khóa học này.',
                statusCode: 422,
                errors: [
                    'required_star' => (int) $course->star_price,
                    'star_balance' => $result['balance'],
                ],
            );
        }

        return $this->success(
            message: 'Mở khóa học thành công.',
            data: [
                'enrolled' => true,
                'star_balance' => $result['balance'],
            ],
        );
    }
}
