<?php

namespace Sowailem\Ownable\Http\Controllers\OwnableModel;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sowailem\Ownable\Services\OwnableModelService;

class ListOwnableModelController extends Controller
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
     * Display a listing of the ownable models.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        return response()->json($this->ownableModelService->getModels($request->all()));
    }
}
