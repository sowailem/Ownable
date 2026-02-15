<?php

namespace Sowailem\Ownable\Http\Controllers\OwnableModel;

use Illuminate\Routing\Controller;
use Sowailem\Ownable\Services\OwnableModelService;

class ViewOwnableModelController extends Controller
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
     * Display the specified ownable model.
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

        return response()->json(['data' => $ownableModel]);
    }
}
