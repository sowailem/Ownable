<?php

namespace Sowailem\Ownable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Owner facade for accessing the Owner service.
 * 
 * This facade provides a static interface to the Owner service,
 * allowing easy access to ownership management functionality.
 * 
 * @method static mixed give(\Illuminate\Database\Eloquent\Model $owner, \Sowailem\Ownable\Contracts\Ownable $ownable)
 * @method static mixed transfer(\Illuminate\Database\Eloquent\Model $fromOwner, \Illuminate\Database\Eloquent\Model $toOwner, \Sowailem\Ownable\Contracts\Ownable $ownable)
 * @method static bool check(\Illuminate\Database\Eloquent\Model $owner, \Sowailem\Ownable\Contracts\Ownable $ownable)
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
