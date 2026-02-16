<?php

namespace Sowailem\Ownable\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateOwnableModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-ownable-models');
    }

    public function rules(): array
    {
        $id = $this->route('ownable_model');

        return [
            'model_class' => 'string|unique:ownable_models,model_class,' . $id,
            'description' => 'nullable|string',
            'response_fields' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}
