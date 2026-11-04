<?php

namespace Tests\Feature;

use App\Events\ServerMetricsUpdated;
use App\Services\ServerMetrics;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerMetricsTemperatureTest extends TestCase
{
    public function test_metrics_endpoint_keeps_cpu_and_board_temperatures_separate(): void
    {
        Event::fake([ServerMetricsUpdated::class]);
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->fakeSnapshot(cpuTemperature: null, boardTemperature: 29.9);

        $response = $this->getJson('/api/metrics');

        $response->assertOk()
            ->assertJsonPath('cpu_temp_c', null)
            ->assertJsonPath('board_temp_c', 29.9);
    }

    public function test_board_temperature_is_not_presented_as_cpu_temperature(): void
    {
        $html = $this->renderMonitor(cpuTemperature: null, boardTemperature: 29.9);

        $this->assertStringContainsString('id="infoTemp">Requiere LibreHardwareMonitor<', $html);
        $this->assertStringContainsString('id="infoBoardTemp">29.9 °C<', $html);
    }

    public function test_cpu_sensor_reading_is_shown_when_available(): void
    {
        $html = $this->renderMonitor(cpuTemperature: 54.5, boardTemperature: 29.9);

        $this->assertStringContainsString('id="infoTemp">54.5 °C<', $html);
    }

    public function test_board_temperature_row_reports_when_no_reading_exists(): void
    {
        $html = $this->renderMonitor(cpuTemperature: null, boardTemperature: null);

        $this->assertStringContainsString('id="infoBoardTemp">No disponible<', $html);
    }

    public function test_cpu_temperature_prefers_the_package_sensor_and_reads_comma_decimals(): void
    {
        Http::fake(['*' => Http::response($this->sensorTree([
            $this->sensor('Core Max', '/intelcpu/0/temperature/0', '74,0 °C'),
            $this->sensor('CPU Package', '/intelcpu/0/temperature/6', '73,5 °C'),
        ]))]);

        $this->assertSame(73.5, app(ServerMetrics::class)->cpuTemperature());
    }

    public function test_cpu_temperature_uses_the_hottest_sensor_without_a_package_reading(): void
    {
        Http::fake(['*' => Http::response($this->sensorTree([
            $this->sensor('CPU Core #1', '/amdcpu/0/temperature/1', '61.5 °C'),
            $this->sensor('CPU Core #2', '/amdcpu/0/temperature/2', '64.0 °C'),
        ]))]);

        $this->assertSame(64.0, app(ServerMetrics::class)->cpuTemperature());
    }

    public function test_cpu_temperature_ignores_tjmax_distance_and_other_hardware(): void
    {
        Http::fake(['*' => Http::response($this->sensorTree([
            $this->sensor('CPU Core #1 Distance to TjMax', '/intelcpu/0/temperature/7', '90,0 °C'),
            $this->sensor('GPU Core', '/gpu-nvidia/0/temperature/0', '80,0 °C'),
        ]))]);

        $this->assertNull(app(ServerMetrics::class)->cpuTemperature());
    }

    public function test_cpu_temperature_is_null_when_the_monitor_is_closed(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->assertNull(app(ServerMetrics::class)->cpuTemperature());
    }

    public function test_cpu_temperature_is_null_when_the_monitor_returns_an_error(): void
    {
        Http::fake(['*' => Http::response('Error', 500)]);

        $this->assertNull(app(ServerMetrics::class)->cpuTemperature());
    }

    /**
     * @param  array<int, array<string, string>>  $sensors
     * @return array<string, mixed>
     */
    private function sensorTree(array $sensors): array
    {
        return ['Text' => 'Sensor', 'Children' => [['Text' => 'DESKTOP', 'Children' => $sensors]]];
    }

    /**
     * @return array<string, string>
     */
    private function sensor(string $name, string $sensorId, string $value): array
    {
        return ['Text' => $name, 'SensorId' => $sensorId, 'Type' => 'Temperature', 'Value' => $value];
    }

    private function fakeSnapshot(?float $cpuTemperature, ?float $boardTemperature): void
    {
        $snapshot = $this->snapshot($cpuTemperature, $boardTemperature);

        $this->mock(ServerMetrics::class)
            ->shouldReceive('snapshot')
            ->andReturn($snapshot);
    }

    private function renderMonitor(?float $cpuTemperature, ?float $boardTemperature): string
    {
        return view('servidor', ['data' => $this->snapshot($cpuTemperature, $boardTemperature)])->render();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(?float $cpuTemperature, ?float $boardTemperature): array
    {
        return [
            'hostname' => 'servidor-de-prueba',
            'os' => 'Windows NT 10.0',
            'php' => '8.5.7',
            'disk' => ['total_gb' => 500.0, 'free_gb' => 250.0, 'used_pct' => 50.0],
            'memory' => ['total_mb' => 16000, 'available_mb' => 8000, 'used_pct' => 50.0],
            'cpu_pct' => 25,
            'uptime_s' => 3600,
            'cpu_temp_c' => $cpuTemperature,
            'board_temp_c' => $boardTemperature,
            'at' => '2026-11-04T03:00:00+00:00',
        ];
    }
}
