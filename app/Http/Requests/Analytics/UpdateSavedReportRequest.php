<?php

namespace App\Http\Requests\Analytics;

use App\Models\SavedReport;
use Illuminate\Support\Facades\Gate;

final class UpdateSavedReportRequest extends SavedReportRequest
{
    public function authorize(): bool
    {
        $savedReport = $this->route('savedReport');

        return parent::authorize()
            && $savedReport instanceof SavedReport
            && Gate::forUser($this->user())
                ->allows('update', $savedReport);
    }
}
