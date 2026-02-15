<?php

namespace Sowailem\Ownable\Http\Controllers\Ownership;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\RemoveOwnershipRequest;
use Sowailem\Ownable\Services\OwnershipService;

class RemoveOwnershipController extends Controller
{
    /**
     * @var \Sowailem\Ownable\Services\OwnershipService
     */
    protected $ownershipService;

    /**
     * @param \Sowailem\Ownable\Services\OwnershipService $ownershipService
     */
    public function __construct(OwnershipService $ownershipService)
    {
        $this->ownershipService = $ownershipService;
    }

    /**
     * Remove ownership of an ownable entity.
     *
     * @param  \Sowailem\Ownable\Http\Requests\RemoveOwnershipRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(RemoveOwnershipRequest $request)
    {
        $removed = $this->ownershipService->removeOwnership($request->validated());

        return response()->json([
            'message' => $removed ? 'Ownership removed successfully.' : 'No current ownership found to remove.',
            'success' => $removed
        ], 200);
    }
}
