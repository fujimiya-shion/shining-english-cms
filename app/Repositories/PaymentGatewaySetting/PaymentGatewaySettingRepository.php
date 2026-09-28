<?php

declare(strict_types=1);

namespace App\Repositories\PaymentGatewaySetting;

use App\Models\PaymentGatewaySetting;
use App\Repositories\Repository;
use App\ValueObjects\QueryOption;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentGatewaySettingRepository extends Repository implements IPaymentGatewaySettingRepository
{
    public function __construct(PaymentGatewaySetting $model)
    {
        parent::__construct($model);
    }

    public function paginateAll(QueryOption|null $options = null): LengthAwarePaginator {
        $query = $this->model->newQuery()->scopes('active');
        $query = $this->applyQueryOption($query, $options);
        $query = $this->applyDefaultOrderIfMissing($query, $options);
        return $query->paginate(
            perPage: $options->getPerPage(), 
            page: $options->getPage(),
        );
    }

    public function findBySlug(string $slug): ?PaymentGatewaySetting
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }
}
