<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveStatusRequest;
use App\Http\Resources\LeaveResource;
use App\Services\LeaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leaveService) {}

    public function index(Request $request): JsonResponse
    {
        $leaves = $this->leaveService->paginate($request->query());

        return $this->success(LeaveResource::collection($leaves), 'Leave requests retrieved successfully.');
    }

    public function show(int $leave): JsonResponse
    {
        $leave = $this->leaveService->find($leave);

        return $this->success(new LeaveResource($leave), 'Leave request retrieved successfully.');
    }

    public function updateStatus(UpdateLeaveStatusRequest $request, int $leave): JsonResponse
    {
        $leave = $this->leaveService->updateStatus($leave, $request->validated());

        return $this->success(new LeaveResource($leave), 'Leave request status updated successfully.');
    }

    public function apply(StoreLeaveRequest $request): JsonResponse
    {
        $leave = $this->leaveService->apply($request->user(), $request->validated());

        return $this->success(new LeaveResource($leave), 'Leave request submitted successfully.', 201);
    }

    public function myLeaves(Request $request): JsonResponse
    {
        $leaves = $this->leaveService->getMyLeaves($request->user(), $request->query());

        return $this->success(LeaveResource::collection($leaves), 'Your leave requests retrieved successfully.');
    }
}
