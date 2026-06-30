<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Setting\StoreHolidayRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\HolidayResource;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private readonly SettingService $settingService) {}

    public function index(): JsonResponse
    {
        $settings = $this->settingService->getAll();

        return $this->success($settings, 'Settings retrieved successfully.');
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $grouped = [];
        foreach ($request->validated()['settings'] as $item) {
            $grouped[$item['group']][$item['key']] = $item['value'];
        }

        $settings = $this->settingService->update($grouped);

        return $this->success($settings, 'Settings updated successfully.');
    }

    public function holidays(Request $request): JsonResponse
    {
        $holidays = $this->settingService->getHolidays($request->query());

        return $this->success(HolidayResource::collection($holidays), 'Holidays retrieved successfully.');
    }

    public function storeHoliday(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = $this->settingService->createHoliday($request->validated());

        return $this->success(new HolidayResource($holiday), 'Holiday created successfully.', 201);
    }

    public function destroyHoliday(int $holiday): JsonResponse
    {
        $this->settingService->deleteHoliday($holiday);

        return $this->success(null, 'Holiday deleted successfully.');
    }
}
