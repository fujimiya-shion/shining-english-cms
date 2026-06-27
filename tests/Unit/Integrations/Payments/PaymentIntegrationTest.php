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

it('returns payos payment method', function (): void {
    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    expect($strategy->method())->toBe(PaymentMethod::Payos);
});

it('throws error when payos initialize response fails', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
        'app.frontend_app_url' => 'https://frontend.test',
    ]);

    Http::fake(['https://payos.test/v2/payment-requests' => Http::response('', 500)]);

    $order = new Order(['total_amount' => 250000]);
    $order->id = 456;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));

    expect(fn () => $strategy->initialize($order, new CheckoutCustomerData))
        ->toThrow(RuntimeException::class, 'Failed to create payOS payment link.');
});

it('throws error when payos initialize returns no checkout URL', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
        'app.frontend_app_url' => 'https://frontend.test',
    ]);

    Http::fake(['https://payos.test/v2/payment-requests' => Http::response(['code' => '00', 'data' => null], 200)]);

    $order = new Order(['total_amount' => 250000]);
    $order->id = 456;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));

    expect(fn () => $strategy->initialize($order, new CheckoutCustomerData))
        ->toThrow(RuntimeException::class, 'payOS did not return a checkout URL.');
});

it('handles payos webhook with invalid signature', function (): void {
    $order = new Order(['total_amount' => 1000]);
    $order->id = 999;

    $repository = Mockery::mock(IOrderRepository::class);

    $strategy = new PayosPaymentStrategy($repository);

    expect(fn () => $strategy->handleWebhook(['data' => ['orderCode' => 999], 'signature' => 'bad']))
        ->toThrow(RuntimeException::class, 'Invalid payOS webhook signature');
});

it('handles payos webhook with missing order', function (): void {
    config(['payos.checksum_key' => 'checksum']);

    $data = ['orderCode' => 888, 'code' => '00'];
    $signature = PayosSignature::sign($data, 'checksum');

    $repository = Mockery::mock(IOrderRepository::class);
    $repository->shouldReceive('getById')->with(888, ['items.course'])->andReturnNull();

    $strategy = new PayosPaymentStrategy($repository);

    expect($strategy->handleWebhook(['data' => $data, 'signature' => $signature]))->toBeNull();
});

it('handles payos webhook with missing orderCode', function (): void {
    config(['payos.checksum_key' => 'checksum']);

    $data = [];
    $signature = PayosSignature::sign($data, 'checksum');

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));

    expect($strategy->handleWebhook(['data' => $data, 'signature' => $signature]))->toBeNull();
});

it('handles payos webhook with pending status', function (): void {
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
    $order->id = 777;
    $order->total_amount = 1000;
    $order->status = OrderStatus::Pending;
    $order->payment_method = PaymentMethod::Payos;
    $order->payment_metadata = [];

    $repository = Mockery::mock(IOrderRepository::class);
    $repository->shouldReceive('getById')->with(777, ['items.course'])->andReturn($order);

    $data = ['orderCode' => 777, 'code' => '01'];
    $signature = PayosSignature::sign($data, 'checksum');

    $result = (new PayosPaymentStrategy($repository))->handleWebhook(['data' => $data, 'signature' => $signature]);

    expect($result->status)->toBe(OrderStatus::Pending);
});

it('handles payos webhook with cancelled status', function (): void {
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
    $order->id = 456;
    $order->status = OrderStatus::Pending;
    $order->payment_method = PaymentMethod::Payos;
    $order->payment_metadata = [];

    $repository = Mockery::mock(IOrderRepository::class);
    $repository->shouldReceive('getById')->with(456, ['items.course'])->andReturn($order);

    $data = ['orderCode' => 456, 'code' => '01', 'desc' => 'CANCEL'];
    $signature = PayosSignature::sign($data, 'checksum');

    $result = (new PayosPaymentStrategy($repository))->handleWebhook([
        'data' => $data,
        'signature' => $signature,
    ]);

    expect($result->status)->toBe(OrderStatus::Cancelled);
});

it('returns order when payos refresh has no payment reference', function (): void {
    $order = new Order(['total_amount' => 1000]);
    $order->payment_method = PaymentMethod::Cod;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));

    expect($strategy->refresh($order))->toBe($order);
});

it('returns order when payos cancel has no payment reference', function (): void {
    $order = new Order(['total_amount' => 1000]);
    $order->payment_method = PaymentMethod::Cod;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));

    expect($strategy->cancel($order, 'test'))->toBe($order);
});

it('returns order when payos refresh response fails', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
    ]);

    Http::fake(['https://payos.test/v2/payment-requests/plink_fail' => Http::response('', 500)]);

    $order = new Order(['total_amount' => 1000, 'payment_method' => PaymentMethod::Payos, 'payment_reference' => 'plink_fail']);
    $order->id = 888;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    expect($strategy->refresh($order))->toBe($order);
});

it('returns order when payos refresh response has no data', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
    ]);

    Http::fake(['https://payos.test/v2/payment-requests/plink_nodata' => Http::response(['code' => '00', 'data' => null], 200)]);

    $order = new Order(['total_amount' => 1000, 'payment_method' => PaymentMethod::Payos, 'payment_reference' => 'plink_nodata']);
    $order->id = 889;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    expect($strategy->refresh($order))->toBe($order);
});

it('throws when payos cancel response fails', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
    ]);

    Http::fake(['https://payos.test/v2/payment-requests/plink_cancel_fail/cancel' => Http::response('', 500)]);

    $order = new Order(['total_amount' => 1000, 'payment_method' => PaymentMethod::Payos, 'payment_reference' => 'plink_cancel_fail']);
    $order->id = 890;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    expect(fn () => $strategy->cancel($order, 'fail'))
        ->toThrow(RuntimeException::class, 'Failed to cancel payOS payment link.');
});

it('throws when payos webhook payload has no signature', function (): void {
    config(['payos.checksum_key' => 'checksum']);

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    expect(fn () => $strategy->handleWebhook([]))
        ->toThrow(RuntimeException::class, 'Invalid payOS webhook payload.');
});

it('throws when payos checksum key is not configured', function (): void {
    config(['payos.client_id' => '', 'payos.api_key' => '', 'payos.checksum_key' => '', 'payos.base_url' => '']);

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    expect(fn () => $strategy->handleWebhook([
        'signature' => 'signature',
        'data' => ['orderCode' => 123],
    ]))
        ->toThrow(RuntimeException::class, 'PAYOS_CHECKSUM_KEY is missing.');
});

it('refreshes payos order with successful GET response', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
    ]);

    Http::fake(['https://payos.test/v2/payment-requests/plink_999' => Http::response([
        'code' => '00',
        'data' => ['status' => 'PAID', 'paymentLinkId' => 'plink_999'],
    ], 200)]);

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
    $order->id = 999;
    $order->total_amount = 200000;
    $order->payment_method = PaymentMethod::Payos;
    $order->payment_reference = 'plink_999';
    $order->payment_metadata = [];
    $order->status = OrderStatus::Pending;

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    $result = $strategy->refresh($order);

    expect($result->status)->toBe(OrderStatus::Paid);
    expect($order->saved)->toBeTrue();
});

it('cancels payos order with successful POST response', function (): void {
    config([
        'payos.client_id' => 'client',
        'payos.api_key' => 'api-key',
        'payos.checksum_key' => 'checksum',
        'payos.base_url' => 'https://payos.test',
    ]);

    Http::fake(['https://payos.test/v2/payment-requests/plink_555/cancel' => Http::response([
        'code' => '00',
        'data' => ['status' => 'CANCELLED'],
    ], 200)]);

    $order = new class extends Order
    {
        public bool $saved = false;

        public function save(array $options = []): bool
        {
            $this->saved = true;

            return true;
        }
    };
    $order->id = 555;
    $order->total_amount = 150000;
    $order->payment_method = PaymentMethod::Payos;
    $order->payment_reference = 'plink_555';
    $order->payment_metadata = [];

    $strategy = new PayosPaymentStrategy(Mockery::mock(IOrderRepository::class));
    $result = $strategy->cancel($order, 'User cancelled');

    expect($result)->toBe($order);
    expect($order->saved)->toBeTrue();
});
