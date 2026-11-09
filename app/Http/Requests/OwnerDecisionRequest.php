<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OwnerDecisionRequest extends FormRequest
{
    /** Authorization is carried by the unguessable booking token in the URL. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:approve,reject'],
        ];
    }

    public function approves(): bool
    {
        return $this->validated('decision') === 'approve';
    }
}
