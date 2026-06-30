<?php

namespace App\Http\Controllers\Api;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function show(Request $request, string $type): JsonResponse
    {
        $report = $this->reportService->generate($type, $request->query());

        return $this->success($report, ucfirst($type).' report generated successfully.');
    }

    public function export(Request $request, string $type, string $format): StreamedResponse|JsonResponse
    {
        return $this->reportService->export($type, $format, $request->query());
    }
}
