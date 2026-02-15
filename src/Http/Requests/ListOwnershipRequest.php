<?php

namespace Sowailem\Ownable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ListOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id' => 'nullable',
            'owner_type' => 'nullable|string',
            'ownable_id' => 'nullable',
            'ownable_type' => 'nullable|string',
            'is_current' => 'nullable|boolean',
        ];
    }
}
