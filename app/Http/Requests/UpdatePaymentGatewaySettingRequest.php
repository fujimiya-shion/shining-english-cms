<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GatewayFieldRegistry;
use App\Enums\GatewayType;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentGatewaySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $gatewaySlug = $this->route('record')?->slug ?? '';

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'settings_fields' => ['sometimes', 'array'],
            'settings_fields.*.key' => ['required_with:settings_fields', 'string', 'max:255'],
            'settings_fields.*.value' => ['required_with:settings_fields', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings_fields.*.key.required_with' => 'Tên trường không được để trống.',
            'settings_fields.*.value.required_with' => 'Giá trị không được để trống.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $gatewaySlug = $this->route('record')?->slug ?? '';
        $gatewayType = GatewayType::tryFrom($gatewaySlug);

        if ($gatewayType === null) {
            return;
        }

        $requiredKeys = GatewayFieldRegistry::getRequiredKeys($gatewayType);
        $submittedKeys = array_column($this->settings_fields ?? [], 'key');

        $missing = array_diff($requiredKeys, $submittedKeys);
        if ($missing !== []) {
            $fieldLabels = GatewayFieldRegistry::getSelectableOptions($gatewayType);
            $missingNames = implode(', ', array_map(static fn (string $key): string => $fieldLabels[$key] ?? $key, $missing));

            $this->merge([
                'settings_fields' => array_merge($this->settings_fields ?? [], array_map(static fn (string $key): array => [
                    'key' => $key,
                    'value' => '',
                ], $missing)),
            ]);

            $this->failedValidation($this->getValidatorInstance());
        }
    }
}
