<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booty Fitness | Inauguracion pronto</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4eff8;
            --card: #ffffff;
            --text: #1f1b2d;
            --muted: #6e6683;
            --primary: #7c3aed;
            --primary-dark: #6d28d9;
            --border: #e7dcf5;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at top right, #efe6fb, var(--bg));
            font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--text);
            padding: 24px;
        }
        .card {
            width: min(680px, 100%);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(28, 15, 55, 0.08);
            padding: 34px 30px;
            text-align: center;
        }
        .brand {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: .5px;
            margin: 0 0 10px;
        }
        .pill {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 999px;
            background: #ede9fe;
            color: #5b21b6;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 14px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: clamp(26px, 5vw, 42px);
            line-height: 1.1;
        }
        p {
            margin: 0;
            color: var(--muted);
            font-size: 17px;
        }
        .actions {
            margin-top: 26px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 12px;
            border: 1px solid var(--border);
            color: var(--text);
            font-weight: 600;
            background: #fff;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .btn-primary:hover { background: var(--primary-dark); }
    </style>
</head>
<body>
    <main class="card">
        <p class="brand">BOOTY_FITNESS</p>
        <span class="pill">Estamos trabajando</span>
        <h1>Inauguracion pronto</h1>
        <p>Estamos ultimando detalles para darte la mejor experiencia de compra.</p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('login') }}">Acceso administrativo</a>
        </div>
    </main>
</body>
</html>

