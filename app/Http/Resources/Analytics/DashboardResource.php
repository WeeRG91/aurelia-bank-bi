<?php

namespace App\Http\Resources\Analytics;

use App\Models\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Dashboard
 */
final class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'description' => $this->description,
            'isDefault' => $this->is_default,
            'widgetCount' => $this->whenCounted('widgets'),
            'widgets' => DashboardWidgetResource::collection(
                $this->whenLoaded('widgets'),
            ),
            'createdAt' => $this->created_at?->toAtomString(),
            'updatedAt' => $this->updated_at?->toAtomString(),
            'showUrl' => route(
                'analytics.dashboards.show',
                $this->resource,
            ),
            'resultsUrl' => route(
                'analytics.dashboards.results',
                $this->resource,
            ),
            'widgetStoreUrl' => route(
                'analytics.dashboards.widgets.store',
                $this->resource,
            ),
            'updateUrl' => route(
                'analytics.dashboards.update',
                $this->resource,
            ),
            'destroyUrl' => route(
                'analytics.dashboards.destroy',
                $this->resource,
            ),
        ];
    }
}
