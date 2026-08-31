<?php

namespace App\Http\Controllers;

use App\Authorization\BranchAccess;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(
        Request $request,
        BranchAccess $branchAccess,
    ): View {
        Gate::authorize('viewAny', Branch::class);

        /** @var User $user */
        $user = $request->user();

        $branches = $branchAccess
            ->queryFor($user)
            ->orderBy('branch_code')
            ->paginate(25);

        return view('branches.index', [
            'branches' => $branches,
        ]);
    }

    public function show(
        Request $request,
        int $branch,
        BranchAccess $branchAccess,
    ): View {
        /** @var User $user */
        $user = $request->user();

        $visibleBranch = $branchAccess
            ->queryFor($user)
            ->findOrFail($branch);

        Gate::authorize('view', $visibleBranch);

        return view('branches.show', [
            'branch' => $visibleBranch,
        ]);
    }
}
