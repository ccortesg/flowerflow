<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(config('flowerflow.flags.public'), 404);

        $competition = Competition::query()->with('categories')->where('active', true)->first();

        return view('public.landing', compact('competition'));
    }
}
