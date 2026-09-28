<?php

declare(strict_types=1);

namespace App\Enums;

class GatewayFieldRegistry
{
    /**
     * @var array<string, array<int, array{key: string, label: string, type: string, required: bool, sensitive: bool}>>
     */
    private const FIELDS = [
        ['key' => 'webhook_url', 'label' => 'Webhook Callback URL', 'type' => 'url', 'required' => true, 'sensitive' => false],
        ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true, 'sensitive' => false],
        ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true, 'sensitive' => true],
        ['key' => 'checksum_key', 'label' => 'Checksum Key', 'type' => 'password', 'required' => true, 'sensitive' => true],
        ['key' => 'base_url', 'label' => 'Base URL', 'type' => 'url', 'required' => false, 'sensitive' => false],
        ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => false],
    ];

    /**
     * @return array<int, string>
     */
    public static function getRequiredKeys(GatewayType $type): array
    {
        return array_column(
            array_filter(self::FIELDS, static fn (array $field): bool => $field['required']),
            'key',
        );
    }

    public static function getField(string $key): ?array
    {
        foreach (self::FIELDS as $field) {
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
    public static function getSelectableOptions(): array
    {
        $options = [];
        foreach (self::FIELDS as $field) {
            $key = (string) $field['key'];
            $options[$key] = $field['label'];
        }

        return $options;
    }
}
