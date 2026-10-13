<?php

namespace App\Models;

use App\Analytics\Dashboards\DashboardWidgetLayout;
use App\Analytics\Dashboards\DashboardWidgetType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'dashboard_id',
    'saved_report_id',
    'title',
    'widget_type',
    'grid_column',
    'grid_row',
    'grid_width',
    'grid_height',
    'configuration',
])]
final class DashboardWidget extends Model
{
    /**
     * @return BelongsTo<Dashboard, $this>
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * @return BelongsTo<SavedReport, $this>
     */
    public function savedReport(): BelongsTo
    {
        return $this->belongsTo(SavedReport::class);
    }

    public function layout(): DashboardWidgetLayout
    {
        return new DashboardWidgetLayout(
            column: $this->grid_column,
            row: $this->grid_row,
            width: $this->grid_width,
            height: $this->grid_height,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'widget_type' => DashboardWidgetType::class,
            'grid_column' => 'integer',
            'grid_row' => 'integer',
            'grid_width' => 'integer',
            'grid_height' => 'integer',
            'configuration' => 'array',
        ];
    }
}
