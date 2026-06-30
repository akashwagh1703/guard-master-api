<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(private readonly ShiftService $shiftService) {}

    public function index(Request $request): JsonResponse
    {
        $shifts = $this->shiftService->paginate($request->query());

        return $this->success(ShiftResource::collection($shifts), 'Shifts retrieved successfully.');
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = $this->shiftService->create($request->validated());

        return $this->success(new ShiftResource($shift), 'Shift created successfully.', 201);
    }

    public function show(int $shift): JsonResponse
    {
        $shift = $this->shiftService->find($shift);

        return $this->success(new ShiftResource($shift), 'Shift retrieved successfully.');
    }

    public function update(UpdateShiftRequest $request, int $shift): JsonResponse
    {
        $shift = $this->shiftService->update($shift, $request->validated());

        return $this->success(new ShiftResource($shift), 'Shift updated successfully.');
    }

    public function destroy(int $shift): JsonResponse
    {
        $this->shiftService->delete($shift);

        return $this->success(null, 'Shift deleted successfully.');
    }
}
