<?php

namespace Sowailem\Ownable\Http\Controllers\OwnableModel;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Http\Requests\UpdateOwnableModelRequest;
use Sowailem\Ownable\Services\OwnableModelService;

class UpdateOwnableModelController extends Controller
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
     * Update the specified ownable model in storage.
     *
     * @param  \Sowailem\Ownable\Http\Requests\UpdateOwnableModelRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateOwnableModelRequest $request, $id)
    {
        $ownableModel = $this->ownableModelService->findModel($id);

        if (!$ownableModel) {
            return response()->json(['message' => 'Ownable model not found.'], 404);
        }

        $this->ownableModelService->updateModel($ownableModel, $request->validated());

        return response()->json([
            'message' => 'Ownable model updated successfully.',
            'data' => $ownableModel
        ]);
    }
}
