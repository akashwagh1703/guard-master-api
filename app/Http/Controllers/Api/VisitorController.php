<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Visitor\GuardStoreVisitorRequest;
use App\Http\Requests\Visitor\StoreVisitorRequest;
use App\Http\Requests\Visitor\UpdateVisitorRequest;
use App\Http\Resources\VisitorResource;
use App\Services\AuthService;
use App\Services\VisitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VisitorController extends Controller
{
    public function __construct(
        private readonly VisitorService $visitorService,
        private readonly AuthService $authService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $visitors = $this->visitorService->paginate($request->query());

        return $this->success(VisitorResource::collection($visitors), 'Visitor entries retrieved successfully.');
    }

    public function store(StoreVisitorRequest $request): JsonResponse
    {
        $visitor = $this->visitorService->create($request->validated());

        return $this->success(new VisitorResource($visitor), 'Visitor entry created successfully.', 201);
    }

    public function show(int $visitor): JsonResponse
    {
        $visitor = $this->visitorService->find($visitor);

        return $this->success(new VisitorResource($visitor), 'Visitor entry retrieved successfully.');
    }

    public function update(UpdateVisitorRequest $request, int $visitor): JsonResponse
    {
        $visitor = $this->visitorService->update($visitor, $request->validated());

        return $this->success(new VisitorResource($visitor), 'Visitor entry updated successfully.');
    }

    public function destroy(int $visitor): JsonResponse
    {
        $this->visitorService->delete($visitor);

        return $this->success(null, 'Visitor entry deleted successfully.');
    }

    public function myVisitors(Request $request): JsonResponse
    {
        $filters = array_merge($request->query(), ['guard_id' => $request->user()->guard_id]);
        $visitors = $this->visitorService->paginate($filters);

        return $this->success(VisitorResource::collection($visitors), 'Your visitor entries retrieved successfully.');
    }

    public function storeForGuard(GuardStoreVisitorRequest $request): JsonResponse
    {
        $assignment = $this->authService->getGuardAssignment($request->user())
            ?? throw ValidationException::withMessages([
                'assignment' => ['No active site assignment found for your account.'],
            ]);

        $data = array_merge($request->validated(), [
            'site_id' => $assignment['site_id'],
            'guard_id' => $request->user()->guard_id,
        ]);

        $visitor = $this->visitorService->create($data);

        return $this->success(new VisitorResource($visitor->load(['site', 'securityGuard'])), 'Visitor entry created successfully.', 201);
    }

    public function recordExit(Request $request, int $visitor): JsonResponse
    {
        $entry = $this->visitorService->find($visitor);

        if ($entry->guard_id !== $request->user()->guard_id) {
            throw ValidationException::withMessages([
                'visitor' => ['You are not authorized to update this visitor entry.'],
            ]);
        }

        $updated = $this->visitorService->recordExit($visitor, $request->only(['latitude', 'longitude']));

        return $this->success(new VisitorResource($updated->load(['site', 'securityGuard'])), 'Visitor exit recorded successfully.');
    }
}
