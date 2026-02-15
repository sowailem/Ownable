<?php

namespace Sowailem\Ownable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Owner facade for accessing the Owner service.
 * 
 * This facade provides a static interface to the Owner service,
 * allowing easy access to ownership management functionality.
 * 
 * @method static \Sowailem\Ownable\Models\Ownership give(\Illuminate\Database\Eloquent\Model $owner, \Illuminate\Database\Eloquent\Model $ownable)
 * @method static \Sowailem\Ownable\Models\Ownership transfer(\Illuminate\Database\Eloquent\Model $fromOwner, \Illuminate\Database\Eloquent\Model $toOwner, \Illuminate\Database\Eloquent\Model $ownable)
 * @method static bool check(\Illuminate\Database\Eloquent\Model $owner, \Illuminate\Database\Eloquent\Model $ownable)
 * 
 * @see \Sowailem\Ownable\Owner
 */
class Owner extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ownable.owner';
    }
}
