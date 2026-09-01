<?php

namespace App\Http\Controllers;

use App\Authorization\EmployeeAccess;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(
        Request $request,
        EmployeeAccess $employeeAccess,
    ): View {
        Gate::authorize('viewAny', Employee::class);

        /** @var User $user */
        $user = $request->user();

        $employees = $employeeAccess
            ->queryFor($user)
            ->with([
                'user:id,name,email',
                'branch:id,branch_code,name',
            ])
            ->orderBy('employee_number')
            ->paginate(25);

        return view('employees.index', [
            'employees' => $employees,
        ]);
    }

    public function profile(
        Request $request,
        EmployeeAccess $employeeAccess,
    ): View {
        /** @var User $user */
        $user = $request->user();

        $employee = $employeeAccess
            ->queryFor($user)
            ->where('employees.user_id', $user->getKey())
            ->firstOrFail();

        Gate::authorize('view', $employee);

        return $this->showView($employee);
    }

    public function show(
        Request $request,
        int $employee,
        EmployeeAccess $employeeAccess,
    ): View {
        /** @var User $user */
        $user = $request->user();

        $visibleEmployee = $employeeAccess
            ->queryFor($user)
            ->findOrFail($employee);

        Gate::authorize('view', $visibleEmployee);

        return $this->showView($visibleEmployee);
    }

    private function showView(Employee $employee): View
    {
        $employee->loadMissing([
            'user:id,name,email',
            'branch:id,branch_code,name',
        ]);

        return view('employees.show', [
            'employee' => $employee,
        ]);
    }
}
