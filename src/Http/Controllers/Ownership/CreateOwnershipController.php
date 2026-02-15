<?php

namespace Sowailem\Ownable\Http\Controllers\Ownership;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\CreateOwnershipRequest;
use Sowailem\Ownable\Services\OwnershipService;

class CreateOwnershipController extends Controller
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
     * Register a new ownership record.
     *
     * @param  \Sowailem\Ownable\Http\Requests\CreateOwnershipRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CreateOwnershipRequest $request)
    {
        $ownership = $this->ownershipService->storeOwnership($request->validated());

        return response()->json([
            'message' => 'Ownership registered successfully.',
            'data' => $ownership
        ], 201);
    }
}
