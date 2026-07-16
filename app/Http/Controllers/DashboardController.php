<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (request()->user()->hasAnyRole(['admin', 'reviewer'])) {
            return redirect()->route('panel.dashboard');
        }

        $submissions = request()->user()->submissions()->with('category')->latest()->get();

        return view('participant.dashboard', compact('submissions'));
    }
}
