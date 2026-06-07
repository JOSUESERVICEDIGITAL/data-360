<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Http\Requests\Back\SuperAdmin\BulkCreditsRequest;
use App\Http\Requests\Back\SuperAdmin\BulkPlanRequest;
use App\Http\Requests\Back\SuperAdmin\BroadcastNotificationRequest;
use App\Http\Requests\Back\SuperAdmin\PurgeRecherchesRequest;
use App\Http\Requests\Back\SuperAdmin\PurgeImportsRequest;
use App\Http\Requests\Back\SuperAdmin\PurgeSessionsRequest;
use App\Http\Requests\Back\SuperAdmin\PurgeLogsRequest;
use App\Http\Requests\Back\SuperAdmin\ToggleMaintenanceRequest;
use App\Models\User;
use App\Models\Back\CsvImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    // ────────────────────────────────────────────────────────────
    // GUARD — toutes les méthodes vérifient le superadmin
    // ────────────────────────────────────────────────────────────
    private function guardSuperAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->isSuperAdmin()) {
            abort(403, 'Accès réservé au superadmin.');
        }
    }

    // ────────────────────────────────────────────────────────────
    // DASHBOARD PRINCIPAL
    // ────────────────────────────────────────────────────────────
    public function index()
    {
        $this->guardSuperAdmin();

        $totalUsers     = User::count();
        $activeUsers    = User::where('is_active', true)->count();
        $adminUsers     = User::where('is_admin', true)->count();
        $superAdmins    = User::where('is_superadmin', true)->count();
        $premiumUsers   = User::whereIn('plan', ['premium', 'enterprise'])->count();
        $freeUsers      = User::where('plan', 'free')->count();
        $totalCredits   = User::sum('credits');

        $recentLogins   = User::whereNotNull('last_login_at')
                            ->orderByDesc('last_login_at')
                            ->take(15)
                            ->get();

        $newUsersToday  = User::whereDate('created_at', today())->count();
        $newUsersWeek   = User::where('created_at', '>=', now()->subDays(7))->count();
        $newUsersMonth  = User::where('created_at', '>=', now()->subDays(30))->count();

        // Jobs en queue
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs  = 0;
        try { $failedJobs = DB::table('failed_jobs')->count(); } catch (\Throwable) {}

        // Sessions actives
        $activeSessions = 0;
        try { $activeSessions = DB::table('sessions')->count(); } catch (\Throwable) {}

        // Imports CSV
        $totalImports   = 0;
        $pendingImports = 0;
        try {
            $totalImports   = CsvImport::count();
            $pendingImports = CsvImport::whereIn('statut', ['en_attente', 'en_cours'])->count();
        } catch (\Throwable) {}

        return view('back.security.superadmin.index', compact(
            'totalUsers', 'activeUsers', 'adminUsers', 'superAdmins',
            'premiumUsers', 'freeUsers', 'totalCredits',
            'recentLogins', 'newUsersToday', 'newUsersWeek', 'newUsersMonth',
            'pendingJobs', 'failedJobs', 'activeSessions',
            'totalImports', 'pendingImports'
        ));
    }

    // ────────────────────────────────────────────────────────────
    // UTILISATEURS
    // ────────────────────────────────────────────────────────────

    /**
     * Liste tous les utilisateurs avec stats avancées
     */
    public function users(Request $request)
    {
        $this->guardSuperAdmin();

        $query = User::query();

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(fn($q) =>
                $q->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
            );
        }

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        if ($request->filled('role')) {
            match($request->role) {
                'superadmin' => $query->where('is_superadmin', true),
                'admin'      => $query->where('is_admin', true),
                'free'       => $query->where('is_admin', false),
                default      => null
            };
        }

        $users = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('back.security.superadmin.users', compact('users'));
    }

    /**
     * Promouvoir un user en superadmin
     */
    public function makeSuperAdmin(User $user)
    {
        $this->guardSuperAdmin();

        if ($user->isSuperAdmin()) {
            return back()->with('error', "{$user->name} est déjà superadmin.");
        }

        $user->update(['is_superadmin' => true, 'is_admin' => true]);

        Log::info("SuperAdmin: {$user->name} promu superadmin par " . Auth::user()->name);

        return back()->with('success', "{$user->name} est maintenant superadmin.");
    }

    /**
     * Rétrograder un superadmin
     */
    public function removeSuperAdmin(User $user)
    {
        $this->guardSuperAdmin();

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre statut superadmin.');
        }

        $user->update(['is_superadmin' => false]);

        Log::info("SuperAdmin: {$user->name} rétrogradé par " . Auth::user()->name);

        return back()->with('success', "{$user->name} n'est plus superadmin.");
    }

    /**
     * Forcer la réinitialisation du mot de passe d'un utilisateur
     */
    public function forcePasswordReset(User $user)
    {
        $this->guardSuperAdmin();

        $newPassword = \Illuminate\Support\Str::random(12);
        $user->update(['password' => bcrypt($newPassword)]);

        Log::warning("SuperAdmin: mot de passe réinitialisé pour {$user->name} par " . Auth::user()->name);

        return back()->with('success', "Mot de passe réinitialisé. Nouveau : {$newPassword}");
    }

    /**
     * Impersonate (connexion en tant qu'utilisateur)
     */
    public function impersonate(User $user)
    {
        $this->guardSuperAdmin();

        if ($user->isSuperAdmin() && $user->id !== Auth::id()) {
            return back()->with('error', 'Impossible d\'usurper l\'identité d\'un autre superadmin.');
        }

        session(['superadmin_impersonating' => Auth::id()]);
        Auth::login($user);

        Log::warning("SuperAdmin: impersonate de {$user->name} par superadmin #" . session('superadmin_impersonating'));

        return redirect()->route('front.home')
            ->with('info', "Vous êtes maintenant connecté en tant que {$user->name}.");
    }

    /**
     * Quitter l'impersonate
     */
    public function stopImpersonate()
    {
        $originalId = session('superadmin_impersonating');

        if (!$originalId) {
            return redirect()->route('admin.superadmin.index');
        }

        $superAdmin = User::find($originalId);

        if (!$superAdmin) {
            Auth::logout();
            return redirect()->route('login');
        }

        Auth::login($superAdmin);
        session()->forget('superadmin_impersonating');

        return redirect()->route('admin.superadmin.index')
            ->with('success', 'Vous avez repris votre session superadmin.');
    }

    /**
     * Attribution de crédits en masse
     */
    public function bulkCredits(BulkCreditsRequest $request)
    {
        $this->guardSuperAdmin();

        $data = $request->validated();

        $query = User::query();

        match($data['target']) {
            'free'       => $query->where('plan', 'free'),
            'premium'    => $query->where('plan', 'premium'),
            'enterprise' => $query->where('plan', 'enterprise'),
            'specific'   => $query->whereIn('id', $data['user_ids'] ?? []),
            default      => null
        };

        $count = match($data['action']) {
            'add'   => $query->increment('credits', (int) $data['amount']),
            'set'   => $query->update(['credits' => (int) $data['amount']]),
            'reset' => $query->update(['credits' => 0]),
        };

        Log::info("SuperAdmin: bulk credits [{$data['action']} {$data['amount']}] sur [{$data['target']}] — {$count} users");

        return back()->with('success', "{$count} utilisateur(s) mis à jour.");
    }

    /**
     * Changer le plan en masse
     */
    public function bulkPlan(BulkPlanRequest $request)
    {
        $this->guardSuperAdmin();

        $data = $request->validated();

        $query = User::where('is_superadmin', false);

        if ($data['from_plan'] !== 'all') {
            $query->where('plan', $data['from_plan']);
        }

        $count = $query->update(['plan' => $data['to_plan']]);

        Log::info("SuperAdmin: bulk plan [{$data['from_plan']} → {$data['to_plan']}] — {$count} users");

        return back()->with('success', "{$count} utilisateur(s) passés au plan {$data['to_plan']}.");
    }

    // ────────────────────────────────────────────────────────────
    // HISTORIQUES
    // ────────────────────────────────────────────────────────────

    /**
     * Historique des connexions avec filtres
     */
    public function connexionsHistory(Request $request)
    {
        $this->guardSuperAdmin();

        $query = User::whereNotNull('last_login_at');

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(fn($q) =>
                $q->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%")
                  ->orWhere('last_login_ip', 'like', "%$s%")
            );
        }

        if ($request->filled('status')) {
            if ($request->status === 'online') {
                $query->where('last_login_at', '>=', now()->subMinutes(30));
            } else {
                $query->where('last_login_at', '<', now()->subMinutes(30));
            }
        }

        $users = $query->orderByDesc('last_login_at')->paginate(30)->withQueryString();

        return response()->json([
            'data'  => $users->items(),
            'total' => $users->total(),
            'pages' => $users->lastPage(),
        ]);
    }

    /**
     * Historique des imports CSV
     */
    public function importsHistory(Request $request)
    {
        $this->guardSuperAdmin();

        try {
            $query = CsvImport::with('user')->latest();

            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            $imports = $query->paginate(30)->withQueryString();

            return response()->json(['success' => true, 'data' => $imports]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────
    // PAIEMENTS
    // ────────────────────────────────────────────────────────────

    /**
     * Vue paiements
     */
    public function payments()
    {
        $this->guardSuperAdmin();

        // Statistiques depuis les transactions en base (si table existe)
        $stats = [];
        try {
            $stats = [
                'total_transactions' => DB::table('credit_transactions')->count(),
                'total_amount'       => DB::table('credit_transactions')->sum('amount') ?? 0,
                'this_month'         => DB::table('credit_transactions')
                                          ->where('created_at', '>=', now()->startOfMonth())
                                          ->sum('amount') ?? 0,
                'recent'             => DB::table('credit_transactions')
                                          ->latest()
                                          ->take(20)
                                          ->get(),
            ];
        } catch (\Throwable) {
            $stats = [
                'total_transactions' => 0,
                'total_amount'       => 0,
                'this_month'         => 0,
                'recent'             => collect(),
            ];
        }

        return view('back.security.superadmin.payments', compact('stats'));
    }

    // ────────────────────────────────────────────────────────────
    // MAINTENANCE BASE DE DONNÉES
    // ────────────────────────────────────────────────────────────

    /**
     * Stats des tables de la base de données
     */
    public function dbStats()
    {
        $this->guardSuperAdmin();

        try {
            $dbName = config('database.connections.mysql.database');

            $tables = DB::select("
                SELECT
                    TABLE_NAME        AS `table`,
                    TABLE_ROWS        AS `rows`,
                    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS `size_mb`,
                    ROUND(DATA_LENGTH / 1024 / 1024, 2)  AS `data_mb`,
                    ROUND(INDEX_LENGTH / 1024 / 1024, 2) AS `index_mb`,
                    CREATE_TIME       AS `created_at`,
                    UPDATE_TIME       AS `updated_at`
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
            ", [$dbName]);

            $totalSizeMb = collect($tables)->sum('size_mb');

            return response()->json([
                'success'       => true,
                'tables'        => $tables,
                'total_size_mb' => $totalSizeMb,
                'db_name'       => $dbName,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Purger les recherches anciennes
     */
    public function purgeRecherches(PurgeRecherchesRequest $request)
    {
        $this->guardSuperAdmin();

        $data = $request->validated();

        try {
            $query = DB::table('recherches');

            $deleted = match($data['period']) {
                '7days'  => $query->where('created_at', '<', now()->subDays(7))->delete(),
                '30days' => $query->where('created_at', '<', now()->subDays(30))->delete(),
                '90days' => $query->where('created_at', '<', now()->subDays(90))->delete(),
                'all'    => $query->delete(),
            };

            Log::warning("SuperAdmin: purge recherches [{$data['period']}] — {$deleted} lignes supprimées par " . Auth::user()->name);

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => "{$deleted} recherche(s) supprimée(s).",
            ]);
        } catch (\Throwable $e) {
            Log::error("SuperAdmin: purge recherches ERREUR — " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Purger les imports CSV (vider csv_content et xlsx_content)
     */
    public function purgeImports(PurgeImportsRequest $request)
    {
        $this->guardSuperAdmin();

        $data = $request->validated();

        try {
            $query = CsvImport::query();

            $count = match($data['mode']) {
                'terminated' => (function() use ($query) {
                    $q = $query->where('statut', 'termine');
                    $c = $q->count();
                    $q->update(['csv_content' => null, 'xlsx_content' => null]);
                    return $c;
                })(),
                'older30' => (function() use ($query) {
                    $q = $query->where('created_at', '<', now()->subDays(30));
                    $c = $q->count();
                    $q->update(['csv_content' => null, 'xlsx_content' => null]);
                    return $c;
                })(),
                'all' => (function() use ($query) {
                    $c = CsvImport::count();
                    CsvImport::query()->update(['csv_content' => null, 'xlsx_content' => null]);
                    return $c;
                })(),
                'delete_all' => (function() use ($query) {
                    $q = $query->where('created_at', '<', now()->subDays(30));
                    $c = $q->count();
                    $q->delete();
                    return $c;
                })(),
            };

            Log::warning("SuperAdmin: purge imports [{$data['mode']}] — {$count} lignes traitées par " . Auth::user()->name);

            return response()->json([
                'success' => true,
                'count'   => $count,
                'message' => "{$count} import(s) traité(s).",
            ]);
        } catch (\Throwable $e) {
            Log::error("SuperAdmin: purge imports ERREUR — " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Vider les sessions expirées
     */
    public function purgeSessions(PurgeSessionsRequest $request)
    {
        $this->guardSuperAdmin();

        try {
            $deleted = DB::table('sessions')
                ->where('last_activity', '<', now()->subHours(24)->timestamp)
                ->delete();

            Log::warning("SuperAdmin: purge sessions — {$deleted} sessions supprimées par " . Auth::user()->name);

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => "{$deleted} session(s) expirée(s) supprimée(s).",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Vider les failed_jobs
     */
    public function purgeFailedJobs(Request $request)
    {
        $this->guardSuperAdmin();

        $request->validate(['confirm' => 'required|in:CONFIRMER']);

        try {
            Artisan::call('queue:flush');
            $output = Artisan::output();

            Log::warning("SuperAdmin: purge failed_jobs par " . Auth::user()->name);

            return response()->json(['success' => true, 'message' => 'Failed jobs vidés.', 'output' => $output]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Vider les logs Laravel
     */
    public function purgeLogs(PurgeLogsRequest $request)
    {
        $this->guardSuperAdmin();

        try {
            $logPath = storage_path('logs');
            $deleted = 0;

            foreach (glob($logPath . '/*.log') as $file) {
                if (is_file($file) && basename($file) !== 'laravel.log') {
                    unlink($file);
                    $deleted++;
                }
            }

            // Vider le log principal sans le supprimer
            file_put_contents(storage_path('logs/laravel.log'), '');

            Log::info("SuperAdmin: logs vidés — {$deleted} fichiers supprimés par " . Auth::user()->name);

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => "Logs vidés ({$deleted} fichier(s) supprimé(s)).",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Vider les anciennes données de cache en base
     */
    public function purgeCache(Request $request)
    {
        $this->guardSuperAdmin();

        try {
            $deleted = DB::table('cache')
                ->where('expiration', '<', now()->timestamp)
                ->delete();

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
                'message' => "{$deleted} entrée(s) de cache expirée(s) supprimée(s).",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────
    // CACHE ARTISAN
    // ────────────────────────────────────────────────────────────

    /**
     * Exécuter une commande artisan de cache
     */
    public function clearCache(Request $request)
    {
        $this->guardSuperAdmin();

        $data = $request->validate([
            'command' => 'required|in:cache:clear,config:clear,route:clear,view:clear,optimize:clear,config:cache,route:cache,view:cache',
        ]);

        try {
            Artisan::call($data['command']);
            $output = trim(Artisan::output()) ?: 'Commande exécutée avec succès.';

            Log::info("SuperAdmin: artisan [{$data['command']}] par " . Auth::user()->name);

            return response()->json(['success' => true, 'output' => $output, 'command' => $data['command']]);
        } catch (\Throwable $e) {
            Log::error("SuperAdmin: artisan [{$data['command']}] ERREUR — " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────
    // PERFORMANCES & MONITORING
    // ────────────────────────────────────────────────────────────

    /**
     * Métriques de performance globales
     */
    public function performanceMetrics()
    {
        $this->guardSuperAdmin();

        try {
            $dbName = config('database.connections.mysql.database');

            // Taille totale DB
            $dbSize = DB::selectOne("
                SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
            ", [$dbName]);

            // Tables volumineuses
            $heavyTables = DB::select("
                SELECT TABLE_NAME AS `table`,
                       TABLE_ROWS AS `rows`,
                       ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
                LIMIT 10
            ", [$dbName]);

            // Queue stats
            $queueStats = [
                'pending'  => DB::table('jobs')->count(),
                'failed'   => 0,
                'sessions' => 0,
            ];

            try { $queueStats['failed']   = DB::table('failed_jobs')->count(); } catch (\Throwable) {}
            try { $queueStats['sessions'] = DB::table('sessions')->count(); } catch (\Throwable) {}

            // Memory PHP
            $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
            $memoryPeak  = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

            // Log size
            $logSize = 0;
            $logPath = storage_path('logs/laravel.log');
            if (file_exists($logPath)) {
                $logSize = round(filesize($logPath) / 1024 / 1024, 2);
            }

            return response()->json([
                'success'      => true,
                'db_size_mb'   => $dbSize->size_mb ?? 0,
                'heavy_tables' => $heavyTables,
                'queue'        => $queueStats,
                'memory_mb'    => $memoryUsage,
                'memory_peak'  => $memoryPeak,
                'log_size_mb'  => $logSize,
                'php_version'  => PHP_VERSION,
                'laravel_version' => app()->version(),
                'env'          => config('app.env'),
                'timestamp'    => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Stats utilisateurs par période (graphiques)
     */
    public function userGrowthStats()
    {
        $this->guardSuperAdmin();

        try {
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $months[] = [
                    'month'  => $date->format('M Y'),
                    'users'  => User::whereYear('created_at', $date->year)
                                    ->whereMonth('created_at', $date->month)
                                    ->count(),
                    'premium'=> User::whereIn('plan', ['premium','enterprise'])
                                    ->whereYear('created_at', $date->year)
                                    ->whereMonth('created_at', $date->month)
                                    ->count(),
                ];
            }

            return response()->json(['success' => true, 'data' => $months]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Taille des logs
     */
    public function logsInfo()
    {
        $this->guardSuperAdmin();

        try {
            $logPath = storage_path('logs');
            $files   = [];

            foreach (glob($logPath . '/*.log') as $file) {
                $files[] = [
                    'name'     => basename($file),
                    'size_kb'  => round(filesize($file) / 1024, 1),
                    'modified' => date('d/m/Y H:i', filemtime($file)),
                ];
            }

            usort($files, fn($a, $b) => $b['size_kb'] <=> $a['size_kb']);

            $totalKb = collect($files)->sum('size_kb');

            return response()->json([
                'success'  => true,
                'files'    => $files,
                'total_kb' => $totalKb,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Stats de la queue en temps réel
     */
    public function queueStats()
    {
        $this->guardSuperAdmin();

        try {
            $pending  = DB::table('jobs')->count();
            $failed   = 0;
            $byQueue  = DB::table('jobs')->select('queue', DB::raw('count(*) as count'))->groupBy('queue')->get();

            try { $failed = DB::table('failed_jobs')->count(); } catch (\Throwable) {}

            $recentFailed = collect();
            try {
                $recentFailed = DB::table('failed_jobs')
                    ->latest('failed_at')
                    ->take(5)
                    ->get()
                    ->map(function ($job) {
                        $payload = json_decode($job->payload, true);
                        return [
                            'id'         => $job->id,
                            'job'        => $payload['displayName'] ?? 'Unknown',
                            'failed_at'  => $job->failed_at ?? null,
                            'exception'  => substr($job->exception ?? '', 0, 120),
                        ];
                    });
            } catch (\Throwable) {}

            return response()->json([
                'success'       => true,
                'pending'       => $pending,
                'failed'        => $failed,
                'by_queue'      => $byQueue,
                'recent_failed' => $recentFailed,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────
    // NOTIFICATIONS GLOBALES
    // ────────────────────────────────────────────────────────────

    /**
     * Envoyer une notification à tous les utilisateurs
     */
    public function broadcastNotification(BroadcastNotificationRequest $request)
    {
        $this->guardSuperAdmin();

        $data = $request->validated();

        try {
            $query = User::query();

            match($data['target']) {
                'premium' => $query->whereIn('plan', ['premium', 'enterprise']),
                'free'    => $query->where('plan', 'free'),
                'admins'  => $query->where('is_admin', true),
                default   => null
            };

            $users = $query->get();
            $count = 0;

            foreach ($users as $user) {
                try {
                    DB::table('notifications')->insert([
                        'id'              => \Illuminate\Support\Str::uuid(),
                        'type'            => 'App\Notifications\GlobalNotification',
                        'notifiable_type' => 'App\Models\User',
                        'notifiable_id'   => $user->id,
                        'data'            => json_encode([
                            'title'   => $data['title'],
                            'message' => $data['message'],
                            'type'    => $data['type'],
                        ]),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    $count++;
                } catch (\Throwable) {}
            }

            Log::info("SuperAdmin: notification broadcast [{$data['target']}] — {$count} users notifiés par " . Auth::user()->name);

            return response()->json([
                'success' => true,
                'count'   => $count,
                'message' => "{$count} utilisateur(s) notifié(s).",
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ────────────────────────────────────────────────────────────
    // EXPORTS
    // ────────────────────────────────────────────────────────────

    /**
     * Export CSV de tous les utilisateurs
     */
    public function exportUsers()
    {
        $this->guardSuperAdmin();

        $users = User::orderBy('id')->get([
            'id', 'name', 'email', 'phone', 'plan', 'credits',
            'is_admin', 'is_superadmin', 'is_active',
            'email_verified_at', 'last_login_at', 'last_login_ip', 'created_at'
        ]);

        $csv  = "ID,Nom,Email,Téléphone,Plan,Crédits,Admin,Superadmin,Actif,Email vérifié,Dernière connexion,IP,Créé le\n";

        foreach ($users as $u) {
            $csv .= implode(',', [
                $u->id,
                '"' . str_replace('"', '""', $u->name ?? '') . '"',
                $u->email,
                $u->phone ?? '',
                $u->plan,
                $u->credits,
                $u->is_admin ? 'Oui' : 'Non',
                $u->is_superadmin ? 'Oui' : 'Non',
                $u->is_active ? 'Actif' : 'Suspendu',
                $u->email_verified_at ? 'Oui' : 'Non',
                optional($u->last_login_at)->format('d/m/Y H:i') ?? '',
                $u->last_login_ip ?? '',
                optional($u->created_at)->format('d/m/Y H:i') ?? '',
            ]) . "\n";
        }

        Log::info("SuperAdmin: export users CSV par " . Auth::user()->name);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data360-users-' . now()->format('Ymd-His') . '.csv"',
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // MAINTENANCE MODE
    // ────────────────────────────────────────────────────────────

    /**
     * Activer / désactiver le mode maintenance
     */
    public function toggleMaintenance(ToggleMaintenanceRequest $request)
    {
        $this->guardSuperAdmin();

        $data = $request->validated();

        try {
            if ($data['action'] === 'down') {
                $options = [];
                if (!empty($data['secret']))  $options['--secret']  = $data['secret'];
                if (!empty($data['message'])) $options['--message'] = $data['message'];
                Artisan::call('down', $options);
            } else {
                Artisan::call('up');
            }

            $output = trim(Artisan::output());

            Log::warning("SuperAdmin: mode maintenance [{$data['action']}] par " . Auth::user()->name);

            return response()->json([
                'success' => true,
                'action'  => $data['action'],
                'output'  => $output,
                'message' => $data['action'] === 'down'
                    ? 'Application mise en maintenance.'
                    : 'Application remise en ligne.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Statut du mode maintenance
     */
    public function maintenanceStatus()
    {
        $this->guardSuperAdmin();

        return response()->json([
            'success'      => true,
            'maintenance'  => app()->isDownForMaintenance(),
            'env'          => config('app.env'),
            'app_name'     => config('app.name'),
            'app_url'      => config('app.url'),
            'php_version'  => PHP_VERSION,
            'laravel'      => app()->version(),
        ]);
    }
}
