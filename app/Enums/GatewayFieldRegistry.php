<?php

declare(strict_types=1);

namespace App\Enums;

class GatewayFieldRegistry
{
    /**
     * @var array<string, array<int, array{key: string, label: string, type: string, required: bool, sensitive: bool}>>
     */
    private const FIELDS = [
        'payos' => [
            ['key' => 'webhook_url', 'label' => 'Webhook Callback URL', 'type' => 'url', 'required' => true, 'sensitive' => false],
            ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true, 'sensitive' => false],
            ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'sensitive' => true],
            ['key' => 'checksum_key', 'label' => 'Checksum Key', 'type' => 'password', 'required' => true, 'sensitive' => true],
            ['key' => 'base_url', 'label' => 'Base URL', 'type' => 'url', 'required' => false, 'sensitive' => false],
        ],
        'cod' => [
            ['key' => 'instructions', 'label' => 'Hướng dẫn thanh toán', 'type' => 'textarea', 'required' => false, 'sensitive' => false],
        ],
        'star' => [],
    ];

    /**
     * @return array<int, array{key: string, label: string, type: string, required: bool, sensitive: bool}>
     */
    public static function getFieldsForGateway(GatewayType $type): array
    {
        return self::FIELDS[$type->value] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function getRequiredKeys(GatewayType $type): array
    {
        return array_column(
            array_filter(self::getFieldsForGateway($type), static fn (array $field): bool => $field['required']),
            'key',
        );
    }

    public static function getField(GatewayType $type, string $key): ?array
    {
        foreach (self::getFieldsForGateway($type) as $field) {
            if ($field['key'] === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Return options array suitable for Select component.
     *
     * @return array<string, string>
     */
    public static function getSelectableOptions(GatewayType $type): array
    {
        $options = [];
        foreach (self::getFieldsForGateway($type) as $field) {
            $options[$field['key']] = $field['label'];
        }

        return $options;
    }
}
