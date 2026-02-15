<?php

namespace Sowailem\Ownable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id' => 'required',
            'owner_type' => 'required|string',
            'ownable_id' => 'required',
            'ownable_type' => 'required|string',
        ];
    }
}
