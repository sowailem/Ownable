<?php

namespace Sowailem\Ownable\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Sowailem\Ownable\Models\Ownership;
use Illuminate\Support\Facades\Validator;

class OwnershipController extends Controller
{
    /**
     * Register a new ownership record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'owner_id' => 'required',
            'owner_type' => 'required|string',
            'ownable_id' => 'required',
            'ownable_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Mark existing ownerships as not current
        Ownership::where('ownable_id', $request->ownable_id)
            ->where('ownable_type', $request->ownable_type)
            ->where('is_current', true)
            ->update(['is_current' => false]);

        $ownership = Ownership::create([
            'owner_id' => $request->owner_id,
            'owner_type' => $request->owner_type,
            'ownable_id' => $request->ownable_id,
            'ownable_type' => $request->ownable_type,
            'is_current' => true,
        ]);

        return response()->json([
            'message' => 'Ownership registered successfully.',
            'data' => $ownership
        ], 201);
    }

    /**
     * Get ownership records.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Ownership::query();

        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }
        if ($request->has('owner_type')) {
            $query->where('owner_type', $request->owner_type);
        }
        if ($request->has('ownable_id')) {
            $query->where('ownable_id', $request->ownable_id);
        }
        if ($request->has('ownable_type')) {
            $query->where('ownable_type', $request->ownable_type);
        }
        if ($request->has('is_current')) {
            $query->where('is_current', $request->boolean('is_current'));
        }

        return response()->json($query->paginate());
    }
}
