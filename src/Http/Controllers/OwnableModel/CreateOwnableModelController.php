<?php

namespace Sowailem\Ownable\Http\Controllers\OwnableModel;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\CreateOwnableModelRequest;
use Sowailem\Ownable\Services\OwnableModelService;

class CreateOwnableModelController extends Controller
{
    /**
     * @var \Sowailem\Ownable\Services\OwnableModelService
     */
    protected $ownableModelService;

    /**
     * @param \Sowailem\Ownable\Services\OwnableModelService $ownableModelService
     */
    public function __construct(OwnableModelService $ownableModelService)
    {
        $this->ownableModelService = $ownableModelService;
    }

    /**
     * Store a newly created ownable model in storage.
     *
     * @param  \Sowailem\Ownable\Http\Requests\CreateOwnableModelRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(CreateOwnableModelRequest $request)
    {
        $ownableModel = $this->ownableModelService->storeModel($request->validated());

        return response()->json([
            'message' => 'Ownable model created successfully.',
            'data' => $ownableModel
        ], 201);
    }
}
