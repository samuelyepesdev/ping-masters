<?php

namespace App\Providers;

use App\Models\TournamentMatch;
use App\Policies\MatchPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Named MatchPolicy (not TournamentMatchPolicy) so it needs explicit registration —
        // Laravel's auto-discovery only looks for "{Model}Policy" by convention.
        Gate::policy(TournamentMatch::class, MatchPolicy::class);
    }
}
