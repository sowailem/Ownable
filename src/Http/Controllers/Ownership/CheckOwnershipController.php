<?php

namespace Sowailem\Ownable\Http\Controllers\Ownership;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\CheckOwnershipRequest;
use Sowailem\Ownable\Services\OwnershipService;

class CheckOwnershipController extends Controller
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
     * Check if an owner owns a specific ownable entity.
     *
     * @param  \Sowailem\Ownable\Http\Requests\CheckOwnershipRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CheckOwnershipRequest $request)
    {
        $owns = $this->ownershipService->checkOwnership($request->validated());

        return response()->json([
            'owns' => $owns
        ], 200);
    }
}
