<?php

namespace Sowailem\Ownable\Services;

use Sowailem\Ownable\Models\Ownership;
use Illuminate\Database\Eloquent\Collection;

class OwnershipService
{
    /**
     * Get ownership records based on filters.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOwnerships(array $filters = [])
    {
        $query = Ownership::with(['owner', 'ownable']);

        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['owner_type'])) {
            $query->where('owner_type', $filters['owner_type']);
        }

        if (isset($filters['ownable_id'])) {
            $query->where('ownable_id', $filters['ownable_id']);
        }

        if (isset($filters['ownable_type'])) {
            $query->where('ownable_type', $filters['ownable_type']);
        }

        if (isset($filters['is_current'])) {
            $query->where('is_current', filter_var($filters['is_current'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate();
    }

    /**
     * Store a new ownership record.
     *
     * @param array $data
     * @return \Sowailem\Ownable\Models\Ownership
     */
    public function storeOwnership(array $data): Ownership
    {
        // Mark existing ownerships as not current
        Ownership::where('ownable_id', $data['ownable_id'])
            ->where('ownable_type', $data['ownable_type'])
            ->where('is_current', true)
            ->update(['is_current' => false]);

        return Ownership::create(array_merge($data, ['is_current' => true]));
    }
    /**
     * Get the current ownership for an ownable entity.
     *
     * @param string $ownableType
     * @param mixed $ownableId
     * @return \Sowailem\Ownable\Models\Ownership|null
     */
    public function getCurrentOwnership(string $ownableType, $ownableId): ?Ownership
    {
        if (is_null($ownableId)) {
            return null;
        }

        $ownership = Ownership::with('owner')
            ->where('ownable_type', $ownableType)
            ->where('ownable_id', $ownableId)
            ->where('is_current', true)
            ->first();

        return $ownership;
    }
}
