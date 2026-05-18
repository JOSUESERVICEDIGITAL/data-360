<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\Recherche;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index()
    {
        return view('back.maintenance.index');
    }

    public function clearRecherches()
    {
        Recherche::query()->delete();

        return back()->with('success', 'Historique des recherches vidé.');
    }

    public function clearCache()
    {
        DB::table('cache')->delete();

        return back()->with('success', 'Cache base de données vidé.');
    }

    public function clearJobs()
    {
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();

        return back()->with('success', 'Jobs et failed_jobs vidés.');
    }
}