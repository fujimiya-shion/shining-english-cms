<?php

namespace App\Http\Requests\Api\V1\User;

use App\Enums\ThirdPartyAuthProviders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ThirdPartyLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'access_token' => ['required', 'string'],
            'provider' => ['required', Rule::enum(ThirdPartyAuthProviders::class)],
            'device_identifier' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
            'platform' => ['nullable', 'string'],
            'ip_address' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string'],
        ];
    }
}
