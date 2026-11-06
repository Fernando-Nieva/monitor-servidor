<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#070b14">
    <title>Monitor del servidor · UTN FRRe</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #070b14;
            --panel: rgba(18, 25, 40, .74);
            --panel-soft: rgba(24, 33, 52, .55);
            --border: rgba(148, 163, 184, .14);
            --border-strong: rgba(148, 163, 184, .24);
            --text: #e8eefc;
            --muted: #93a4bf;
            --faint: #64748b;
            --accent: #38bdf8;
            --accent-2: #818cf8;
            --ok: #34d399;
            --warn: #fbbf24;
            --danger: #f87171;
            --radius: 20px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: var(--text);
            background:
                radial-gradient(900px 520px at 12% -10%, rgba(56, 189, 248, .16), transparent 60%),
                radial-gradient(820px 520px at 92% -5%, rgba(129, 140, 248, .15), transparent 55%),
                radial-gradient(760px 520px at 50% 115%, rgba(52, 211, 153, .08), transparent 60%),
                var(--bg);
            -webkit-font-smoothing: antialiased;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(148, 163, 184, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, .045) 1px, transparent 1px);
            background-size: 52px 52px;
            -webkit-mask-image: radial-gradient(circle at 50% 0%, #000 0%, transparent 72%);
            mask-image: radial-gradient(circle at 50% 0%, #000 0%, transparent 72%);
        }

        .shell {
            position: relative;
            z-index: 1;
            max-width: 1320px;
            margin: 0 auto;
            padding: 34px 24px 48px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo {
            width: 52px;
            height: 52px;
            flex: none;
            display: grid;
            place-items: center;
            border-radius: 16px;
            border: 1px solid var(--border-strong);
            color: var(--accent);
            background: linear-gradient(135deg, rgba(56, 189, 248, .22), rgba(129, 140, 248, .18));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .08), 0 10px 30px -18px rgba(56, 189, 248, .7);
        }

        .brand h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 650;
            letter-spacing: -.02em;
        }

        .brand p {
            margin: 3px 0 0;
            font-size: .82rem;
            color: var(--muted);
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 15px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--panel);
            color: var(--muted);
            font-size: .84rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--ok);
            box-shadow: 0 0 0 4px rgba(52, 211, 153, .14);
            animation: pulse 2s ease-in-out infinite;
        }

        .status[data-state="offline"] {
            color: #fca5a5;
            border-color: rgba(248, 113, 113, .3);
        }

        .status[data-state="offline"] .dot {
            background: var(--danger);
            box-shadow: 0 0 0 4px rgba(248, 113, 113, .14);
            animation: none;
        }

        .status[data-state="polling"] .dot,
        .status[data-state="connecting"] .dot {
            background: var(--warn);
            box-shadow: 0 0 0 4px rgba(251, 191, 36, .14);
        }

        .refresh {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 999px;
            border: 1px solid var(--border-strong);
            background: rgba(56, 189, 248, .1);
            color: var(--text);
            font: inherit;
            font-size: .84rem;
            font-weight: 550;
            cursor: pointer;
            transition: background .2s ease, border-color .2s ease, transform .2s ease;
        }

        .refresh:hover {
            background: rgba(56, 189, 248, .18);
            border-color: rgba(56, 189, 248, .4);
        }

        .refresh:active {
            transform: scale(.97);
        }

        .refresh svg {
            transition: transform .6s ease;
        }

        .refresh.is-busy svg {
            animation: spin .8s linear infinite;
        }

        .section-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .section-head h2 {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 600;
            letter-spacing: -.01em;
        }

        .section-head p {
            margin: 4px 0 0;
            font-size: .8rem;
            color: var(--faint);
        }

        .last-reading {
            font-size: .8rem;
            color: var(--faint);
            white-space: nowrap;
        }

        .last-reading strong {
            color: var(--muted);
            font-weight: 550;
            font-variant-numeric: tabular-nums;
        }

        .resources {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 18px;
        }

        .resource {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 22px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: linear-gradient(180deg, var(--panel), var(--panel-soft));
            --c: var(--accent);
        }

        .resource::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: radial-gradient(220px 150px at 100% 0%, color-mix(in srgb, var(--c) 14%, transparent), transparent 72%);
        }

        .resource[data-level="ok"] {
            --c: var(--ok);
        }

        .resource[data-level="warn"] {
            --c: var(--warn);
        }

        .resource[data-level="danger"] {
            --c: var(--danger);
        }

        .resource[data-level="none"] {
            --c: var(--faint);
        }

        .resource-head {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .icon {
            width: 40px;
            height: 40px;
            flex: none;
            display: grid;
            place-items: center;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: rgba(148, 163, 184, .07);
            color: var(--c);
        }

        .resource h2 {
            margin: 0;
            font-size: .98rem;
            font-weight: 600;
        }

        .kicker {
            margin: 2px 0 0;
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .resource-body {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 18px;
        }

        .gauge {
            position: relative;
            width: 118px;
            height: 118px;
            flex: none;
        }

        .gauge svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .gauge .track,
        .gauge .gauge-bar {
            fill: none;
            stroke-width: 10;
        }

        .gauge .track {
            stroke: rgba(148, 163, 184, .12);
        }

        .gauge .gauge-bar {
            stroke: var(--c);
            stroke-linecap: round;
            stroke-dasharray: 326.73;
            stroke-dashoffset: 326.73;
            transition: stroke-dashoffset .9s cubic-bezier(.22, 1, .36, 1), stroke .4s ease;
        }

        .gauge-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
        }

        .gauge-value {
            font-size: 1.9rem;
            font-weight: 650;
            line-height: 1;
            letter-spacing: -.03em;
            color: var(--c);
            font-variant-numeric: tabular-nums;
        }

        .gauge-unit {
            font-size: .76rem;
            line-height: 1;
            color: var(--muted);
        }

        .resource-meta {
            margin: 0;
            font-size: .88rem;
            line-height: 1.55;
            color: var(--muted);
        }

        .resource-note {
            margin: 8px 0 0;
            font-size: .76rem;
            color: var(--faint);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid var(--border);
            font-size: .72rem;
            font-weight: 550;
            color: var(--muted);
            white-space: nowrap;
        }

        .badge::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--c);
        }

        .spark {
            position: relative;
            z-index: 1;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }

        .spark-plot {
            position: relative;
            height: 44px;
            cursor: crosshair;
            touch-action: none;
        }

        .spark-plot svg {
            display: block;
            width: 100%;
            height: 100%;
            overflow: visible;
        }

        .spark-base {
            stroke: var(--border-strong);
            stroke-width: 1;
            vector-effect: non-scaling-stroke;
        }

        .spark-line {
            fill: none;
            stroke: var(--accent);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            vector-effect: non-scaling-stroke;
        }

        .spark-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            margin: -4px 0 0 -4px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 2px #121928;
            pointer-events: none;
        }

        .spark-readout {
            margin: 8px 0 0;
            min-height: 1.2em;
            font-size: .74rem;
            color: var(--faint);
            font-variant-numeric: tabular-nums;
        }

        .details {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 18px;
        }

        .panel {
            padding: 22px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: linear-gradient(180deg, var(--panel), var(--panel-soft));
        }

        .panel h3 {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 6px;
            font-size: .98rem;
            font-weight: 600;
        }

        .panel h3::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, .12);
        }

        .panel-sub {
            margin: 0 0 18px;
            font-size: .78rem;
            color: var(--faint);
        }

        .data-list {
            display: grid;
            gap: 1px;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            background: var(--border);
            margin: 0;
        }

        .data-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 13px 16px;
            background: rgba(9, 13, 24, .62);
        }

        .data-key {
            font-size: .79rem;
            color: var(--faint);
            white-space: nowrap;
        }

        .data-val {
            font-size: .9rem;
            font-weight: 550;
            text-align: right;
            word-break: break-word;
            font-variant-numeric: tabular-nums;
        }

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--border);
            font-size: .78rem;
            color: var(--faint);
        }

        .footer strong {
            color: var(--muted);
            font-weight: 550;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .45;
            }
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (min-width: 1240px) {
            .resources {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .resource-body {
                gap: 16px;
            }

            .gauge {
                width: 104px;
                height: 104px;
            }

            .gauge-value {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 860px) {
            .details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .resources {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 540px) {
            .shell {
                padding: 24px 16px 38px;
            }

            .brand h1 {
                font-size: 1.28rem;
            }

            .resource-body {
                gap: 16px;
            }

            .gauge {
                width: 104px;
                height: 104px;
            }

            .gauge-value {
                font-size: 1.6rem;
            }

            .data-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }

            .data-val {
                text-align: left;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
            }
        }
    </style>
</head>

<body>
    <main class="shell">
        <header class="topbar">
            <div class="brand">
                <div class="logo" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="7" rx="2" />
                        <rect x="3" y="14" width="18" height="7" rx="2" />
                        <path d="M7 6.5h.01M7 17.5h.01" />
                    </svg>
                </div>
                <div>
                    <h1>Monitor del Servidor</h1>
                    <p>UTN FRRe · Programación IV · Cohorte 2026</p>
                </div>
            </div>

            <div class="top-actions">
                <div class="status" id="status" data-state="connecting" role="status" aria-live="polite">
                    <span class="dot" aria-hidden="true"></span>
                    <span id="statusText">Conectando…</span>
                </div>
                <button class="refresh" id="refreshBtn" type="button">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-2.64-6.36" />
                        <path d="M21 3v6h-6" />
                    </svg>
                    Actualizar
                </button>
            </div>
        </header>

        <div class="section-head">
            <div>
                <h2>Recursos en tiempo real</h2>
                <p>Vía WebSockets (Laravel Reverb), con sondeo automático si dejan de llegar datos.</p>
            </div>
            <div class="last-reading">Última lectura <strong id="lastUpdate">{{ $data['at'] ?? '—' }}</strong></div>
        </div>

        <section class="resources" aria-label="Métricas de recursos">
            <article class="resource" data-gauge="cpu" data-level="none">
                <div class="resource-head">
                    <span class="icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="7" y="7" width="10" height="10" rx="2" />
                            <path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3" />
                        </svg>
                    </span>
                    <div>
                        <h2>CPU</h2>
                        <p class="kicker">Procesador</p>
                    </div>
                </div>
                <div class="resource-body">
                    <div class="gauge">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle class="track" cx="60" cy="60" r="52" />
                            <circle class="gauge-bar" cx="60" cy="60" r="52" />
                        </svg>
                        <div class="gauge-center">
                            <span class="gauge-value" id="cpu">{{ $data['cpu_pct'] ?? '—' }}</span>
                            <span class="gauge-unit">%</span>
                        </div>
                    </div>
                    <div>
                        <p class="resource-meta" id="cpuMeta">Carga actual del procesador.</p>
                        <p class="resource-note">Uso instantáneo</p>
                    </div>
                </div>
            </article>

            <article class="resource" data-gauge="memory" data-level="none">
                <div class="resource-head">
                    <span class="icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="10" rx="2" />
                            <path d="M6 17v3M10 17v3M14 17v3M18 17v3M7 11h.01M11 11h.01M15 11h.01" />
                        </svg>
                    </span>
                    <div>
                        <h2>Memoria</h2>
                        <p class="kicker">RAM</p>
                    </div>
                </div>
                <div class="resource-body">
                    <div class="gauge">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle class="track" cx="60" cy="60" r="52" />
                            <circle class="gauge-bar" cx="60" cy="60" r="52" />
                        </svg>
                        <div class="gauge-center">
                            <span class="gauge-value" id="memory">{{ $data['memory']['used_pct'] ?? '—' }}</span>
                            <span class="gauge-unit">%</span>
                        </div>
                    </div>
                    <div>
                        <p class="resource-meta" id="memoryMeta">
                            @if ($data['memory'])
                                {{ $data['memory']['available_mb'] }} MB libres de {{ $data['memory']['total_mb'] }} MB
                            @else
                                No disponible
                            @endif
                        </p>
                        <p class="resource-note">Memoria física</p>
                    </div>
                </div>
            </article>

            <article class="resource" data-gauge="disk" data-level="none">
                <div class="resource-head">
                    <span class="icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M3 13h18M8 17h.01M12 17h.01" />
                        </svg>
                    </span>
                    <div>
                        <h2>Disco</h2>
                        <p class="kicker">Almacenamiento</p>
                    </div>
                </div>
                <div class="resource-body">
                    <div class="gauge">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle class="track" cx="60" cy="60" r="52" />
                            <circle class="gauge-bar" cx="60" cy="60" r="52" />
                        </svg>
                        <div class="gauge-center">
                            <span class="gauge-value" id="disk">{{ $data['disk']['used_pct'] ?? '—' }}</span>
                            <span class="gauge-unit">%</span>
                        </div>
                    </div>
                    <div>
                        <p class="resource-meta" id="diskMeta">
                            {{ $data['disk']['free_gb'] ?? '—' }} GB libres de {{ $data['disk']['total_gb'] ?? '—' }} GB
                        </p>
                        <p class="resource-note">Unidad del proyecto</p>
                    </div>
                </div>
            </article>

            <article class="resource" data-gauge="temperature" data-level="none">
                <div class="resource-head">
                    <span class="icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 14.76V5a2 2 0 0 0-4 0v9.76a4 4 0 1 0 4 0Z" />
                        </svg>
                    </span>
                    <div>
                        <h2>Temperatura</h2>
                        <p class="kicker" id="temperatureKicker">Sin sensor</p>
                    </div>
                </div>
                <div class="resource-body">
                    <div class="gauge">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle class="track" cx="60" cy="60" r="52" />
                            <circle class="gauge-bar" cx="60" cy="60" r="52" />
                        </svg>
                        <div class="gauge-center">
                            <span class="gauge-value">—</span>
                            <span class="gauge-unit">°C</span>
                        </div>
                    </div>
                    <div>
                        <p class="resource-meta">No disponible</p>
                        <p class="resource-note" id="temperatureNote"></p>
                    </div>
                </div>
            </article>
        </section>

        <section class="details" aria-label="Detalles del sistema">
            <article class="panel">
                <h3>Información del sistema</h3>
                <p class="panel-sub">Identificación y estado del host.</p>
                <div class="data-list">
                    <div class="data-row">
                        <span class="data-key">Nombre del equipo</span>
                        <span class="data-val" id="infoHost">{{ $data['hostname'] }}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Sistema operativo</span>
                        <span class="data-val" id="infoOs">{{ $data['os'] }}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Versión de PHP</span>
                        <span class="data-val" id="infoPhp">{{ $data['php'] }}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Encendido hace</span>
                        <span class="data-val" id="infoUptime">{{ $data['uptime_s'] ?? 'No disponible' }}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Temperatura CPU</span>
                        <span class="data-val" id="infoTemp">{{ isset($data['cpu_temp_c']) ? $data['cpu_temp_c'].' °C' : 'Requiere LibreHardwareMonitor' }}</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Zona térmica ACPI (placa)</span>
                        <span class="data-val" id="infoBoardTemp">{{ isset($data['board_temp_c']) ? $data['board_temp_c'].' °C' : 'No disponible' }}</span>
                    </div>
                </div>
            </article>

            <article class="panel">
                <h3>Capacidad de recursos</h3>
                <p class="panel-sub">Valores absolutos del último muestreo.</p>
                <div class="data-list">
                    <div class="data-row">
                        <span class="data-key">Memoria total</span>
                        <span class="data-val" id="infoMemTotal">{{ $data['memory']['total_mb'] ?? '—' }} MB</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Memoria disponible</span>
                        <span class="data-val" id="infoMemFree">{{ $data['memory']['available_mb'] ?? '—' }} MB</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Disco total</span>
                        <span class="data-val" id="infoDiskTotal">{{ $data['disk']['total_gb'] ?? '—' }} GB</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Disco libre</span>
                        <span class="data-val" id="infoDiskFree">{{ $data['disk']['free_gb'] ?? '—' }} GB</span>
                    </div>
                    <div class="data-row">
                        <span class="data-key">Fuente de datos</span>
                        <span class="data-val">Laravel {{ app()->version() }}</span>
                    </div>
                </div>
            </article>
        </section>

        <footer class="footer">
            <span>Monitor de servidor · tiempo real con <strong>WebSockets (Laravel Reverb)</strong></span>
            <span>Canal: <strong>server-metrics</strong></span>
        </footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <script>
        const GAUGE_LENGTH = 2 * Math.PI * 52;
        const HISTORY_SIZE = 60;
        const STALE_AFTER_MS = 10000;
        const STATUS_TEXT = {
            live: 'En vivo',
            polling: 'Sondeo cada 5 s',
            connecting: 'Conectando…',
            offline: 'Sin conexión',
        };
        const LEVEL_TEXT = {
            ok: 'Normal',
            warn: 'Elevado',
            danger: 'Crítico',
            none: 'Sin datos',
        };
        const GAUGE_UNITS = { cpu: '%', memory: '%', disk: '%', temperature: ' °C' };
        const initial = @json($data);
        const history = {};
        let isFetching = false;
        let lastDataAt = Date.now();
        let lastSocketAt = 0;
        let lastSampleAt = null;

        const byId = (id) => document.getElementById(id);

        function formatNumber(value, digits = 1) {
            if (value === null || value === undefined || Number.isNaN(Number(value))) {
                return null;
            }

            return Number(value).toFixed(digits).replace(/\.0$/, '');
        }

        function formatMegabytes(value) {
            if (value === null || value === undefined) {
                return '—';
            }

            return Number(value) >= 1024
                ? `${formatNumber(Number(value) / 1024)} GB`
                : `${value} MB`;
        }

        function formatUptime(seconds) {
            if (seconds === null || seconds === undefined) {
                return 'No disponible';
            }

            const total = Math.max(0, Math.floor(Number(seconds)));
            const days = Math.floor(total / 86400);
            const hours = Math.floor((total % 86400) / 3600);
            const minutes = Math.floor((total % 3600) / 60);

            if (days > 0) return `${days} d ${hours} h ${minutes} min`;
            if (hours > 0) return `${hours} h ${minutes} min`;

            return `${minutes} min`;
        }

        function setText(id, value) {
            const element = byId(id);

            if (element) {
                element.textContent = value;
            }
        }

        function formatTime(date) {
            return date.toLocaleTimeString('es-AR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        }

        function buildSparklines() {
            document.querySelectorAll('.resource[data-gauge]').forEach((card) => {
                const name = card.dataset.gauge;

                history[name] = [];
                card.querySelector('.resource-head').insertAdjacentHTML('beforeend', '<span class="badge"></span>');
                card.insertAdjacentHTML('beforeend', `
                    <div class="spark">
                        <div class="spark-plot">
                            <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                <line class="spark-base" x1="0" y1="100" x2="100" y2="100" />
                                <polyline class="spark-line" />
                            </svg>
                            <span class="spark-dot" hidden></span>
                        </div>
                        <p class="spark-readout"></p>
                    </div>
                `);

                const plot = card.querySelector('.spark-plot');

                plot.addEventListener('pointermove', (event) => {
                    const points = history[name];

                    if (points.length === 0) {
                        return;
                    }

                    const bounds = plot.getBoundingClientRect();
                    const ratio = Math.min(1, Math.max(0, (event.clientX - bounds.left) / bounds.width));

                    showSparkPoint(card, name, Math.round(ratio * (points.length - 1)));
                });

                plot.addEventListener('pointerleave', () => renderSparkline(card, name));
            });
        }

        function sparkPosition(index, count, value) {
            return {
                x: count > 1 ? (index / (count - 1)) * 100 : 100,
                y: 100 - Math.min(100, Math.max(0, value)),
            };
        }

        function placeSparkDot(card, position) {
            const dot = card.querySelector('.spark-dot');

            dot.hidden = false;
            dot.style.left = `${position.x}%`;
            dot.style.top = `${position.y}%`;
        }

        function showSparkPoint(card, name, index) {
            const points = history[name];
            const point = points[index];

            placeSparkDot(card, sparkPosition(index, points.length, point.value));
            card.querySelector('.spark-readout').textContent =
                `${formatNumber(point.value)}${GAUGE_UNITS[name]} · ${formatTime(point.at)}`;
        }

        function renderSparkline(card, name) {
            const points = history[name];
            const readout = card.querySelector('.spark-readout');
            const positions = points.map((point, index) => sparkPosition(index, points.length, point.value));

            card.querySelector('.spark-line').setAttribute(
                'points',
                positions.map((position) => `${position.x},${position.y}`).join(' ')
            );

            if (points.length === 0) {
                card.querySelector('.spark-dot').hidden = true;
                readout.textContent = 'Sin historial';
                return;
            }

            placeSparkDot(card, positions[positions.length - 1]);

            const values = points.map((point) => point.value);

            readout.textContent = points.length === 1
                ? 'Registrando historial…'
                : `Mín ${formatNumber(Math.min(...values))}${GAUGE_UNITS[name]} · Máx ${formatNumber(Math.max(...values))}${GAUGE_UNITS[name]} · ${points.length} lecturas`;
        }

        function recordSample(name, value, at) {
            const card = document.querySelector(`.resource[data-gauge="${name}"]`);

            if (!card) {
                return;
            }

            if (value !== null && value !== undefined && !Number.isNaN(Number(value))) {
                history[name].push({ value: Number(value), at });
                history[name].splice(0, Math.max(0, history[name].length - HISTORY_SIZE));
            }

            renderSparkline(card, name);
        }

        function temperatureView(data) {
            if (data.cpu_temp_c !== null && data.cpu_temp_c !== undefined) {
                return {
                    value: data.cpu_temp_c,
                    kicker: 'Sensor CPU',
                    meta: 'Lectura real del procesador.',
                    note: 'Vía LibreHardwareMonitor',
                    options: { warnAt: 70, dangerAt: 85 },
                };
            }

            if (data.board_temp_c !== null && data.board_temp_c !== undefined) {
                return {
                    value: data.board_temp_c,
                    kicker: 'Zona ACPI',
                    meta: 'Dato de la placa, no del procesador.',
                    note: 'Sensor real: requiere LibreHardwareMonitor.',
                    options: { neutralLabel: 'Referencial' },
                };
            }

            return {
                value: null,
                kicker: 'Sin sensor',
                meta: 'No disponible',
                note: 'Sensor real: requiere LibreHardwareMonitor.',
                options: {},
            };
        }

        function setGauge(name, value, meta, { warnAt = 60, dangerAt = 85, neutralLabel = null } = {}) {
            const card = document.querySelector(`.resource[data-gauge="${name}"]`);

            if (!card) {
                return;
            }

            const bar = card.querySelector('.gauge-bar');
            const valueElement = card.querySelector('.gauge-value');
            const unitElement = card.querySelector('.gauge-unit');
            const metaElement = card.querySelector('.resource-meta');

            const hasValue = value !== null && value !== undefined && !Number.isNaN(Number(value));
            const percentage = hasValue ? Math.min(100, Math.max(0, Number(value))) : 0;
            const level = !hasValue || neutralLabel
                ? 'none'
                : percentage >= dangerAt
                    ? 'danger'
                    : percentage >= warnAt
                        ? 'warn'
                        : 'ok';

            card.dataset.level = level;
            card.querySelector('.badge').textContent = neutralLabel ?? LEVEL_TEXT[level];
            bar.style.strokeDashoffset = String(GAUGE_LENGTH * (1 - percentage / 100));
            valueElement.textContent = hasValue ? formatNumber(percentage) : '—';
            unitElement.style.visibility = hasValue ? 'visible' : 'hidden';

            if (meta !== undefined && metaElement) {
                metaElement.textContent = meta;
            }
        }

        function applyData(data) {
            const cpu = data.cpu_pct;
            const memory = data.memory;
            const disk = data.disk;

            setGauge(
                'cpu',
                cpu,
                cpu === null || cpu === undefined ? 'No disponible' : 'Carga actual del procesador.'
            );

            setGauge(
                'memory',
                memory?.used_pct ?? null,
                memory
                    ? `${formatMegabytes(memory.available_mb)} libres de ${formatMegabytes(memory.total_mb)}`
                    : 'No disponible'
            );

            setGauge(
                'disk',
                disk?.used_pct ?? null,
                disk
                    ? `${formatNumber(disk.free_gb)} GB libres de ${formatNumber(disk.total_gb)} GB`
                    : 'No disponible'
            );

            setText('infoHost', data.hostname ?? '—');
            setText('infoOs', data.os ?? '—');
            setText('infoPhp', data.php ?? '—');
            setText('infoUptime', formatUptime(data.uptime_s));
            setText('infoTemp', data.cpu_temp_c === null || data.cpu_temp_c === undefined
                ? 'Requiere LibreHardwareMonitor'
                : `${formatNumber(data.cpu_temp_c)} °C`);
            setText('infoBoardTemp', data.board_temp_c === null || data.board_temp_c === undefined
                ? 'No disponible'
                : `${formatNumber(data.board_temp_c)} °C`);

            const temperature = temperatureView(data);

            setGauge('temperature', temperature.value, temperature.meta, temperature.options);
            setText('temperatureKicker', temperature.kicker);
            setText('temperatureNote', temperature.note);

            setText('infoMemTotal', memory ? formatMegabytes(memory.total_mb) : '—');
            setText('infoMemFree', memory ? formatMegabytes(memory.available_mb) : '—');
            setText('infoDiskTotal', disk ? `${formatNumber(disk.total_gb)} GB` : '—');
            setText('infoDiskFree', disk ? `${formatNumber(disk.free_gb)} GB` : '—');

            if (data.at) {
                const date = new Date(data.at);

                setText('lastUpdate', formatTime(date));
                byId('lastUpdate')?.setAttribute('title', date.toLocaleString('es-AR'));

                // Una misma lectura puede llegar por HTTP y por WebSocket: se registra una sola vez.
                if (data.at !== lastSampleAt) {
                    lastSampleAt = data.at;
                    recordSample('cpu', cpu, date);
                    recordSample('memory', memory?.used_pct, date);
                    recordSample('disk', disk?.used_pct, date);
                    recordSample('temperature', temperature.value, date);
                }
            }
        }

        function setStatus(state) {
            const status = byId('status');

            if (!status) {
                return;
            }

            status.dataset.state = state;
            setText('statusText', STATUS_TEXT[state]);
        }

        async function actualizar() {
            if (isFetching) {
                return;
            }

            isFetching = true;
            const button = byId('refreshBtn');
            button?.classList.add('is-busy');

            try {
                const response = await fetch('/api/metrics', {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                applyData(await response.json());
                lastDataAt = Date.now();
                setStatus(Date.now() - lastSocketAt < STALE_AFTER_MS ? 'live' : 'polling');
            } catch (error) {
                setStatus('offline');
                console.error('No se pudieron obtener las métricas:', error);
            } finally {
                isFetching = false;
                button?.classList.remove('is-busy');
            }
        }

        buildSparklines();
        applyData(initial);
        byId('refreshBtn')?.addEventListener('click', actualizar);

        // Si dejan de llegar datos (Reverb o monitor:stream detenidos), se consulta por HTTP.
        setInterval(() => {
            if (Date.now() - lastDataAt >= STALE_AFTER_MS) {
                actualizar();
            }
        }, 5000);

        // Conexión WebSockets con Laravel Echo y Laravel Reverb
        (function initWebSockets() {
            try {
                if (typeof Pusher === 'undefined' || typeof Echo === 'undefined') {
                    console.warn('Pusher o Laravel Echo no se cargaron. Activando sondeo.');
                    return;
                }

                window.Pusher = Pusher;

                // Soporte para formato IIFE (Echo o Echo.default)
                const EchoConstructor = typeof Echo === 'function'
                    ? Echo
                    : (Echo.default || window.Echo?.default || window.Echo);

                if (typeof EchoConstructor !== 'function') {
                    console.warn('Constructor de Echo no disponible. Activando sondeo.');
                    return;
                }

                const host = window.location.hostname || '{{ config('broadcasting.connections.reverb.options.host') }}' || 'localhost';

                const echo = new EchoConstructor({
                    broadcaster: 'reverb',
                    key: '{{ config('broadcasting.connections.reverb.key') }}',
                    wsHost: host,
                    wsPort: {{ config('broadcasting.connections.reverb.options.port') ?: 8080 }},
                    wssPort: {{ config('broadcasting.connections.reverb.options.port') ?: 8080 }},
                    forceTLS: {{ config('broadcasting.connections.reverb.options.scheme') === 'https' ? 'true' : 'false' }},
                    enabledTransports: ['ws', 'wss'],
                });

                window.Echo = echo;

                const handleMetrics = (payload) => {
                    const data = payload?.data || payload;
                    if (data) {
                        lastDataAt = lastSocketAt = Date.now();
                        applyData(data);
                        setStatus('live');
                    }
                };

                // Escuchar el canal público del monitor
                echo.channel('server-metrics')
                    .listen('.ServerMetricsUpdated', handleMetrics)
                    .listen('ServerMetricsUpdated', handleMetrics);

                // Monitorear el estado de la conexión en vivo
                const conn = echo.connector?.pusher?.connection;
                if (conn) {
                    const handleSocketLoss = () => {
                        lastSocketAt = 0;
                        actualizar();
                    };

                    conn.bind('disconnected', handleSocketLoss);
                    conn.bind('unavailable', handleSocketLoss);
                    conn.bind('failed', handleSocketLoss);
                }
            } catch (error) {
                console.error('Error al iniciar WebSockets (Echo):', error);
            }
        })();
    </script>
</body>

</html>
