<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('is_active', true)],
            'currency'   => ['required', 'string', Rule::in(['USDT', 'BTC'])],
        ];
    }
}
