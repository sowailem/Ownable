<?php

namespace Sowailem\Ownable\Http\Controllers\Ownership;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\TransferOwnershipRequest;
use Sowailem\Ownable\Services\OwnershipService;

class TransferOwnershipController extends Controller
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
     * Transfer ownership of an ownable entity.
     *
     * @param  \Sowailem\Ownable\Http\Requests\TransferOwnershipRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(TransferOwnershipRequest $request)
    {
        $ownership = $this->ownershipService->transferOwnership($request->validated());

        return response()->json([
            'message' => 'Ownership transferred successfully.',
            'data' => $ownership
        ], 200);
    }
}
