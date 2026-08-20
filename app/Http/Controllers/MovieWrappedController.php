<?php

namespace App\Http\Controllers;

use App\Services\MovieWrappedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovieWrappedController extends Controller
{
    protected MovieWrappedService $wrappedService;

    public function __construct(MovieWrappedService $wrappedService)
    {
        $this->wrappedService = $wrappedService;
    }

    /**
     * Display the interactive Movie Wrapped experience
     */
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan masuk untuk melihat kilas balik Movie Wrapped Anda.');
        }

        $user = Auth::user();
        $period = $request->query('period', 'year');
        $year = (int)$request->query('year', date('Y'));
        $month = (int)$request->query('month', date('n'));

        $wrappedData = $this->wrappedService->generateWrappedData($user, $period, $year, $month);

        return view('wrapped.index', [
            'wrapped' => $wrappedData,
            'period'  => $period,
            'year'    => $year,
            'month'   => $month,
        ]);
    }

    /**
     * API endpoint to dynamically load statistics for different periods
     */
    public function apiStats(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $period = $request->query('period', 'year');
        $year = (int)$request->query('year', date('Y'));
        $month = (int)$request->query('month', date('n'));

        $wrappedData = $this->wrappedService->generateWrappedData($user, $period, $year, $month);

        return response()->json([
            'success' => true,
            'data'    => $wrappedData,
        ]);
    }
}
