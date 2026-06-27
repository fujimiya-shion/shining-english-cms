<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Integrations\Payments\Contracts\PaymentStrategy;
use App\Integrations\Payments\DTO\PaymentInitializationResult;
use App\Integrations\Payments\Strategies\CodPaymentStrategy;
use App\Integrations\Payments\Strategies\PayosPaymentStrategy;
use App\Integrations\Payments\Support\PayosSignature;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Order\IOrderRepository;
use App\ValueObjects\CheckoutCustomerData;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('creates checkout action from payment initialization result', function (): void {
    expect(PaymentInitializationResult::none()->toCheckoutAction())->toBeNull();

    $result = PaymentInitializationResult::redirect('https://checkout.test', ['provider' => 'payos']);
    $action = $result->toCheckoutAction();

    expect($action?->toArray())->toBe([
        'type' => 'redirect',
        'url' => 'https://checkout.test',
        'metadata' => ['provider' => 'payos'],
    ]);
});

it('handles cod payment strategy as no-op strategy', function (): void {
    $order = new Order(['total_amount' => 1000]);
    $strategy = new CodPaymentStrategy;

    expect($strategy)->toBeInstanceOf(PaymentStrategy::class);
    expect($strategy->method())->toBe(PaymentMethod::Cod);
    expect($strategy->initialize($order, new CheckoutCustomerData)->toCheckoutAction())->toBeNull();
    expect($strategy->refresh($order))->toBe($order);
    expect($strategy->cancel($order, 'reason'))->toBe($order);
    expect($strategy->handleWebhook([]))->toBeNull();
});

it('signs and verifies payos payloads', function (): void {
    $data = [
        'nullable' => null,
        'enabled' => true,
        'disabled' => false,
        'amount' => 1000,
    ];

    $signature = PayosSignature::sign($data, 'checksum');

    expect($signature)->toBeString();
    expect(PayosSignature::verify($data, strtoupper($signature), 'checksum'))->toBeTrue();
    expect(PayosSignature::verify($data, 'bad-signature', 'checksum'))->toBeFalse();
});

it('initializes payos payment and persists checkout metadata', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
        'app.frontend_app_url' => 'https://frontend.test',
    ]);

    Http::fake([
        'https://payos.test/v2/payment-requests' => Http::response([
            'code' => '00',
            'data' => [
                'checkoutUrl' => 'https://checkout.test/123',
                'paymentLinkId' => 'plink_123',
                'status' => 'PENDING',
                'qrCode' => 'qr',
            ],
        ], 200),
    ]);

    $order = new class extends Order
    {
        public bool $saved = false;

        public function save(array $options = []): bool
        {
            $this->saved = true;

            return true;
        }
    };
    $order->id = 123;
    $order->total_amount = 250000;
    $item = new OrderItem([
        'quantity' => 2,
        'price' => 125000,
    ]);
    $order->setRelation('items', collect([$item]));

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    $result = $strategy->initialize($order, new CheckoutCustomerData('Learner', 'learner@example.com', '0909000000'));

    expect($result->toCheckoutAction()?->toArray()['url'])->toBe('https://checkout.test/123');
    expect($order->saved)->toBeTrue();
    expect($order->payment_reference)->toBe('plink_123');
    expect($order->payment_checkout_url)->toBe('https://checkout.test/123');
});

it('returns no payos action for zero amount orders', function (): void {
    $order = new Order(['total_amount' => 0]);
    $order->id = 1;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));

    expect($strategy->initialize($order, new CheckoutCustomerData)->toCheckoutAction())->toBeNull();
});

it('handles payos webhook status mapping', function (): void {
    config(['payos.checksum_key' => 'checksum']);

    $order = new class extends Order
    {
        public bool $saved = false;

        public function save(array $options = []): bool
        {
            $this->saved = true;

            return true;
        }

        public function fresh($with = []): ?Order
        {
            return $this;
        }
    };
    $order->id = 123;
    $order->status = OrderStatus::Pending;
    $order->payment_method = PaymentMethod::Payos;
    $order->payment_metadata = [];

    $repository = Mockery::mock(IOrderRepository::class);
    $repository->shouldReceive('getById')
        ->once()
        ->with(123, ['items.course'])
        ->andReturn($order);

    $data = [
        'orderCode' => 123,
        'code' => '00',
        'paymentLinkId' => 'plink_123',
    ];
    $signature = PayosSignature::sign($data, 'checksum');

    $result = (new PayosPaymentStrategy($repository))->handleWebhook([
        'data' => $data,
        'signature' => $signature,
    ]);

    expect($result)->toBe($order);
    expect($order->saved)->toBeTrue();
    expect($order->status)->toBe(OrderStatus::Paid);
    expect($order->payment_reference)->toBe('plink_123');
});
