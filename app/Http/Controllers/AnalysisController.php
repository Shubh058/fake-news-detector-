<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $analyses = Analysis::orderBy('created_at', 'desc')->take(20)->get();
        $totalAnalyses = Analysis::count();
        $verifiedCount = Analysis::where('result', 'Verified News')->orWhere('result', 'Trusted Source')->count();
        $fakeCount = Analysis::where('result', 'Unverified')->orWhere('result', 'Possibly Fake')->count();

        return view('dashboard', compact('analyses', 'totalAnalyses', 'verifiedCount', 'fakeCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Analysis $analysis)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Analysis $analysis)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Analysis $analysis)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Analysis $analysis)
    {
        //
    }
}
