<?php

namespace Sowailem\Ownable;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Sowailem\Ownable\Models\Ownership;

/**
 * Main Owner service class for managing ownership relationships.
 * 
 * This class provides a centralized service for managing ownership
 * operations between models, including giving ownership, transferring
 * ownership, and checking ownership status.
 */
class Owner
{
    /**
     * Give ownership of an ownable entity to an owner.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model
     * @param \Illuminate\Database\Eloquent\Model $ownable The ownable entity
     * @return \Sowailem\Ownable\Models\Ownership
     * @throws \InvalidArgumentException When owner or ownable are not Eloquent models
     */
    public function give($owner, $ownable)
    {
        if (!($owner instanceof Model) || !($ownable instanceof Model)) {
            throw new \InvalidArgumentException('Owner and ownable must be Eloquent models');
        }

        // Mark existing ownerships as not current
        Ownership::where('ownable_id', $ownable->getKey())
            ->where('ownable_type', get_class($ownable))
            ->where('is_current', true)
            ->update(['is_current' => false]);

        return Ownership::create([
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
            'ownable_id' => $ownable->getKey(),
            'ownable_type' => get_class($ownable),
            'is_current' => true,
        ]);
    }

    /**
     * Transfer ownership of an ownable entity from one owner to another.
     * 
     * @param \Illuminate\Database\Eloquent\Model $fromOwner The current owner model
     * @param \Illuminate\Database\Eloquent\Model $toOwner The new owner model
     * @param \Illuminate\Database\Eloquent\Model $ownable The ownable entity
     * @return \Sowailem\Ownable\Models\Ownership
     * @throws \InvalidArgumentException When owners or ownable are not Eloquent models
     */
    public function transfer($fromOwner, $toOwner, $ownable)
    {
        return $this->give($toOwner, $ownable);
    }

    /**
     * Check if an owner owns a specific ownable entity.
     * 
     * @param \Illuminate\Database\Eloquent\Model $owner The owner model to check
     * @param \Illuminate\Database\Eloquent\Model $ownable The ownable entity to check
     * @return bool True if the owner owns the entity, false otherwise
     * @throws \InvalidArgumentException When owner or ownable are not Eloquent models
     */
    public function check($owner, $ownable)
    {
        if (!($owner instanceof Model) || !($ownable instanceof Model)) {
            throw new \InvalidArgumentException('Owner and ownable must be Eloquent models');
        }

        return Ownership::where('owner_id', $owner->getKey())
            ->where('owner_type', get_class($owner))
            ->where('ownable_id', $ownable->getKey())
            ->where('ownable_type', get_class($ownable))
            ->where('is_current', true)
            ->exists();
    }

    /**
     * Remove ownership of an ownable entity.
     * 
     * @param \Illuminate\Database\Eloquent\Model $ownable The ownable entity
     * @return bool
     */
    public function remove($ownable)
    {
        if (!($ownable instanceof Model)) {
            throw new \InvalidArgumentException('Ownable must be an Eloquent model');
        }

        return (bool) Ownership::where('ownable_id', $ownable->getKey())
            ->where('ownable_type', get_class($ownable))
            ->where('is_current', true)
            ->update(['is_current' => false]);
    }

    /**
     * Get the current owner of an ownable entity.
     * 
     * @param \Illuminate\Database\Eloquent\Model $ownable The ownable entity
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function currentOwner($ownable)
    {
        if (!($ownable instanceof Model)) {
            throw new \InvalidArgumentException('Ownable must be an Eloquent model');
        }

        return Ownership::where('ownable_id', $ownable->getKey())
            ->where('ownable_type', get_class($ownable))
            ->where('is_current', true)
            ->first()
            ?->owner;
    }
}