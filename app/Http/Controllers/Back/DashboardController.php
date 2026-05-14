<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Syndic;
use App\Models\Back\Recherche;

class DashboardController extends Controller
{
    public function index()
    {
        return view('back.dashboard', [
            'totalAdresses' => Adresse::count(),
            'totalBatiments' => Batiment::count(),
            'totalCoproprietes' => Copropriete::count(),
            'totalSyndics' => Syndic::count(),
            'totalRecherches' => Recherche::count(),
            'dernieresRecherches' => Recherche::with('adresse', 'user')
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}