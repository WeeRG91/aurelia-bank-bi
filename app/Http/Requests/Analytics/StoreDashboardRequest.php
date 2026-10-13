<?php

namespace App\Http\Requests\Analytics;

use App\Models\Dashboard;
use Illuminate\Support\Facades\Gate;

class StoreDashboardRequest extends DashboardRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->allows('create', Dashboard::class);
    }
}
