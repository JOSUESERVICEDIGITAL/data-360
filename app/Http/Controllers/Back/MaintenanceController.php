<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\Adresse;
use App\Models\Back\Batiment;
use App\Models\Back\Copropriete;
use App\Models\Back\Recherche;
use App\Models\Back\Syndic;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index()
    {
        return view('back.maintenance.index', [
            'recherchesCount'   => DB::table('recherches')->count(),
            'cacheCount'        => DB::table('cache')->count(),
            'jobsCount'         => DB::table('jobs')->count(),
            'failedJobsCount'   => DB::table('failed_jobs')->count(),
            'adressesCount'     => Adresse::count(),
            'batimentsCount'    => Batiment::count(),
            'coproprietesCount' => Copropriete::count(),
            'syndicsCount'      => Syndic::count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RECHERCHES
    |--------------------------------------------------------------------------
    */

    public function clearRecherches()
    {
        try {

            DB::table('recherches')->delete();

            return back()->with(
                'success',
                'Historique des recherches supprimé.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'Erreur suppression recherches : '.$e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE
    |--------------------------------------------------------------------------
    */

    public function clearCache()
    {
        try {

            DB::table('cache')->delete();

            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return back()->with(
                'success',
                'Cache Laravel et DB vidé.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'Erreur cache : '.$e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | JOBS
    |--------------------------------------------------------------------------
    */

    public function clearJobs()
    {
        try {

            DB::table('jobs')->delete();

            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                DB::table('failed_jobs')->delete();
            }

            return back()->with(
                'success',
                'Jobs et failed_jobs supprimés.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'Erreur jobs : '.$e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OPTIMIZE
    |--------------------------------------------------------------------------
    */

    public function optimize()
    {
        try {

            Artisan::call('optimize:clear');

            return back()->with(
                'success',
                'Laravel optimisé avec succès.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'Erreur optimisation : '.$e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RESET TABLES SaaS
    |--------------------------------------------------------------------------
    */

    public function resetAdresses()
    {
        try {

            Adresse::query()->delete();

            return back()->with(
                'success',
                'Toutes les adresses ont été supprimées.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    public function resetBatiments()
    {
        try {

            Batiment::query()->delete();

            return back()->with(
                'success',
                'Tous les bâtiments ont été supprimés.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    public function resetCoproprietes()
    {
        try {

            Copropriete::query()->delete();

            return back()->with(
                'success',
                'Toutes les copropriétés ont été supprimées.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    public function resetSyndics()
    {
        try {

            Syndic::query()->delete();

            return back()->with(
                'success',
                'Tous les syndics ont été supprimés.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }
}