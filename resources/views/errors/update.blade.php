<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="45">
    <title>Aplicación en actualización</title>
    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --ink: #111827;
            --muted: #64748b;
            --line: #e5e7eb;
            --brand: #f59e0b;
            --brand-dark: #d97706;
            --ok: #10b981;
            --shadow: 0 24px 70px rgba(15, 23, 42, 0.14);
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
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 20% 20%, rgba(245, 158, 11, 0.16), transparent 28rem),
                radial-gradient(circle at 82% 14%, rgba(16, 185, 129, 0.12), transparent 24rem),
                linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
        }

        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
        }

        .shell {
            width: min(100%, 58rem);
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(18rem, 0.9fr);
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 1.5rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .content {
            padding: clamp(2rem, 5vw, 4rem);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.7rem;
            border: 1px solid rgba(245, 158, 11, 0.28);
            border-radius: 999px;
            color: #92400e;
            background: #fffbeb;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: var(--brand);
            animation: pulse 1.25s ease-in-out infinite;
        }

        h1 {
            margin: 1.5rem 0 0;
            max-width: 12ch;
            font-size: clamp(2.35rem, 6vw, 4.25rem);
            line-height: 0.96;
            letter-spacing: 0;
        }

        p {
            margin: 1.25rem 0 0;
            max-width: 34rem;
            color: var(--muted);
            font-size: clamp(1rem, 2vw, 1.125rem);
            line-height: 1.65;
        }

        .status {
            margin-top: 2rem;
            display: grid;
            gap: 0.8rem;
        }

        .status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            color: #334155;
            font-size: 0.92rem;
            font-weight: 650;
        }

        .track {
            height: 0.6rem;
            overflow: hidden;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .bar {
            width: 38%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--brand), var(--ok));
            animation: loading 2.2s ease-in-out infinite;
        }

        .visual {
            position: relative;
            display: grid;
            place-items: center;
            min-height: 28rem;
            border-left: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(248, 250, 252, 0.86), rgba(241, 245, 249, 0.94));
        }

        .ring {
            position: relative;
            width: min(72%, 17rem);
            aspect-ratio: 1;
            border-radius: 999px;
            background: conic-gradient(from 0deg, var(--brand), var(--ok), #38bdf8, var(--brand));
            animation: spin 4.5s linear infinite;
        }

        .ring::before {
            content: "";
            position: absolute;
            inset: 0.7rem;
            border-radius: inherit;
            background: #f8fafc;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.22);
        }

        .mascot-wrap {
            position: absolute;
            display: grid;
            place-items: center;
            width: min(54%, 12rem);
            aspect-ratio: 1;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.74);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.14);
            animation: breathe 1.8s ease-in-out infinite;
        }

        .mascot {
            width: 88%;
            height: 88%;
            object-fit: contain;
            filter: drop-shadow(0 12px 16px rgba(15, 23, 42, 0.18));
        }

        .mini-card {
            position: absolute;
            right: clamp(1.25rem, 4vw, 3rem);
            bottom: clamp(1.25rem, 4vw, 3rem);
            width: min(72%, 15rem);
            padding: 1rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.10);
        }

        .mini-card strong {
            display: block;
            font-size: 0.9rem;
        }

        .mini-card span {
            display: block;
            margin-top: 0.25rem;
            color: var(--muted);
            font-size: 0.82rem;
            line-height: 1.45;
        }

        .actions {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.75rem;
            padding: 0.7rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            color: #1f2937;
            background: #fff;
            font-weight: 700;
            text-decoration: none;
        }

        .button-primary {
            border-color: var(--brand-dark);
            color: #fff;
            background: var(--brand-dark);
        }

        @media (max-width: 760px) {
            .page {
                padding: 1rem;
            }

            .shell {
                grid-template-columns: 1fr;
                border-radius: 1.1rem;
            }

            .visual {
                min-height: 18rem;
                border-left: 0;
                border-top: 1px solid var(--line);
            }

            .content {
                padding: 2rem 1.35rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.001ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
            }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes loading {
            0% { transform: translateX(-120%); width: 35%; }
            50% { width: 62%; }
            100% { transform: translateX(285%); width: 35%; }
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.45; transform: scale(0.85); }
            50% { opacity: 1; transform: scale(1.1); }
        }

        @keyframes breathe {
            0%, 100% { transform: scale(0.96); }
            50% { transform: scale(1.03); }
        }
    </style>
</head>
<body>
    <main class="page" role="main">
        <section class="shell" aria-labelledby="page-title">
            <div class="content">
                <span class="badge"><span class="dot"></span> Actualización en curso</span>
                <h1 id="page-title">Volvemos en unos minutos</h1>
                <p>
                    Estamos aplicando mejoras en la aplicación. Por favor espera un par de minutos
                    y vuelve a intentar; esta pantalla se actualizará automáticamente.
                </p>

                <div class="status" aria-label="Estado de actualización">
                    <div class="status-row">
                        <span>Preparando el sistema</span>
                        <span>En proceso</span>
                    </div>
                    <div class="track" aria-hidden="true"><div class="bar"></div></div>
                </div>

                <div class="actions">
                    <a class="button button-primary" href="/admin">Intentar nuevamente</a>
                    <a class="button" href="javascript:window.location.reload()">Actualizar página</a>
                </div>
            </div>

            <div class="visual" aria-hidden="true">
                <div class="ring"></div>
                <div class="mascot-wrap">
                    <img class="mascot" src="/images/update-mascot.png" alt="">
                </div>
                <div class="mini-card">
                    <strong>Gracias por tu paciencia</strong>
                    <span>El equipo está dejando todo listo para continuar trabajando con normalidad.</span>
                </div>
            </div>
        </section>
    </main>
</body>
</html>