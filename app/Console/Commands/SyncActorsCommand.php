<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Film;
use App\Models\Actor;
use App\Services\MovieBoxService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class SyncActorsCommand extends Command
{
    protected $signature = 'films:sync-actors {--purge-dummy : Hapus seluruh data dummy aktor sebelum sync API}';
    protected $description = 'Hapus data dummy aktor dan ambil data asli dari MovieBox API';

    public function handle(MovieBoxService $movieBox): int
    {
        $purgeDummy = $this->option('purge-dummy');

        if ($this->option('no-interaction')) {
            $this->info("Mengirim Job SyncActorsJob ke queue...");
            \App\Jobs\SyncActorsJob::dispatch(null, $purgeDummy);
            $this->info("Job sinkronisasi aktor berhasil dijadwalkan.");
            return Command::SUCCESS;
        }

        $this->info("Memulai proses sinkronisasi data Aktor dari MovieBox API secara synchronous...");
        
        $job = new \App\Jobs\SyncActorsJob(null, $purgeDummy);
        $job->handle($movieBox);

        $status = \App\Models\Setting::get('last_actor_api_sync_status', 'Sinkronisasi aktor selesai.');
        $this->info($status);

        return Command::SUCCESS;
    }
}
