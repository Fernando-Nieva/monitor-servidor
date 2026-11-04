<?php

namespace App\Http\Controllers;

use App\Events\ServerMetricsUpdated;
use App\Services\ServerMetrics;

class ServerController extends Controller
{
    // Laravel crea el ServerMetrics y lo pasa solo (inyección de dependencias)
    public function index(ServerMetrics $metrics)
    {
        return view('servidor', ['data' => $metrics->snapshot()]);
    }

    public function api(ServerMetrics $metrics)
    {
        $data = $metrics->snapshot();

        try {
            ServerMetricsUpdated::dispatch($data);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json($data);
    }
}
