<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'proposal'        => \App\Models\Proposal::class,
            'progress_report' => \App\Models\ProgressReport::class,
            'final_report'    => \App\Models\FinalReport::class,
            'output'          => \App\Models\Output::class,
        ]);

        $this->configureDefaults();

        Gate::define('superadmin', function ($user) {
            return $user->role->role_code === "SUPERADMIN";
        });

        Gate::define('admin', function ($user) {
            return $user->role->role_code === "ADMIN";
        });

        Gate::define('superadminOrAdmin', function ($user) {
            return $user->role->role_code === "SUPERADMIN" || $user->role->role_code === "ADMIN";
        });

        Gate::define('user', function ($user) {
            return $user->role->role_code === "USER";
        });

        Gate::define('reviewer', function ($user) {
            return $user->role->role_code === "REVIEWER";
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn(): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
