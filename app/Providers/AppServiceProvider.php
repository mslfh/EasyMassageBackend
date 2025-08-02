<?php

namespace App\Providers;

use App\Contracts\ServiceContract;
use App\Contracts\VoucherContract;
use App\Repositories\ServiceRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use App\Contracts\PackageContract;
use App\Repositories\PackageRepository;
use App\Contracts\ServiceAppointmentContract;
use App\Repositories\ServiceAppointmentRepository;
use App\Contracts\StaffContract;
use App\Repositories\StaffRepository;
use App\Contracts\ScheduleContract;
use App\Repositories\ScheduleRepository;
use App\Contracts\ScheduleHistoryContract;
use App\Repositories\ScheduleHistoryRepository;
use App\Contracts\OrderContract;
use App\Contracts\UserContract;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Contracts\AppointmentContract;
use App\Repositories\AppointmentRepository;
use App\Contracts\SystemSettingContract;
use App\Repositories\SystemSettingRepository;
use App\Contracts\SmsContract;
use App\Repositories\SmsRepository;
use App\Contracts\NotificationContract;
use App\Repositories\NotificationRepository;
use App\Contracts\UserProfileContract;
use App\Repositories\UserProfileRepository;
use App\Repositories\VoucherRepository;
use App\Contracts\AppointmentLogContract;
use App\Repositories\AppointmentLogRepository;
use App\Contracts\VoucherHistoryContract;
use App\Repositories\VoucherHistoryRepository;

// Import Service classes
use App\Services\AppointmentService;
use App\Services\ServiceAppointmentService;
use App\Services\UserService;
use App\Services\StaffService;
use App\Services\SmsService;
use App\Services\SystemSettingService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\AppointmentLogService;
use App\Services\PackageService;
use App\Services\ServiceService;
use App\Services\ScheduleService;
use App\Services\ScheduleHistoryService;
use App\Services\UserProfileService;
use App\Services\VoucherService;
use App\Services\VoucherHistoryService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(PackageContract::class, PackageRepository::class);
        $this->app->bind(ServiceContract::class, ServiceRepository::class);
        $this->app->bind(ServiceAppointmentContract::class, ServiceAppointmentRepository::class);
        $this->app->bind(StaffContract::class, StaffRepository::class);
        $this->app->bind(ScheduleContract::class, ScheduleRepository::class);
        $this->app->bind(ScheduleHistoryContract::class, ScheduleHistoryRepository::class);
        $this->app->bind(OrderContract::class, OrderRepository::class);
        $this->app->bind(AppointmentContract::class, AppointmentRepository::class);
        $this->app->bind(UserContract::class, UserRepository::class);
        $this->app->bind(SystemSettingContract::class, SystemSettingRepository::class);
        $this->app->bind(SmsContract::class, SmsRepository::class);
        $this->app->bind(NotificationContract::class, NotificationRepository::class);
        $this->app->bind(UserProfileContract::class, UserProfileRepository::class);
        $this->app->bind(VoucherContract::class, VoucherRepository::class);
        $this->app->bind(AppointmentLogContract::class, AppointmentLogRepository::class);
        $this->app->bind(VoucherHistoryContract::class, VoucherHistoryRepository::class);

        // Service bindings
        $this->app->singleton(AppointmentService::class);
        $this->app->singleton(ServiceAppointmentService::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(StaffService::class);
        $this->app->singleton(SmsService::class);
        $this->app->singleton(SystemSettingService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(AppointmentLogService::class);
        $this->app->singleton(PackageService::class);
        $this->app->singleton(ServiceService::class);
        $this->app->singleton(ScheduleService::class);
        $this->app->singleton(ScheduleHistoryService::class);
        $this->app->singleton(UserProfileService::class);
        $this->app->singleton(VoucherService::class);
        $this->app->singleton(VoucherHistoryService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
