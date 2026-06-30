<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Guard\StoreGuardRequest;
use App\Http\Requests\Guard\UpdateGuardRequest;
use App\Http\Resources\GuardResource;
use App\Services\GuardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardController extends Controller
{
    public function __construct(private readonly GuardService $guardService) {}

    public function index(Request $request): JsonResponse
    {
        $guards = $this->guardService->paginate($request->query());

        return $this->success(GuardResource::collection($guards), 'Guards retrieved successfully.');
    }

    public function store(StoreGuardRequest $request): JsonResponse
    {
        $guard = $this->guardService->create($request->validated());

        return $this->success(new GuardResource($guard), 'Guard created successfully.', 201);
    }

    public function show(int $guard): JsonResponse
    {
        $guard = $this->guardService->find($guard);

        return $this->success(new GuardResource($guard), 'Guard retrieved successfully.');
    }

    public function update(UpdateGuardRequest $request, int $guard): JsonResponse
    {
        $guard = $this->guardService->update($guard, $request->validated());

        return $this->success(new GuardResource($guard), 'Guard updated successfully.');
    }

    public function destroy(int $guard): JsonResponse
    {
        $this->guardService->delete($guard);

        return $this->success(null, 'Guard deleted successfully.');
    }
}
