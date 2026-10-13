<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Resources\Analytics\DashboardResource;
use App\Models\Dashboard;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
    ): AnonymousResourceCollection {
        Gate::authorize('viewAny', Dashboard::class);

        /** @var User $user */
        $user = $request->user();

        /** @var Employee $employee */
        $employee = $user->employee;

        $dashboards = $employee
            ->dashboards()
            ->withCount('widgets')
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return DashboardResource::collection($dashboards);
    }
}
