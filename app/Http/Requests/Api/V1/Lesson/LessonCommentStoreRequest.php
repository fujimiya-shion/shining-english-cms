<?php

namespace App\Http\Requests\Api\V1\Lesson;

use Illuminate\Foundation\Http\FormRequest;

class LessonCommentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Content is required.',
            'content.string' => 'Content must be a string.',
        ];
    }
}
