<?php

namespace Sowailem\Ownable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_owner_id' => 'required',
            'from_owner_type' => 'required|string',
            'to_owner_id' => 'required',
            'to_owner_type' => 'required|string',
            'ownable_id' => 'required',
            'ownable_type' => 'required|string',
        ];
    }
}
