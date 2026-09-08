<?php

namespace App\Providers;

use App\Enums\EmployeeRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request): bool {
            $user = $request->user();

            return $user instanceof User
                && Gate::forUser($user)
                    ->allows('viewHorizon');
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define(
            'viewHorizon',
            static fn (?User $user = null): bool => $user?->isActiveEmployee() === true
                && $user->employee?->role
                    === EmployeeRole::ADMINISTRATOR,
        );
    }
}
