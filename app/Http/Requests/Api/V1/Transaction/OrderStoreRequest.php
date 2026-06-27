<?php

namespace App\Http\Requests\Api\V1\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:cart,buy_now'],
            'payment_method' => ['nullable', 'string', 'in:cod,payos,star'],
            'course_id' => ['required_if:type,buy_now', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'buyer_email' => ['nullable', 'email:rfc', 'max:255'],
            'buyer_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Order type is required.',
            'type.in' => 'Order type must be cart or buy_now.',
            'payment_method.in' => 'Payment method must be cod, payos, or star.',
            'course_id.required_if' => 'Course id is required for buy now.',
            'course_id.integer' => 'Course id must be an integer.',
            'quantity.integer' => 'Quantity must be an integer.',
            'quantity.min' => 'Quantity must be at least 1.',
            'buyer_name.string' => 'Buyer name must be a string.',
            'buyer_email.email' => 'Buyer email must be a valid email address.',
            'buyer_phone.string' => 'Buyer phone must be a string.',
        ];
    }
}
