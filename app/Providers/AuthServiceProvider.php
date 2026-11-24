<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // global job posting templates & themes: DEF-MASTERADMIN only (DEF-ADMIN is not enough)
        Gate::define('manage-job-templates', fn ($user) => ($user->role_code ?? null) === 'DEF-MASTERADMIN');
        // global announcements: DEF-ADMIN and DEF-MASTERADMIN write them (everybody signed in reads the live ones)
        Gate::define('manage-announcements', fn ($user) => in_array($user->role_code ?? null, \App\Services\AnnouncementService::ADMIN_ROLES, true));
    }
}
