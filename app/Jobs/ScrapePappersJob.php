<?php

namespace App\Jobs;

use App\Services\Api\PappersScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ScrapePappersJob
 * ─────────────────────────────────────────────────────────────────────────
 * Job optionnel : scrape un batch de SIRENs en arrière-plan
 * et stocke les résultats dans le cache.
 *
 * Utile quand tu veux pré-charger les données Pappers sans bloquer
 * la réponse HTTP de la recherche principale.
 *
 * Usage :
 *   ScrapePappersJob::dispatch(['411484926', '552032534'], $rechercheId);
 */
class ScrapePappersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout  = 300;  // 5 min max
    public int $tries    = 2;
    public int $backoff  = 60;

    public function __construct(
        private readonly array $sirens,
        private readonly ?int  $rechercheId = null,
    ) {}

    public function handle(PappersScraperService $scraper): void
    {
        if (empty($this->sirens)) {
            return;
        }

        Log::info("ScrapePappersJob: scraping " . count($this->sirens) . " SIREN(s)");

        $results = $scraper->scrapeMultiple($this->sirens);

        // Stocker les résultats liés à une recherche spécifique
        if ($this->rechercheId) {
            $key = "pappers_batch_{$this->rechercheId}";
            Cache::put($key, $results, 86400);
        }

        $found = collect($results)->filter(fn($r) => !empty($r['capital_social']))->count();

        Log::info("ScrapePappersJob: {$found}/" . count($results) . " capital sociaux trouvés");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("ScrapePappersJob failed: " . $e->getMessage(), [
            'sirens' => $this->sirens,
        ]);
    }
}