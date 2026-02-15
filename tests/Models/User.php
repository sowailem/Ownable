<?php

namespace Sowailem\Ownable\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];
}