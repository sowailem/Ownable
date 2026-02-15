<?php

namespace Sowailem\Ownable\Http\Controllers\Ownership;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\GetCurrentOwnerRequest;
use Sowailem\Ownable\Services\OwnershipService;

class GetCurrentOwnerController extends Controller
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
     * Get the current owner of an ownable entity.
     *
     * @param  \Sowailem\Ownable\Http\Requests\GetCurrentOwnerRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(GetCurrentOwnerRequest $request)
    {
        $ownership = $this->ownershipService->getCurrentOwnership(
            $request->input('ownable_type'),
            $request->input('ownable_id')
        );

        return response()->json([
            'data' => $ownership ? $ownership->owner : null
        ], 200);
    }
}
