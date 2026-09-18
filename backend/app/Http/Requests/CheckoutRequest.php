<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checkout_token' => ['required', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.slug' => ['required', 'string', 'max:255', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:30'],
            'fulfillment.method' => ['required', Rule::in(['delivery', 'pickup'])],
            'fulfillment.address' => ['required_if:fulfillment.method,delivery', 'nullable', 'string', 'max:1000'],
            'fulfillment.city' => ['required_if:fulfillment.method,delivery', 'nullable', 'string', 'max:255'],
            'fulfillment.county' => ['required_if:fulfillment.method,delivery', 'nullable', 'string', 'max:255'],
            'fulfillment.pickup_location_id' => ['required_if:fulfillment.method,pickup', 'nullable', 'integer', 'exists:pickup_locations,id'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
