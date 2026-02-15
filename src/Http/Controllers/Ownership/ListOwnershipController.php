<?php

namespace Sowailem\Ownable\Http\Controllers\Ownership;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\ListOwnershipRequest;
use Sowailem\Ownable\Services\OwnershipService;

class ListOwnershipController extends Controller
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
     * Get ownership records.
     *
     * @param  \Sowailem\Ownable\Http\Requests\ListOwnershipRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ListOwnershipRequest $request)
    {
        return response()->json($this->ownershipService->getOwnerships($request->all()));
    }
}
