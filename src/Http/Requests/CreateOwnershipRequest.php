<?php

namespace Sowailem\Ownable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create-ownership');
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
