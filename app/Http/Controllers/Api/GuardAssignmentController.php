<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Assignment\StoreAssignmentRequest;
use App\Http\Requests\Assignment\UpdateAssignmentRequest;
use App\Http\Resources\GuardAssignmentResource;
use App\Services\GuardAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardAssignmentController extends Controller
{
    public function __construct(private readonly GuardAssignmentService $assignmentService) {}

    public function index(Request $request): JsonResponse
    {
        $assignments = $this->assignmentService->paginate($request->query());

        return $this->success(GuardAssignmentResource::collection($assignments), 'Assignments retrieved successfully.');
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $assignment = $this->assignmentService->create($request->validated());

        return $this->success(new GuardAssignmentResource($assignment), 'Assignment created successfully.', 201);
    }

    public function show(int $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->find($assignment);

        return $this->success(new GuardAssignmentResource($assignment), 'Assignment retrieved successfully.');
    }

    public function update(UpdateAssignmentRequest $request, int $assignment): JsonResponse
    {
        $assignment = $this->assignmentService->update($assignment, $request->validated());

        return $this->success(new GuardAssignmentResource($assignment), 'Assignment updated successfully.');
    }

    public function destroy(int $assignment): JsonResponse
    {
        $this->assignmentService->delete($assignment);

        return $this->success(null, 'Assignment deleted successfully.');
    }
}
