<?php

namespace App\Http\Resources\Analytics;

use App\Models\DashboardWidget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DashboardWidget
 */
final class DashboardWidgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'dashboardId' => $this->dashboard_id,
            'savedReportId' => $this->saved_report_id,
            'title' => $this->title,
            'type' => $this->widget_type->value,
            'layout' => $this->layout()->toArray(),
            'configuration' => $this->configuration,
            'createdAt' => $this->created_at?->toAtomString(),
            'updatedAt' => $this->updated_at?->toAtomString(),
            'updateUrl' => route(
                'analytics.dashboards.widgets.update',
                [
                    'dashboard' => $this->dashboard_id,
                    'dashboardWidget' => $this->resource,
                ],
            ),
            'destroyUrl' => route(
                'analytics.dashboards.widgets.destroy',
                [
                    'dashboard' => $this->dashboard_id,
                    'dashboardWidget' => $this->resource,
                ],
            ),
        ];
    }
}
