<?php

namespace App\Providers;

use App\Listeners\LogSuccessfulLogin;
use App\Models\AdminNote;
use App\Models\BudgetProposal;
use App\Models\Download;
use App\Models\ExternalProposal;
use App\Models\FinalReport;
use App\Models\Informations;
use App\Models\Outcome;
use App\Models\Period;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\ProposalStudent;
use App\Models\ResearchScheme;
use App\Models\ReviewerNote;
use App\Models\User;
use App\Observers\FinalReportObserver;
use App\Observers\OutcomeObserver;
use App\Observers\ProgressReportObserver;
use App\Observers\ProposalObserver;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        Event::listen(
            Login::class,
            LogSuccessfulLogin::class,
        );
        Proposal::observe(ProposalObserver::class);
        ProgressReport::observe(ProgressReportObserver::class);
        FinalReport::observe(FinalReportObserver::class);
        OutCome::observe(OutcomeObserver::class);

        Relation::enforceMorphMap([
            // ── Core / Auth ──
            'user'               => User::class,
            'role'               => Role::class,

            // ── Master Data ──
            'period'             => Period::class,
            'research_scheme'    => ResearchScheme::class,

            // ── Proposal & Turunan ──
            'proposal'           => Proposal::class,
            'budget_proposal'    => BudgetProposal::class,
            'proposal_member'    => ProposalMember::class,
            'proposal_student'   => ProposalStudent::class,

            // ── Laporan & Outcome ──
            'progress_report'    => ProgressReport::class,
            'final_report'       => FinalReport::class,
            'outcome'            => Outcome::class,

            // ── Catatan (polymorphic) ──
            'admin_note'         => AdminNote::class,
            'reviewer_note'      => ReviewerNote::class,

            // ── Konten & Publikasi ──
            'informations'       => Informations::class,
            'download'           => Download::class,

            // ── Penelitian Eksternal ──
            'external_proposal'  => ExternalProposal::class,
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
