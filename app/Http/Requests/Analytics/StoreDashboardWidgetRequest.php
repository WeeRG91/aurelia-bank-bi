<?php

namespace App\Http\Requests\Analytics;

use App\Models\Dashboard;
use Illuminate\Support\Facades\Gate;

final class StoreDashboardWidgetRequest extends DashboardWidgetRequest
{
    public function authorize(): bool
    {
        $dashboard = $this->route('dashboard');

        return $dashboard instanceof Dashboard
            && Gate::forUser($this->user())
                ->allows('addWidget', $dashboard);
    }
}
