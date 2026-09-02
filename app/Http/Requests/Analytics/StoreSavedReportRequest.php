<?php

namespace App\Http\Requests\Analytics;

use App\Models\SavedReport;
use Illuminate\Support\Facades\Gate;

final class StoreSavedReportRequest extends SavedReportRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return parent::authorize()
            && Gate::forUser($this->user())
                ->allows(
                    'create',
                    SavedReport::class
                );
    }
}
