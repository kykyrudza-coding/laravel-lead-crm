<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CallResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duration' => ['required', 'integer', 'min:1'],
            'result' => ['required', 'string', Rule::enum(CallResult::class)],
            'manager_id' => ['required', 'integer', 'exists:managers,id'],
        ];
    }
}
