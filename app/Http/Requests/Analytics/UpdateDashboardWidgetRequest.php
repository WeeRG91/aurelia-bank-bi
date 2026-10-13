<?php

namespace App\Http\Requests\Analytics;

use App\Models\Dashboard;
use App\Models\DashboardWidget;
use Illuminate\Support\Facades\Gate;

final class UpdateDashboardWidgetRequest extends DashboardWidgetRequest
{
    public function authorize(): bool
    {
        $dashboard = $this->route('dashboard');
        $widget = $this->route('dashboardWidget');

        return $dashboard instanceof Dashboard
            && $widget instanceof DashboardWidget
            && $widget->dashboard_id === $dashboard->getKey()
            && Gate::forUser($this->user())
                ->allows('update', $widget);
    }
}
