<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UserUpdateRequest extends FormRequest
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
            'name' => ['sometimes', 'filled', 'string', 'max:255'],
            'phone' => ['sometimes', 'filled', 'string', 'max:30'],
            'birthday' => ['sometimes', 'filled', 'date'],
            'avatar' => [
                'sometimes',
                Rule::when(
                    is_string($this->input('avatar')),
                    ['filled', 'string'],
                    [File::image()->max(5 * 1024)],
                ),
            ],
            'city_id' => ['sometimes', 'filled', 'integer', 'exists:cities,id'],
            'password' => ['sometimes', 'filled', 'string', 'min:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.filled' => 'Name is required.',
            'birthday.date' => 'Birthday must be a valid date.',
            'phone.filled' => 'Phone is required.',
            'city_id.integer' => 'City id must be an integer.',
            'city_id.exists' => 'City not found.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }
}
