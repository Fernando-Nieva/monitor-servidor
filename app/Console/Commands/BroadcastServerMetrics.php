<?php

namespace App\Console\Commands;

use App\Events\ServerMetricsUpdated;
use App\Services\ServerMetrics;
use Illuminate\Console\Command;

class BroadcastServerMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:stream
                            {--interval=5 : Intervalo en segundos entre cada lectura}
                            {--once : Realizar una única lectura y salir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transmite las lecturas del servidor en tiempo real por WebSockets vía Reverb';

    /**
     * Execute the console command.
     */
    public function handle(ServerMetrics $metrics): int
    {
        $interval = max(1, (int) $this->option('interval'));
        $once = (bool) $this->option('once');

        $this->info('Iniciando transmisión en tiempo real de métricas del servidor...');
        $this->info("Canal: server-metrics | Evento: ServerMetricsUpdated | Intervalo: {$interval}s");
        $this->comment("Presiona CTRL+C para detener el proceso.\n");

        do {
            $snapshot = $metrics->snapshot();

            try {
                ServerMetricsUpdated::dispatch($snapshot);

                $hora = now()->format('H:i:s');
                $cpu = $snapshot['cpu_pct'] !== null ? "{$snapshot['cpu_pct']}%" : 'N/D';
                $mem = isset($snapshot['memory']['used_pct']) ? "{$snapshot['memory']['used_pct']}%" : 'N/D';
                $disk = isset($snapshot['disk']['used_pct']) ? "{$snapshot['disk']['used_pct']}%" : 'N/D';

                $this->line("[$hora] Métrica emitida -> CPU: $cpu | RAM: $mem | Disco: $disk");
            } catch (\Throwable $e) {
                $this->warn('['.now()->format('H:i:s')."] Advertencia: No se pudo conectar a Reverb en el puerto 8080. Asegúrate de ejecutar 'php artisan reverb:start'.");
            }

            if ($once) {
                break;
            }

            sleep($interval);
        } while (true);

        return Command::SUCCESS;
    }
}
