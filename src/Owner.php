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
     * @var \Sowailem\Ownable\Services\OwnershipService
     */
    protected $ownershipService;

    /**
     * Create a new Owner instance.
     * 
     * @param \Sowailem\Ownable\Services\OwnershipService $ownershipService
     */
    public function __construct(\Sowailem\Ownable\Services\OwnershipService $ownershipService)
    {
        $this->ownershipService = $ownershipService;
    }

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
        if (!($owner instanceof Model)) {
            throw new \InvalidArgumentException('Owner must be an Eloquent model');
        }

        if (!($ownable instanceof Model)) {
            throw new \InvalidArgumentException('Ownable must implement Sowailem\Ownable\Contracts\Ownable');
        }

        $this->ownershipService->storeOwnership([
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
            'ownable_id' => $ownable->getKey(),
            'ownable_type' => get_class($ownable),
        ]);

        return $ownable;
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
        if (!($fromOwner instanceof Model) || !($toOwner instanceof Model)) {
            throw new \InvalidArgumentException('Owners must be Eloquent models');
        }

        if (!($ownable instanceof Model)) {
            throw new \InvalidArgumentException('Ownable must implement Sowailem\Ownable\Contracts\Ownable');
        }

        $this->ownershipService->transferOwnership([
            'from_owner_id' => $fromOwner->getKey(),
            'from_owner_type' => get_class($fromOwner),
            'to_owner_id' => $toOwner->getKey(),
            'to_owner_type' => get_class($toOwner),
            'ownable_id' => $ownable->getKey(),
            'ownable_type' => get_class($ownable),
        ]);

        return $ownable;
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
        if (!($owner instanceof Model)) {
            throw new \InvalidArgumentException('Owner must be an Eloquent model');
        }

        if (!($ownable instanceof Model)) {
            throw new \InvalidArgumentException('Ownable must implement Sowailem\Ownable\Contracts\Ownable');
        }

        return $this->ownershipService->checkOwnership([
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
            'ownable_id' => $ownable->getKey(),
            'ownable_type' => get_class($ownable),
        ]);
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

        return $this->ownershipService->removeOwnership([
            'ownable_id' => $ownable->getKey(),
            'ownable_type' => get_class($ownable),
        ]);
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

        return $this->ownershipService->getCurrentOwnership(
            get_class($ownable),
            $ownable->getKey()
        )?->owner;
    }
}