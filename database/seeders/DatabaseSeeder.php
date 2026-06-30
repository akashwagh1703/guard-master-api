<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Guard;
use App\Models\GuardAssignment;
use App\Models\Holiday;
use App\Models\Incident;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use App\Models\VisitorEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        $this->seedSettings();
        $this->seedHolidays();

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@secureguard.com',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => UserRole::Admin,
            'phone' => '+91 90000 00001',
        ]);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $sites = $this->seedSites($admin->id);
        $shifts = $this->seedShifts($admin->id);
        $guards = $this->seedGuards($admin->id);
        $this->seedAssignments($guards, $sites, $shifts, $admin->id);
        $this->seedVisitors($guards, $sites, $admin->id);
        $this->seedIncidents($guards, $sites, $admin->id);
        $this->seedLeaveRequests($guards, $admin->id);
        $this->seedPayrolls($guards, $admin->id);
    }

    private function seedRolesAndPermissions(): void
    {
        $adminRole = Role::create(['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full system access']);
        $guardRole = Role::create(['name' => 'Security Guard', 'slug' => 'guard', 'description' => 'Mobile app access']);

        $permissions = [
            ['name' => 'Manage Sites', 'slug' => 'sites.manage', 'group' => 'sites'],
            ['name' => 'Manage Guards', 'slug' => 'guards.manage', 'group' => 'guards'],
            ['name' => 'Manage Attendance', 'slug' => 'attendance.manage', 'group' => 'attendance'],
            ['name' => 'Manage Payroll', 'slug' => 'payroll.manage', 'group' => 'payroll'],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::create($perm);
            $adminRole->permissions()->attach($permission);
        }

        $guardRole->permissions()->attach(
            Permission::whereIn('slug', ['attendance.manage'])->pluck('id')
        );
    }

    private function seedSettings(): void
    {
        $settings = [
            ['group' => 'company', 'key' => 'company_name', 'value' => 'SecureGuard Agency'],
            ['group' => 'company', 'key' => 'company_email', 'value' => 'info@secureguard.com'],
            ['group' => 'company', 'key' => 'company_phone', 'value' => '+91 80 1234 5678'],
            ['group' => 'attendance', 'key' => 'grace_minutes', 'value' => '10'],
            ['group' => 'attendance', 'key' => 'half_day_hours', 'value' => '4'],
            ['group' => 'payroll', 'key' => 'working_days_per_month', 'value' => '26'],
            ['group' => 'payroll', 'key' => 'late_deduction', 'value' => '100'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }

    private function seedHolidays(): void
    {
        $holidays = [
            ['name' => 'Republic Day', 'date' => '2026-01-26'],
            ['name' => 'Holi', 'date' => '2026-03-14'],
            ['name' => 'Independence Day', 'date' => '2026-08-15'],
            ['name' => 'Diwali', 'date' => '2026-11-08'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::create($holiday);
        }
    }

    private function seedSites(int $adminId): array
    {
        $data = [
            ['name' => 'Tech Park Tower A', 'client_name' => 'Infosys Ltd', 'contact_person' => 'Rajesh Kumar', 'phone' => '+91 98765 43210', 'address' => 'Whitefield, Bangalore', 'latitude' => 12.9698, 'longitude' => 77.7499, 'attendance_radius' => 100],
            ['name' => 'Metro Mall', 'client_name' => 'Prestige Group', 'contact_person' => 'Priya Sharma', 'phone' => '+91 87654 32109', 'address' => 'MG Road, Bangalore', 'latitude' => 12.9756, 'longitude' => 77.6064, 'attendance_radius' => 150],
            ['name' => 'Green Valley Apartments', 'client_name' => 'Brigade Group', 'contact_person' => 'Amit Patel', 'phone' => '+91 76543 21098', 'address' => 'Sarjapur Road, Bangalore', 'latitude' => 12.9102, 'longitude' => 77.6856, 'attendance_radius' => 80],
            ['name' => 'Industrial Zone B', 'client_name' => 'Tata Motors', 'contact_person' => 'Suresh Reddy', 'phone' => '+91 65432 10987', 'address' => 'Peenya, Bangalore', 'latitude' => 13.0287, 'longitude' => 77.5147, 'attendance_radius' => 200, 'status' => RecordStatus::Inactive],
            ['name' => 'City Hospital', 'client_name' => 'Apollo Hospitals', 'contact_person' => 'Dr. Meera Nair', 'phone' => '+91 54321 09876', 'address' => 'Bannerghatta Road, Bangalore', 'latitude' => 12.8876, 'longitude' => 77.5978, 'attendance_radius' => 120],
        ];

        $sites = [];
        foreach ($data as $row) {
            $sites[] = Site::create(array_merge($row, [
                'status' => $row['status'] ?? RecordStatus::Active,
                'created_by' => $adminId,
            ]));
        }

        return $sites;
    }

    private function seedShifts(int $adminId): array
    {
        $data = [
            ['name' => 'Morning Shift', 'start_time' => '06:00', 'end_time' => '14:00', 'grace_minutes' => 10, 'late_after' => 15],
            ['name' => 'Evening Shift', 'start_time' => '14:00', 'end_time' => '22:00', 'grace_minutes' => 10, 'late_after' => 15],
            ['name' => 'Night Shift', 'start_time' => '22:00', 'end_time' => '06:00', 'grace_minutes' => 15, 'late_after' => 20],
            ['name' => 'General Shift', 'start_time' => '09:00', 'end_time' => '18:00', 'grace_minutes' => 10, 'late_after' => 15, 'status' => RecordStatus::Inactive],
        ];

        $shifts = [];
        foreach ($data as $row) {
            $shifts[] = Shift::create(array_merge($row, [
                'status' => $row['status'] ?? RecordStatus::Active,
                'created_by' => $adminId,
            ]));
        }

        return $shifts;
    }

    private function seedGuards(int $adminId): array
    {
        $data = [
            ['employee_id' => 'SG-001', 'name' => 'Ramesh Singh', 'mobile' => '+91 98765 11111', 'email' => 'ramesh@secureguard.com', 'address' => 'Koramangala, Bangalore', 'joining_date' => '2022-03-15', 'salary' => 18000, 'overtime_rate' => 150, 'username' => 'ramesh.s'],
            ['employee_id' => 'SG-002', 'name' => 'Vikram Das', 'mobile' => '+91 98765 22222', 'email' => 'vikram@secureguard.com', 'address' => 'Indiranagar, Bangalore', 'joining_date' => '2021-08-20', 'salary' => 20000, 'overtime_rate' => 175, 'username' => 'vikram.d'],
            ['employee_id' => 'SG-003', 'name' => 'Mohammed Ali', 'mobile' => '+91 98765 33333', 'email' => 'ali@secureguard.com', 'address' => 'Jayanagar, Bangalore', 'joining_date' => '2023-01-10', 'salary' => 16000, 'overtime_rate' => 130, 'username' => 'ali.m'],
            ['employee_id' => 'SG-004', 'name' => 'Suresh Kumar', 'mobile' => '+91 98765 44444', 'email' => 'suresh@secureguard.com', 'address' => 'HSR Layout, Bangalore', 'joining_date' => '2020-11-05', 'salary' => 22000, 'overtime_rate' => 200, 'username' => 'suresh.k', 'status' => RecordStatus::Inactive],
            ['employee_id' => 'SG-005', 'name' => 'Anil Prasad', 'mobile' => '+91 98765 55555', 'email' => 'anil@secureguard.com', 'address' => 'BTM Layout, Bangalore', 'joining_date' => '2022-06-18', 'salary' => 17000, 'overtime_rate' => 140, 'username' => 'anil.p'],
        ];

        $guards = [];
        foreach ($data as $row) {
            $guard = Guard::create(array_merge($row, [
                'status' => $row['status'] ?? RecordStatus::Active,
                'created_by' => $adminId,
            ]));

            $user = User::create([
                'name' => $guard->name,
                'email' => $guard->email,
                'username' => $guard->username,
                'password' => Hash::make('password123'),
                'role' => UserRole::Guard,
                'guard_id' => $guard->id,
                'phone' => $guard->mobile,
            ]);

            $guard->update(['user_id' => $user->id]);
            $user->roles()->attach(Role::where('slug', 'guard')->first());
            $guards[] = $guard->fresh();
        }

        return $guards;
    }

    private function seedAssignments(array $guards, array $sites, array $shifts, int $adminId): void
    {
        $assignments = [
            [0, 0, 0, '2026-06-01', '2026-06-30'],
            [1, 1, 1, '2026-06-01', '2026-06-30'],
            [2, 4, 2, '2026-06-15', '2026-07-15'],
        ];

        foreach ($assignments as [$gi, $si, $shi, $from, $to]) {
            GuardAssignment::create([
                'guard_id' => $guards[$gi]->id,
                'site_id' => $sites[$si]->id,
                'shift_id' => $shifts[$shi]->id,
                'from_date' => $from,
                'to_date' => $to,
                'status' => RecordStatus::Active,
                'created_by' => $adminId,
            ]);
        }
    }

    private function seedVisitors(array $guards, array $sites, int $adminId): void
    {
        VisitorEntry::create([
            'site_id' => $sites[0]->id, 'guard_id' => $guards[0]->id,
            'visitor_name' => 'John Smith', 'purpose' => 'Business Meeting', 'person_to_meet' => 'Rajesh Kumar',
            'entry_time' => now()->setTime(9, 15), 'exit_time' => now()->setTime(11, 30),
            'status' => 'exited', 'created_by' => $adminId,
        ]);
        VisitorEntry::create([
            'site_id' => $sites[1]->id, 'guard_id' => $guards[1]->id,
            'visitor_name' => 'Sarah Johnson', 'purpose' => 'Delivery', 'person_to_meet' => 'Reception',
            'entry_time' => now()->setTime(10, 45), 'status' => 'inside', 'created_by' => $adminId,
        ]);
    }

    private function seedIncidents(array $guards, array $sites, int $adminId): void
    {
        Incident::create([
            'site_id' => $sites[0]->id, 'guard_id' => $guards[0]->id,
            'category' => 'Security', 'title' => 'Unauthorized Entry Attempt',
            'description' => 'Unknown person tried to enter through back gate without ID.',
            'priority' => 'high', 'status' => 'open', 'incident_time' => now(), 'created_by' => $adminId,
        ]);
        Incident::create([
            'site_id' => $sites[1]->id, 'guard_id' => $guards[1]->id,
            'category' => 'Dispute', 'title' => 'Parking Dispute',
            'description' => 'Visitor parked in reserved spot causing argument.',
            'priority' => 'medium', 'status' => 'pending', 'incident_time' => now()->subDay(), 'created_by' => $adminId,
        ]);
    }

    private function seedLeaveRequests(array $guards, int $adminId): void
    {
        LeaveRequest::create([
            'guard_id' => $guards[0]->id, 'type' => 'Sick Leave',
            'from_date' => '2026-07-05', 'to_date' => '2026-07-06', 'days' => 2,
            'reason' => 'Medical appointment', 'status' => 'pending', 'created_by' => $adminId,
        ]);
        LeaveRequest::create([
            'guard_id' => $guards[1]->id, 'type' => 'Casual Leave',
            'from_date' => '2026-07-10', 'to_date' => '2026-07-12', 'days' => 3,
            'reason' => 'Family function', 'status' => 'approved', 'created_by' => $adminId,
        ]);
    }

    private function seedPayrolls(array $guards, int $adminId): void
    {
        Payroll::create([
            'guard_id' => $guards[0]->id, 'month' => 6, 'year' => 2026,
            'base_salary' => 18000, 'present_days' => 24, 'absent_days' => 2,
            'overtime_amount' => 2250, 'bonus' => 1000, 'deduction' => 500,
            'net_salary' => 20750, 'status' => 'pending', 'generated_at' => now(), 'created_by' => $adminId,
        ]);
        Payroll::create([
            'guard_id' => $guards[1]->id, 'month' => 6, 'year' => 2026,
            'base_salary' => 20000, 'present_days' => 26, 'absent_days' => 0,
            'overtime_amount' => 3500, 'bonus' => 1500, 'deduction' => 800,
            'net_salary' => 24200, 'status' => 'processed', 'generated_at' => now(), 'created_by' => $adminId,
        ]);
    }
}
