<?php

namespace Sowailem\Ownable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CreateOwnableModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::any(['manage-ownable-models', 'create-ownable-model']);
    }

    public function rules(): array
    {
        return [
            'model_class' => 'required|string|unique:ownable_models,model_class',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
