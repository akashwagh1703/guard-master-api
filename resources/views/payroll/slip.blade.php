<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip - {{ $payroll->securityGuard->name ?? 'Guard' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .total { font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company['name'] ?? 'Security Guard Management' }}</h1>
        <p>Salary Slip - {{ \Carbon\Carbon::create($payroll->year, $payroll->month)->format('F Y') }}</p>
    </div>

    <table>
        <tr><th>Employee ID</th><td>{{ $payroll->securityGuard->employee_id ?? '-' }}</td></tr>
        <tr><th>Name</th><td>{{ $payroll->securityGuard->name ?? '-' }}</td></tr>
        <tr><th>Present Days</th><td>{{ $payroll->present_days }}</td></tr>
        <tr><th>Absent Days</th><td>{{ $payroll->absent_days }}</td></tr>
        <tr><th>Half Days</th><td>{{ $payroll->half_days }}</td></tr>
        <tr><th>Late Count</th><td>{{ $payroll->late_count }}</td></tr>
    </table>

    <table>
        <thead>
            <tr><th>Description</th><th>Type</th><th>Amount</th></tr>
        </thead>
        <tbody>
            <tr><td>Base Salary</td><td>Earning</td><td>{{ number_format($payroll->base_salary, 2) }}</td></tr>
            <tr><td>Overtime</td><td>Earning</td><td>{{ number_format($payroll->overtime_amount, 2) }}</td></tr>
            <tr><td>Bonus</td><td>Earning</td><td>{{ number_format($payroll->bonus, 2) }}</td></tr>
            <tr><td>Advance</td><td>Deduction</td><td>{{ number_format($payroll->advance, 2) }}</td></tr>
            <tr><td>Other Deductions</td><td>Deduction</td><td>{{ number_format($payroll->deduction, 2) }}</td></tr>
            @foreach($payroll->items as $item)
            <tr><td>{{ $item->description }}</td><td>{{ ucfirst($item->type) }}</td><td>{{ number_format($item->amount, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">Net Salary: {{ number_format($payroll->net_salary, 2) }}</p>
    <p>Status: {{ ucfirst($payroll->status->value) }}</p>
    <p>Generated: {{ $payroll->generated_at?->format('d M Y H:i') }}</p>
</body>
</html>
