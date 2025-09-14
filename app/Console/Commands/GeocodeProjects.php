<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class GeocodeProjects extends Command
{
    protected $signature   = 'projects:geocode 
        {--limit=200} 
        {--fresh : Ignore reuse} 
        {--all : Loop through all missing addresses}
        {--max=5 : Max attempts before marking failed}
        {--retry-failed : Include previously failed rows}';

    protected $description = 'Geocode projects with missing coordinates via OpenStreetMap Nominatim';

    public function handle()
    {
        $limit       = (int) $this->option('limit');
        $fresh       = (bool) $this->option('fresh');
        $all         = (bool) $this->option('all');
        $max         = (int) $this->option('max');
        $retryFailed = (bool) $this->option('retry-failed');

        $processed = 0;

        do {
            $q = Project::query()
                ->whereNull('geo_lat')
                ->whereNotNull('address');

            // Only apply these filters if the columns exist
            if (Schema::hasColumn('projects','geocode_failed_at')) {
                $q->whereNull('geocode_failed_at');
            }
            if (Schema::hasColumn('projects','geocode_attempts')) {
                $q->where(function ($qq) use ($max) {
                    $qq->whereNull('geocode_attempts')
                    ->orWhere('geocode_attempts', '<', $max);
                });
            }

            $batch = $q->limit($limit)->get();

            if ($batch->isEmpty()) {
                $this->info($processed ? "Done. Geocoded {$processed} rows." : 'Nothing to geocode.');
                break;
            }

            $this->info("Geocoding {$batch->count()} projects…");

            foreach ($batch as $p) {
                $addr = trim((string) $p->address);
                if ($addr === '') { continue; }

                // Reuse coords for identical address (unless --fresh)
                if (!$fresh) {
                    $reuse = Project::where('address', $addr)
                        ->whereNotNull('geo_lat')->whereNotNull('geo_lng')
                        ->first();

                    if ($reuse) {
                        $p->geo_lat = $reuse->geo_lat;
                        $p->geo_lng = $reuse->geo_lng;
                        $p->geocode_attempts   = 0;
                        $p->geocode_failed_at  = null;
                        $p->geocode_last_error = null;
                        if (Schema::hasColumn('projects','geocode_provider')) $p->geocode_provider = $reuse->geocode_provider ?: 'reuse';
                        if (Schema::hasColumn('projects','geocoded_at'))      $p->geocoded_at = now();
                        $p->save();
                        $processed++;
                        $this->line("↺ Reused for #{$p->id} ({$addr})");
                        continue;
                    }
                }

                // Build query (append Norway if missing)
                $qStr = $addr;
                $low  = mb_strtolower($qStr);
                if (!str_contains($low, 'norge') && !str_contains($low, 'norway')) $qStr .= ', Norway';

                $resp = Http::timeout(20)
                    ->withHeaders([
                        'User-Agent' => 'GrasdalApp/1.0 (post@example.no)', // use real contact
                        'Accept'     => 'application/json',
                    ])->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $qStr, 'format' => 'jsonv2', 'limit' => 1,
                        'countrycodes' => 'no', 'addressdetails' => 0,
                    ]);

                // Transient errors: don’t count as failed attempts
                if ($resp->status() == 429 || $resp->serverError()) {
                    $this->warn("Transient error {$resp->status()} for #{$p->id} {$addr} — will retry later");
                    sleep(2);
                    continue;
                }

                $json = $resp->json();
                $hit  = is_array($json) ? ($json[0] ?? null) : null;

                if (!$resp->ok() || !$hit || !isset($hit['lat'], $hit['lon'])) {
                    // hard failure/no result → increment attempts; mark failed if >= max
                    $p->geocode_attempts   = (int) $p->geocode_attempts + 1;
                    $p->geocode_last_error = $resp->ok() ? 'no_result' : ('http_'.$resp->status());
                    if ($p->geocode_attempts >= $max) {
                        $p->geocode_failed_at = now();
                        $this->warn("✗ Marked FAILED (#{$p->id}) after {$p->geocode_attempts} attempts — {$addr}");
                    }
                    $p->save();
                    sleep(1);
                    continue;
                }

                // success
                $p->geo_lat = (float) $hit['lat'];
                $p->geo_lng = (float) $hit['lon'];
                $p->geocode_attempts   = 0;
                $p->geocode_failed_at  = null;
                $p->geocode_last_error = null;
                if (Schema::hasColumn('projects','geocode_provider')) $p->geocode_provider = 'nominatim';
                if (Schema::hasColumn('projects','geocoded_at'))      $p->geocoded_at = now();
                $p->save();
                $processed++;

                $this->line("✓ #{$p->id} {$addr} -> {$p->geo_lat}, {$p->geo_lng}");
                sleep(1);
            }

        } while ($all);

        return self::SUCCESS;
    }
}
