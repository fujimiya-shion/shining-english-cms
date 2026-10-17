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
        $query = $this
            ->model
            ->newQuery()
            ->scopes('active');

        $query = $this->applyQueryOption($query, $options);
        $query = $this->applyDefaultOrderIfMissing($query, $options);
        $paginator = $query->paginate(
            perPage: $options->getPerPage(), 
            page: $options->getPage(),
        );

        $paginator->getCollection()->transform(function (PaymentGatewaySetting $model) {
            $secure = $model->getAttribute('secure_settings');
            $model->setAttribute('settings', $secure);
            return $model;
        });

        return $paginator;
    }

    public function findBySlug(string $slug): ?PaymentGatewaySetting
    {
        return $this->model->newQuery()->where('slug', $slug)->first();
    }
}
