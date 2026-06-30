<?php

namespace App\Http\Controllers\Api;

use App\Enums\IncidentPriority;
use App\Http\Requests\Incident\GuardStoreIncidentRequest;
use App\Http\Requests\Incident\StoreIncidentRequest;
use App\Http\Requests\Incident\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Services\AuthService;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidentService,
        private readonly AuthService $authService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $incidents = $this->incidentService->paginate($request->query());

        return $this->success(IncidentResource::collection($incidents), 'Incidents retrieved successfully.');
    }

    public function store(StoreIncidentRequest $request): JsonResponse
    {
        $incident = $this->incidentService->create($request->validated());

        return $this->success(new IncidentResource($incident), 'Incident reported successfully.', 201);
    }

    public function show(int $incident): JsonResponse
    {
        $incident = $this->incidentService->find($incident);

        return $this->success(new IncidentResource($incident), 'Incident retrieved successfully.');
    }

    public function update(UpdateIncidentRequest $request, int $incident): JsonResponse
    {
        $incident = $this->incidentService->update($incident, $request->validated());

        return $this->success(new IncidentResource($incident), 'Incident updated successfully.');
    }

    public function destroy(int $incident): JsonResponse
    {
        $this->incidentService->delete($incident);

        return $this->success(null, 'Incident deleted successfully.');
    }

    public function myIncidents(Request $request): JsonResponse
    {
        $filters = array_merge($request->query(), ['guard_id' => $request->user()->guard_id]);
        $incidents = $this->incidentService->paginate($filters);

        return $this->success(IncidentResource::collection($incidents), 'Your incidents retrieved successfully.');
    }

    public function storeForGuard(GuardStoreIncidentRequest $request): JsonResponse
    {
        $assignment = $this->authService->getGuardAssignment($request->user())
            ?? throw ValidationException::withMessages([
                'assignment' => ['No active site assignment found for your account.'],
            ]);

        $validated = $request->validated();
        $incident = $this->incidentService->create([
            'site_id' => $assignment['site_id'],
            'guard_id' => $request->user()->guard_id,
            'category' => $validated['category'],
            'title' => $validated['category'],
            'description' => $validated['description'],
            'priority' => IncidentPriority::Medium,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'incident_time' => now(),
        ]);

        return $this->success(new IncidentResource($incident->load(['site', 'securityGuard'])), 'Incident reported successfully.', 201);
    }
}
