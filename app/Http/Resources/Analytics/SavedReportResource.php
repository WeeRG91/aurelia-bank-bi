<?php

namespace App\Http\Resources\Analytics;

use App\Models\SavedReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SavedReport
 */
final class SavedReportResource extends JsonResource
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
            'dataset' => $this->dataset->value,
            'definitionVersion' => $this->definition_version,
            'definition' => $this->definition,
            'createdAt' => $this->created_at?->toAtomString(),
            'updatedAt' => $this->updated_at?->toAtomString(),
        ];
    }
}
