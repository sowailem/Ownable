<?php

namespace Sowailem\Ownable\Http\Controllers\OwnableModel;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Services\OwnableModelService;

class DeleteOwnableModelController extends Controller
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
     * Remove the specified ownable model from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke($id)
    {
        $ownableModel = $this->ownableModelService->findModel($id);

        if (!$ownableModel) {
            return response()->json(['message' => 'Ownable model not found.'], 404);
        }

        $this->ownableModelService->deleteModel($ownableModel);

        return response()->json(['message' => 'Ownable model deleted successfully.']);
    }
}
