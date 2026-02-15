<?php

namespace Sowailem\Ownable\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * OwnableModel model representing the models that can be owned.
 * 
 * @property int $id
 * @property string $model_class The class name of the ownable model
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class OwnableModel extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'ownable_models';

    /**
     * The attributes that are mass assignable.
     * 
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'model_class',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
