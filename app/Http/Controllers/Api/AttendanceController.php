<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\CheckOutRequest;
use App\Http\Requests\Attendance\CorrectAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function index(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->paginate($request->query());

        return $this->success(AttendanceResource::collection($attendance), 'Attendance records retrieved successfully.');
    }

    public function show(int $attendance): JsonResponse
    {
        $record = $this->attendanceService->find($attendance);

        return $this->success(new AttendanceResource($record), 'Attendance record retrieved successfully.');
    }

    public function correct(CorrectAttendanceRequest $request, int $attendance): JsonResponse
    {
        $record = $this->attendanceService->correct($attendance, $request->validated());

        return $this->success(new AttendanceResource($record), 'Attendance corrected successfully.');
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        return $this->attendanceService->export($request->query());
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $record = $this->attendanceService->checkIn($request->user(), $request->validated());

        return $this->success(new AttendanceResource($record), 'Check-in recorded successfully.');
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $record = $this->attendanceService->checkOut($request->user(), $request->validated());

        return $this->success(new AttendanceResource($record), 'Check-out recorded successfully.');
    }

    public function myAttendance(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->getMyAttendance($request->user(), $request->query());

        return $this->success(AttendanceResource::collection($attendance), 'Your attendance records retrieved successfully.');
    }
}
