<?php

namespace App\Providers;

use App\Repositories\AttendanceRepository;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\GuardAssignmentRepositoryInterface;
use App\Repositories\Contracts\GuardRepositoryInterface;
use App\Repositories\Contracts\IncidentRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\PayrollRepositoryInterface;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Repositories\Contracts\ShiftRepositoryInterface;
use App\Repositories\Contracts\SiteRepositoryInterface;
use App\Repositories\Contracts\VisitorRepositoryInterface;
use App\Repositories\GuardAssignmentRepository;
use App\Repositories\GuardRepository;
use App\Repositories\IncidentRepository;
use App\Repositories\LeaveRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\SettingRepository;
use App\Repositories\ShiftRepository;
use App\Repositories\SiteRepository;
use App\Repositories\VisitorRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SiteRepositoryInterface::class, SiteRepository::class);
        $this->app->bind(GuardRepositoryInterface::class, GuardRepository::class);
        $this->app->bind(ShiftRepositoryInterface::class, ShiftRepository::class);
        $this->app->bind(GuardAssignmentRepositoryInterface::class, GuardAssignmentRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);
        $this->app->bind(VisitorRepositoryInterface::class, VisitorRepository::class);
        $this->app->bind(IncidentRepositoryInterface::class, IncidentRepository::class);
        $this->app->bind(LeaveRepositoryInterface::class, LeaveRepository::class);
        $this->app->bind(PayrollRepositoryInterface::class, PayrollRepository::class);
        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
