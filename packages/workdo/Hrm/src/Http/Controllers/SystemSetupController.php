<?php

namespace Workdo\Hrm\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class SystemSetupController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->can('manage-branches')) {
            return redirect()->route('hrm.branches.index');
        }

        return redirect()->route('hrm.index')->with('error', __('Permission denied'));
    }
}

