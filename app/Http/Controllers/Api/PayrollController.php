<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Payroll\GeneratePayrollRequest;
use App\Http\Resources\PayrollResource;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(Request $request): JsonResponse
    {
        $payrolls = $this->payrollService->paginate($request->query());

        return $this->success(PayrollResource::collection($payrolls), 'Payroll records retrieved successfully.');
    }

    public function show(int $payroll): JsonResponse
    {
        $payroll = $this->payrollService->find($payroll);

        return $this->success(new PayrollResource($payroll), 'Payroll record retrieved successfully.');
    }

    public function generate(GeneratePayrollRequest $request): JsonResponse
    {
        $result = $this->payrollService->generate($request->validated());

        return $this->success($result, 'Payroll generated successfully.', 201);
    }

    public function payslip(int $payroll): Response
    {
        return $this->payrollService->getPayslip($payroll);
    }

    public function myPayroll(Request $request): JsonResponse
    {
        $payrolls = $this->payrollService->getMyPayroll($request->user(), $request->query());

        return $this->success(PayrollResource::collection($payrolls), 'Your payroll records retrieved successfully.');
    }

    public function myPayslip(Request $request, int $payroll): Response
    {
        return $this->payrollService->getMyPayslip($request->user(), $payroll);
    }
}
