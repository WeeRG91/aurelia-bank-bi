<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Resources\Analytics\DashboardResource;
use App\Models\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DashboardShowController extends Controller
{
    public function __invoke(
        Request $request,
        Dashboard $dashboard,
    ): DashboardResource {
        Gate::forUser($request->user())
            ->authorize('view', $dashboard);

        $dashboard->loadCount('widgets');
        $dashboard->load('widgets');

        return new DashboardResource($dashboard);
    }
}
