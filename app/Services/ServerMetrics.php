<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ServerMetrics
{
    public function snapshot(): array
    {
        $win = $this->readWindows();

        return [
            'hostname' => gethostname(),
            'os' => php_uname('s').' '.php_uname('r'),
            'php' => PHP_VERSION,
            'disk' => $this->disk(),
            'memory' => $this->memory($win),
            'cpu_pct' => $win['cpu_pct'] ?? null,
            'uptime_s' => $win['uptime_s'] ?? null,
            'cpu_temp_c' => $this->cpuTemperature(),
            'board_temp_c' => $win['board_temp_c'] ?? null,
            'at' => now()->toIso8601String(),
        ];
    }

    /**
     * Lee la temperatura real del procesador desde el servidor web de LibreHardwareMonitor.
     */
    public function cpuTemperature(): ?float
    {
        try {
            $response = Http::connectTimeout(1)
                ->timeout(2)
                ->acceptJson()
                ->get(config('services.librehardwaremonitor.url'));
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $sensors = $this->cpuTemperatureSensors($response->json() ?? []);

        if ($sensors === []) {
            return null;
        }

        foreach ($sensors as $name => $temperature) {
            if (preg_match('/Package|Tctl/', $name) === 1) {
                return $temperature;
            }
        }

        return max($sensors);
    }

    /**
     * Recorre el árbol de sensores y devuelve las temperaturas absolutas del procesador.
     * Se descartan los sensores "Distance to TjMax", que miden un margen y no una temperatura.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, float>
     */
    private function cpuTemperatureSensors(array $node): array
    {
        $sensors = [];
        $isCpuTemperature = preg_match('#^/\w*cpu/\d+/temperature/#', $node['SensorId'] ?? '') === 1
            && ! str_contains($node['Text'] ?? '', 'Distance');

        if ($isCpuTemperature && preg_match('/\d+(?:[.,]\d+)?/', $node['Value'] ?? '', $matches) === 1) {
            $sensors[$node['Text'] ?? ''] = (float) str_replace(',', '.', $matches[0]);
        }

        foreach ($node['Children'] ?? [] as $child) {
            $sensors += $this->cpuTemperatureSensors($child);
        }

        return $sensors;
    }

    private function disk(): array
    {
        $path = base_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        return [
            'total_gb' => round($total / 1e9, 1),
            'free_gb' => round($free / 1e9, 1),
            'used_pct' => round(100 * ($total - $free) / $total, 1),
        ];
    }

    private function memory(?array $win): ?array
    {
        if (empty($win['mem_total_kb'])) {
            return null;
        }

        $total = $win['mem_total_kb'];
        $free = $win['mem_free_kb'];

        return [
            'total_mb' => intdiv($total, 1024),
            'available_mb' => intdiv($free, 1024),
            'used_pct' => round(100 * ($total - $free) / $total, 1),
        ];
    }

    // Ejecuta un script de PowerShell y devuelve sus datos como array
    private function readWindows(): ?array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $script = <<<'PS'
$os = Get-CimInstance Win32_OperatingSystem

$cpu = (Get-CimInstance Win32_Processor |
    Measure-Object -Property LoadPercentage -Average).Average

$boardTemp = $null

try {
    $zones = @(Get-CimInstance Win32_PerfFormattedData_Counters_ThermalZoneInformation -ErrorAction Stop |
        Where-Object { $_.HighPrecisionTemperature -gt 0 })

    if ($zones.Count -gt 0) {
        $hottest = ($zones | Measure-Object -Property HighPrecisionTemperature -Maximum).Maximum
        $boardTemp = [math]::Round($hottest / 10 - 273.15, 1)
    }
} catch {}

[pscustomobject]@{
    mem_total_kb = [int64]$os.TotalVisibleMemorySize
    mem_free_kb  = [int64]$os.FreePhysicalMemory
    cpu_pct      = $cpu
    uptime_s     = [int64]((Get-Date) - $os.LastBootUpTime).TotalSeconds
    board_temp_c = $boardTemp
} | ConvertTo-Json -Compress
PS;

        $encoded = base64_encode(
            mb_convert_encoding($script, 'UTF-16LE', 'UTF-8')
        );

        $output = shell_exec(
            "powershell -NoProfile -NonInteractive -EncodedCommand $encoded"
        );

        return $output ? json_decode($output, true) : null;
    }
}
