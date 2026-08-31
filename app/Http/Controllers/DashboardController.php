<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $user->loadMissing('employee.branch');

        return view('dashboard', [
            'user' => $user,
        ]);
    }
}
