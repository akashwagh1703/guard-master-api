<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Site\StoreSiteRequest;
use App\Http\Requests\Site\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use App\Services\SiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function __construct(private readonly SiteService $siteService) {}

    public function index(Request $request): JsonResponse
    {
        $sites = $this->siteService->paginate($request->query());

        return $this->success(SiteResource::collection($sites), 'Sites retrieved successfully.');
    }

    public function store(StoreSiteRequest $request): JsonResponse
    {
        $site = $this->siteService->create($request->validated());

        return $this->success(new SiteResource($site), 'Site created successfully.', 201);
    }

    public function show(int $site): JsonResponse
    {
        $site = $this->siteService->find($site);

        return $this->success(new SiteResource($site), 'Site retrieved successfully.');
    }

    public function update(UpdateSiteRequest $request, int $site): JsonResponse
    {
        $site = $this->siteService->update($site, $request->validated());

        return $this->success(new SiteResource($site), 'Site updated successfully.');
    }

    public function destroy(int $site): JsonResponse
    {
        $this->siteService->delete($site);

        return $this->success(null, 'Site deleted successfully.');
    }
}
