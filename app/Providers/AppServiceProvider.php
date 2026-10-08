<?php

namespace App\Providers;

use App\Models\FinalReport;
use App\Models\OutCome;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\User;
use App\Observers\FinalReportObserver;
use App\Observers\OutcomeObserver;
use App\Observers\ProgressReportObserver;
use App\Observers\ProposalObserver;
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
        Proposal::observe(ProposalObserver::class);
        ProgressReport::observe(ProgressReportObserver::class);
        FinalReport::observe(FinalReportObserver::class);
        OutCome::observe(OutcomeObserver::class);

        Relation::enforceMorphMap([
            'user'            => User::class,
            'proposal'        => Proposal::class,
            'progress_report' => ProgressReport::class,
            'final_report'    => FinalReport::class,
            'outcome'          => Outcome::class,
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

        Gate::define('switch-account', function (User $user) {
            return $user->role?->role_code === 'SUPERADMIN' || $user->role?->role_code === 'ADMIN';
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
