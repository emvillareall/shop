<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Landing Fitness Mujeres</title>

  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- ✅ Leaflet CSS (MAPA REAL) -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <style>
    :root {
      --lila: #c9add6;
      --lavanda: #edc7ff;
      --blanco: #ffffff;
      --oscuro: #1f1f1f;
      --base: #f5e3fa;
      --header: #f5e3fa;

      /* Fuentes */
      --font-primary: 'Bebas Neue', sans-serif;
      --font-secondary: 'Roboto', sans-serif;

      /* Tamaños de fuente */
      --font-size-xl: 3rem;
      --font-size-lg: 2.8rem;
      --font-size-md: 1.4rem;
      --font-size-base: 1.2rem;
      --font-size-sm: 1rem;

      /* Pesos de fuente */
      --font-weight-bold: 700;
      --font-weight-semi: 600;
      --font-weight-normal: 400;
      --font-weight-light: 300;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    html,
    body {
      overflow-x: hidden;
    }

    body {
      background-color: var(--base);
      color: var(--oscuro);
      font-family: var(--font-secondary);
      font-size: var(--font-size-base);
      line-height: 1.6;
      letter-spacing: 0;
    }

    img,
    svg,
    video,
    canvas {
      max-width: 100%;
      height: auto;
    }

    /* Accesibilidad */
    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    /* ========================= HEADER ========================= */
    header {
      position: relative;
      height: 70px;
      background: var(--lavanda);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      border-bottom: 1px solid rgba(31, 31, 31, .10);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      position: relative;
      min-width: 0;
    }

    .brand img {
      transform: translateY(46px);
      height: 200px;
      margin-left: -52px;
      filter: drop-shadow(0 4px 8px rgba(0, 0, 0, .25));
      flex: 0 0 auto;
    }

    .brand-name {
      font-family: var(--font-primary);
      font-size: 1.9rem;
      letter-spacing: 3px;
      font-weight: 700;
      color: var(--oscuro);
      white-space: nowrap;
    }

    nav {
      display: flex;
      gap: 1.2rem;
      align-items: center;
      flex-wrap: wrap;
    }

    nav a {
      text-decoration: none;
      color: var(--oscuro);
      font-weight: var(--font-weight-semi);
      font-family: var(--font-secondary);
      letter-spacing: 0;
      padding: 6px 2px;
    }

    nav a:hover {
      text-decoration: underline;
      text-underline-offset: 4px;
    }

    /* ========================= HERO ========================= */
    .hero {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 6rem 2rem;
      background-image:
        linear-gradient(120deg, rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)),
        url("/imagenes/Landingpage/atardecergym.jpeg");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      color: var(--blanco);
    }

    .hero h1 {
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: var(--font-size-xl);
      max-width: 700px;
      line-height: 1.05;
    }

    .hero p {
      font-family: var(--font-secondary);
      margin: 1.5rem 0;
      font-size: var(--font-size-base);
      max-width: 600px;
      letter-spacing: 0;
    }

    /* ========================= BOTONES ========================= */
    .btn {
      background-color: var(--blanco);
      color: var(--oscuro);
      padding: 0.75rem 2rem;
      border-radius: 30px;
      font-weight: 800;
      text-decoration: none;
      transition: 0.25s;
      font-family: var(--font-primary);
      letter-spacing: 1px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
    }

    .btn:hover {
      background-color: #574b90;
      color: var(--blanco);
      transform: scale(1.03);
    }

    .btn_log {
      background-color: var(--oscuro);
      color: var(--blanco);
      padding: 0.75rem 2rem;
      border-radius: 30px;
      font-weight: 800;
      text-decoration: none;
      transition: 0.25s;
      font-family: var(--font-primary);
      letter-spacing: 1px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
    }

    .btn_log:hover {
      background-color: #574b90;
      transform: scale(1.03);
      color: var(--blanco);
      text-decoration: none;
    }

    /* ========================= SECCIONES ========================= */
    section {
      padding: 4rem 2rem;
      text-align: center;
    }

    /* Reutilizable con background hero-like */
    .highlight-section {
      background-image:
        linear-gradient(120deg, rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)),
        url("/imagenes/Landingpage/gym.jpg");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      min-height: 70vh;
      padding: 3rem 0;
    }

    /* ========================= LÍNEAS ========================= */
    #lineas h2 {
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: clamp(1.8rem, 2.2vw, 2.4rem);
      color: var(--oscuro);
    }

    .lines-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }

    .line-card {
      position: relative;
      height: 320px;
      border-radius: 20px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.5rem;
      color: white;
      background-size: cover;
      background-position: center;
      transition: .25s;
      text-align: left;
    }

    a.line-card {
      text-decoration: none;
      color: inherit;
    }

    .line-card::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.2));
    }

    .line-card:hover {
      transform: translateY(-5px);
    }

    .line-card:hover h3 {
      transform: scale(1.08);
      transition: 0.3s ease;
    }

    .line-card h3 {
      position: absolute;
      top: 14px;
      left: 14px;
      background: var(--lavanda);
      color: var(--oscuro);
      padding: 0.45rem 1.1rem;
      border-radius: 999px;
      font-size: 1rem;
      font-weight: 600;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      font-family: var(--font-primary);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
      z-index: 3;
    }

    .line-card p {
      position: relative;
      z-index: 2;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      opacity: .9;
    }

    .line-1 {
      background-image: url("/imagenes/Landingpage/musgo.jpg");
    }

    .line-2 {
      background-image: url("/imagenes/Landingpage/levantamiento_cadera.jpg");
    }

    .line-3 {
      background-image: url("/imagenes/Landingpage/gymgriss.jpeg");
    }

    .line-4 {
      background-image: url("/imagenes/Landingpage/variedad.jpeg");
    }

    /* ========================= CONTENEDOR ========================= */
    .container {
      padding: 1rem 2rem;
    }

    .section-title-2 {
      font-size: var(--font-size-lg);
      font-weight: var(--font-weight-bold);
      text-align: center;
      margin-bottom: 1rem;
      color: #000;
      font-family: var(--font-primary);
      letter-spacing: 1px;
    }

    .section-title {
      font-size: var(--font-size-lg);
      font-weight: var(--font-weight-bold);
      text-align: center;
      margin-bottom: 1rem;
      color: #fff;
      font-family: var(--font-primary);
      letter-spacing: 1px;
    }

    .section-subtitle {
      text-align: center;
      font-size: var(--font-size-base);
      color: #fff;
      max-width: 700px;
      margin: 0 auto 3rem;
      line-height: 1.6;
      font-family: var(--font-secondary);
      letter-spacing: 0;
    }

    /* ========================= #NUTRICION ========================= */
    #nutricion {
      padding: 0;
    }

    #nutricion.highlight-section {
      min-height: auto !important;
      padding: 2rem 0 !important;
    }

    .nutrition-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }

    /* envíos (estilo pro) */
    #nutricion .shipping-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.2rem;
      align-items: stretch;
    }

    #nutricion .shipping-panel {
      background: rgba(255, 255, 255, .88);
      border: 1px solid rgba(31, 31, 31, .08);
      border-radius: 18px;
      padding: 1.2rem 1.2rem;
      box-shadow: 0 12px 30px rgba(31, 31, 31, .06);
      transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
      backdrop-filter: blur(10px);
      text-align: left;
    }

    #nutricion .shipping-panel:hover {
      transform: translateY(-2px);
      box-shadow: 0 18px 42px rgba(31, 31, 31, .10);
      border-color: rgba(31, 31, 31, .14);
    }

    #nutricion .shipping-title {
      margin: 0 0 .8rem 0;
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: 1.35rem;
      color: var(--oscuro);
    }

    #nutricion .shipping-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: grid;
      gap: .75rem;
    }

    #nutricion .shipping-list li {
      display: grid;
      grid-template-columns: 34px 1fr;
      gap: .75rem;
      align-items: start;
      padding: .65rem .7rem;
      border-radius: 14px;
      transition: background .2s ease, transform .2s ease;
    }

    #nutricion .shipping-list li:hover {
      background: rgba(201, 173, 214, .18);
      transform: translateY(-1px);
    }

    #nutricion .ship-ico {
      width: 34px;
      height: 34px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      color: var(--oscuro);
      background: rgba(237, 199, 255, .35);
      border: 1px solid rgba(31, 31, 31, .08);
    }

    #nutricion .ship-ico svg {
      width: 18px;
      height: 18px;
    }

    #nutricion .shipping-list strong {
      font-family: var(--font-secondary);
      letter-spacing: 0;
      font-weight: 700;
      color: var(--oscuro);
    }

    #nutricion .shipping-list span {
      font-family: var(--font-secondary);
      letter-spacing: 0;
      color: rgba(31, 31, 31, .78);
    }

    #nutricion .shipping-cta {
      display: flex;
      flex-wrap: wrap;
      gap: .65rem;
      margin-top: 1rem;
    }

    #nutricion .btn-ghost,
    #nutricion .btn-solid {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      padding: .75rem .95rem;
      border-radius: 14px;
      text-decoration: none;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      font-weight: 700;
      transition: transform .2s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
      will-change: transform;
    }

    #nutricion .btn-ghost {
      color: var(--oscuro);
      background: rgba(255, 255, 255, .65);
      border: 1px solid rgba(31, 31, 31, .14);
    }

    #nutricion .btn-solid {
      color: var(--blanco);
      background: var(--oscuro);
      border: 1px solid rgba(31, 31, 31, .18);
      box-shadow: 0 10px 24px rgba(31, 31, 31, .18);
    }

    #nutricion .btn-ghost:hover,
    #nutricion .btn-solid:hover {
      transform: translateY(-1px);
    }

    #nutricion .btn-ghost:active,
    #nutricion .btn-solid:active {
      transform: translateY(0px) scale(.99);
    }

    #nutricion .shipping-panel--accent {
      background: rgba(255, 255, 255, .82) !important;
    }

    #nutricion .shipping-note {
      margin-top: 1rem;
      padding: .9rem .95rem;
      border-radius: 16px;
      background: rgba(255, 255, 255, .70);
      border: 1px solid rgba(31, 31, 31, .10);
    }

    #nutricion .shipping-note p {
      margin: 0;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      color: rgba(31, 31, 31, .80);
    }

    /* ✅ Estilos faltantes: tracking */
    #nutricion .track-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: .9rem;
      margin-bottom: .85rem;
    }

    #nutricion .track-title {
      margin: 0;
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: 1.25rem;
      color: var(--oscuro);
    }

    #nutricion .track-sub {
      margin: .35rem 0 0;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      color: rgba(31, 31, 31, .78);
      font-size: .95rem;
      line-height: 1.45;
    }

    #nutricion .track-pill {
      display: inline-flex;
      align-items: center;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(237, 199, 255, .35);
      border: 1px solid rgba(31, 31, 31, .10);
      font-weight: 800;
      font-size: .85rem;
      white-space: nowrap;
      height: fit-content;
      font-family: var(--font-secondary);
      letter-spacing: 0;
    }

    #nutricion .track-form {
      display: grid;
      gap: .75rem;
      margin-top: .4rem;
    }

    #nutricion .track-input {
      width: 100%;
      padding: .85rem .9rem;
      border-radius: 14px;
      border: 1px solid rgba(31, 31, 31, .14);
      background: rgba(255, 255, 255, .86);
      font-family: var(--font-secondary);
      letter-spacing: 0;
      font-size: 1rem;
      outline: none;
    }

    #nutricion .track-input:focus {
      border-color: rgba(87, 75, 144, .55);
      box-shadow: 0 0 0 4px rgba(237, 199, 255, .25);
    }

    #nutricion .track-actions {
      display: flex;
      gap: .65rem;
      flex-wrap: wrap;
    }

    #nutricion .track-btn {
      border: 1px solid rgba(31, 31, 31, .14);
      border-radius: 14px;
      padding: .8rem 1rem;
      cursor: pointer;
      font-weight: 900;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      transition: transform .2s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
    }

    #nutricion .track-btn--solid {
      background: var(--oscuro);
      color: var(--blanco);
      border-color: rgba(31, 31, 31, .18);
      box-shadow: 0 10px 24px rgba(31, 31, 31, .18);
    }

    #nutricion .track-btn--solid:hover {
      transform: translateY(-1px);
    }

    #nutricion .track-btn--solid:active {
      transform: translateY(0) scale(.99);
    }

    /* ========================= mapa + 3 columnas (#nutricion) ========================= */
    #nutricion .nutrition-grid.shipping-grid {
      display: grid;
      gap: 1rem !important;
      align-items: start !important;
      grid-template-columns: 1fr !important;
    }

    @media (min-width: 900px) {
      #nutricion .nutrition-grid.shipping-grid {
        grid-template-columns: .90fr .60fr 1.50fr !important;
      }
    }

    #nutricion .ship-map-card {
      margin-top: 0 !important;
      border-radius: 16px !important;
      overflow: hidden !important;
      background: rgba(255, 255, 255, .86) !important;
      border: 1px solid rgba(31, 31, 31, .08) !important;
      box-shadow: 0 12px 26px rgba(0, 0, 0, .10) !important;
      text-align: left;
    }

    #nutricion .ship-map-wrap {
      padding: .85rem .9rem 1rem !important;
    }

    #nutricion .ship-map {
      width: 100% !important;
      height: clamp(320px, 52vh, 560px) !important;
      max-height: 560px !important;
      margin: 0 auto !important;
      border-radius: 14px !important;
      border: 1px solid rgba(31, 31, 31, .08) !important;
      background: rgba(237, 199, 255, .10) !important;
      position: relative;
      overflow: hidden;
    }

    #nutricion #map-ecuador {
      height: 100%;
      width: 100%;
    }

    #nutricion .leaflet-container {
      font-family: var(--font-secondary);
      border-radius: 14px;
      letter-spacing: 0;
    }

    #nutricion .leaflet-control-zoom {
      border: 1px solid rgba(31, 31, 31, .10);
      box-shadow: 0 10px 22px rgba(0, 0, 0, .12);
      border-radius: 14px;
      overflow: hidden;
    }

    #nutricion .leaflet-control-zoom a {
      color: var(--oscuro);
    }

    .bf-pin {
      position: relative;
      width: 26px;
      height: 26px;
      transform: translateY(-2px);
      filter: drop-shadow(0 6px 10px rgba(0, 0, 0, .22));
    }

    .bf-pin svg {
      width: 26px;
      height: 26px;
      display: block;
    }

    .bf-pin .pin-body {
      fill: rgba(31, 31, 31, .95);
    }

    .bf-pin .pin-hole {
      fill: rgba(255, 255, 255, .96);
    }

    .bf-pin::after {
      content: "";
      position: absolute;
      left: 50%;
      top: 50%;
      width: 28px;
      height: 28px;
      transform: translate(-50%, -50%);
      border-radius: 999px;
      border: 2px solid rgba(201, 173, 214, .95);
      opacity: .35;
      animation: bfPulse 2.2s ease-in-out infinite;
    }

    .bf-pin--galapagos .pin-body {
      fill: rgba(87, 75, 144, .95);
    }

    @keyframes bfPulse {
      0% {
        transform: translate(-50%, -50%) scale(.75);
        opacity: .10;
      }

      55% {
        transform: translate(-50%, -50%) scale(1.15);
        opacity: .35;
      }

      100% {
        transform: translate(-50%, -50%) scale(1.45);
        opacity: 0;
      }
    }

    .leaflet-tooltip.bf-tooltip {
      background: rgba(255, 255, 255, .92);
      border: 1px solid rgba(31, 31, 31, .12);
      color: rgba(31, 31, 31, .90);
      border-radius: 12px;
      box-shadow: 0 10px 22px rgba(0, 0, 0, .12);
      padding: 6px 10px;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      font-weight: 700;
    }

    .leaflet-tooltip.bf-tooltip::before {
      border-top-color: rgba(31, 31, 31, .12);
    }

    /* ========================= NOTICIAS ========================= */
    .news-section {
      padding: 4rem 2rem;
      background: #fff;
    }

    .news-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }

    .news-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
      transition: .25s;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
    }

    .news-card:hover {
      transform: translateY(-6px);
    }

    .news-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .news-content {
      padding: 1rem;
    }

    .news-content h4 {
      font-size: 1rem;
      margin-bottom: .4rem;
      font-family: var(--font-primary);
      letter-spacing: 1px;
    }

    .news-content p {
      font-size: .85rem;
      color: #666;
      font-family: var(--font-secondary);
      letter-spacing: 0;
    }

    /* ==========================================================
     ✅ #comprar — 2 columnas FULL WIDTH (Políticas | Rutas)
     y TAB Tienda con MAPA + Dirección integrado
     ========================================================== */

    #comprar.buy4 {
      width: 100vw;
      margin-left: calc(50% - 50vw);
      margin-right: calc(50% - 50vw);
      padding: 2.8rem 0 !important;
      text-align: left;
      overflow-x: clip;

      background:
        radial-gradient(900px 420px at 14% 6%, rgba(237, 199, 255, .22), transparent 60%),
        radial-gradient(720px 380px at 88% 12%, rgba(201, 173, 214, .16), transparent 58%),
        linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(245, 227, 250, .55));
    }

    #comprar.buy4 .buy4-container {
      width: 100%;
      max-width: none;
      margin: 0;
      padding: 0 clamp(14px, 3.6vw, 56px);
    }

    #comprar.buy4,
    #comprar.buy4 * {
      font-family: var(--font-secondary);
      letter-spacing: 0;
      box-sizing: border-box;
    }

    #comprar.buy4 {
      --buy4-radius: 18px;
      --buy4-border: rgba(31, 31, 31, .10);
      --buy4-shadow: 0 14px 34px rgba(0, 0, 0, .08);
      --buy4-shadow2: 0 18px 46px rgba(0, 0, 0, .10);
    }

    #comprar.buy4 .buy4-header {
      text-align: center;
      display: grid;
      justify-items: center;
      gap: 10px;
      margin-bottom: 16px;
    }

    #comprar.buy4 .buy4-kicker {
      margin: 0;
      opacity: .72;
      font-size: .95rem;
    }

    #comprar.buy4 .buy4-title {
      margin: 0;
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: clamp(1.65rem, 2.2vw, 2.25rem);
      line-height: 1.05;
      color: var(--oscuro);
      position: relative;
      padding-bottom: 10px;
    }

    #comprar.buy4 .buy4-title::after {
      content: "";
      display: block;
      width: 84px;
      height: 3px;
      border-radius: 999px;
      margin: 10px auto 0;
      background: rgba(31, 31, 31, .14);
    }

    #comprar.buy4 .buy4-sub {
      margin: 0;
      opacity: .86;
      max-width: 76ch;
      font-size: .99rem;
      line-height: 1.5;
    }

    #comprar.buy4 .buy4-ctas {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 6px;
      justify-content: center;
    }

    #comprar.buy4 .buy4-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .55rem;
      padding: 10px 14px;
      border-radius: 14px;
      text-decoration: none;
      font-weight: 900;
      border: 1px solid rgba(31, 31, 31, .14);
      transition: transform .12s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
      will-change: transform;
    }

    #comprar.buy4 .buy4-btn:hover {
      transform: translateY(-1px);
    }

    #comprar.buy4 .buy4-btn:active {
      transform: translateY(0px) scale(.99);
    }

    #comprar.buy4 .buy4-btn--solid {
      background: var(--oscuro);
      color: var(--blanco);
      border-color: rgba(31, 31, 31, .18);
      box-shadow: 0 12px 24px rgba(0, 0, 0, .14);
    }

    #comprar.buy4 .buy4-btn--ghost {
      background: rgba(255, 255, 255, .80);
      color: var(--oscuro);
    }

    #comprar.buy4 .buy4-btn--full {
      width: 100%;
      margin-top: 10px;
      padding: 11px 14px;
      border-radius: 14px;
    }

    #comprar.buy4 .buy4-meta {
      margin: 10px 0 0;
      padding: 0;
      list-style: none;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
    }

    #comprar.buy4 .buy4-meta li {
      padding: 7px 11px;
      border-radius: 999px;
      border: 1px solid rgba(31, 31, 31, .10);
      background: rgba(255, 255, 255, .74);
      box-shadow: 0 10px 16px rgba(0, 0, 0, .04);
      font-size: .9rem;
    }

    /* ✅ GRID 2 COLUMNAS (Políticas | Rutas) */
    #comprar.buy4 .buy4-grid {
      display: grid;
      gap: 1rem;
      align-items: start;
      grid-template-columns: 1fr 1.6fr;
      /* POLÍTICAS PRIMERO */
    }

    @media (max-width: 980px) {
      #comprar.buy4 .buy4-grid {
        grid-template-columns: 1fr;
      }
    }

    #comprar.buy4 .buy4-card {
      background: rgba(255, 255, 255, .82);
      border: 1px solid var(--buy4-border);
      border-radius: var(--buy4-radius);
      padding: 14px;
      box-shadow: var(--buy4-shadow);
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      position: relative;
      overflow: clip;
    }

    #comprar.buy4 .buy4-card::before {
      content: "";
      position: absolute;
      inset: -2px;
      background:
        radial-gradient(520px 180px at 18% 0%, rgba(237, 199, 255, .22), transparent 62%),
        radial-gradient(520px 180px at 88% 12%, rgba(201, 173, 214, .14), transparent 60%);
      pointer-events: none;
      opacity: .7;
    }

    #comprar.buy4 .buy4-card>* {
      position: relative;
      z-index: 2;
    }

    #comprar.buy4 .buy4-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--buy4-shadow2);
      border-color: rgba(31, 31, 31, .14);
    }

    /* Sticky SOLO para políticas (desktop) */
    @media (min-width: 981px) {
      #comprar.buy4 .buy4-card--policies {
        position: sticky;
        top: 92px;
      }
    }

    #comprar.buy4 .buy4-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 10px;
    }

    #comprar.buy4 .buy4-card-tag {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255, 255, 255, .76);
      border: 1px solid rgba(31, 31, 31, .10);
      font-weight: 900;
      font-size: .86rem;
      color: rgba(31, 31, 31, .84);
      box-shadow: 0 10px 18px rgba(0, 0, 0, .05);
      white-space: nowrap;
    }

    #comprar.buy4 .buy4-card-tag small {
      opacity: .72;
      font-weight: 700;
    }

    /* TABS con indicador */
    #comprar.buy4 .buy4-tabs {
      --buy4-ind-left: 0px;
      --buy4-ind-width: 0px;

      background: rgba(31, 31, 31, .04);
      border: 1px solid rgba(31, 31, 31, .10);
      border-radius: 999px;
      padding: 6px;
      display: inline-flex;
      gap: 6px;
      width: fit-content;
      position: relative;
      overflow: hidden;
      max-width: 100%;
    }

    #comprar.buy4 .buy4-tabs::after {
      content: "";
      position: absolute;
      top: 6px;
      bottom: 6px;
      left: var(--buy4-ind-left);
      width: var(--buy4-ind-width);
      border-radius: 999px;
      background: rgba(31, 31, 31, .92);
      box-shadow: 0 12px 22px rgba(0, 0, 0, .14);
      transition: left .22s ease, width .22s ease;
      z-index: 0;
    }

    #comprar.buy4 .buy3-tab {
      position: relative;
      z-index: 1;
      border: 1px solid transparent;
      background: transparent;
      border-radius: 999px;
      padding: 9px 12px;
      cursor: pointer;
      font-weight: 900;
      color: rgba(31, 31, 31, .86);
      transition: transform .14s ease, color .18s ease;
      display: inline-flex;
      align-items: center;
      gap: .55rem;
      white-space: nowrap;
    }

    #comprar.buy4 .buy3-tab:hover {
      transform: translateY(-1px);
    }

    #comprar.buy4 .buy3-tab.is-active {
      color: #fff;
    }

    #comprar.buy4 .buy4-h {
      margin: 12px 0 6px;
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: 1.18rem;
    }

    #comprar.buy4 .buy4-p {
      margin: 0 0 10px;
      opacity: .86;
      font-size: .95rem;
      line-height: 1.5;
    }

    #comprar.buy4 .buy3-panel {
      animation: buy4FadeUp .18s ease both;
    }

    @keyframes buy4FadeUp {
      from {
        opacity: 0;
        transform: translateY(6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ✅ Steps con NUMERACIÓN */
    #comprar.buy4 .buy4-steps {
      list-style: none;
      padding: 0;
      margin: 10px 0 0;
      display: grid;
      gap: 10px;
      grid-template-columns: 1fr;
      counter-reset: buy4step;
    }

    @media (min-width: 1100px) {
      #comprar.buy4 .buy4-steps {
        grid-template-columns: 1fr 1fr;
      }

      #comprar.buy4 .buy4-steps li:nth-child(5) {
        grid-column: 1 / -1;
      }
    }

    #comprar.buy4 .buy4-steps li {
      padding: 11px 12px;
      border-radius: 14px;
      border: 1px solid rgba(31, 31, 31, .08);
      background: rgba(255, 255, 255, .70);
      display: grid;
      grid-template-columns: 44px 1fr;
      gap: 10px;
      align-items: start;
      counter-increment: buy4step;
    }

    #comprar.buy4 .buy4-steps li::before {
      content: counter(buy4step);
      width: 44px;
      height: 44px;
      display: grid;
      place-items: center;
      border-radius: 14px;
      background: rgba(237, 199, 255, .32);
      border: 1px solid rgba(31, 31, 31, .08);
      font-size: 15px;
      font-weight: 900;
      color: rgba(31, 31, 31, .92);
      box-shadow: 0 10px 18px rgba(0, 0, 0, .05);
    }

    #comprar.buy4 .buy4-steps li strong {
      display: block;
      margin: 0;
      font-weight: 900;
      color: rgba(31, 31, 31, .92);
    }

    #comprar.buy4 .buy4-steps li span {
      display: block;
      margin-top: 2px;
      font-size: .92rem;
      opacity: .86;
      line-height: 1.45;
    }

    #comprar.buy4 .buy4-note {
      margin-top: 10px;
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px dashed rgba(31, 31, 31, .16);
      background: rgba(245, 227, 250, .30);
      font-size: .92rem;
      line-height: 1.45;
    }

    #comprar.buy4 .buy4-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }

    /* Políticas */
    #comprar.buy4 .buy4-acc {
      border: 1px solid rgba(31, 31, 31, .10);
      border-radius: 14px;
      padding: 10px 10px;
      background: rgba(255, 255, 255, .74);
      margin-top: 10px;
    }

    #comprar.buy4 .buy4-acc summary {
      cursor: pointer;
      font-weight: 900;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 4px 2px;
    }

    #comprar.buy4 .buy4-acc summary::after {
      content: "▾";
      font-weight: 900;
      opacity: .55;
      transition: transform .18s ease;
    }

    #comprar.buy4 .buy4-acc[open] summary::after {
      transform: rotate(180deg);
    }

    #comprar.buy4 .buy4-acc[open] {
      background: rgba(245, 227, 250, .34);
      border-color: rgba(31, 31, 31, .12);
    }

    #comprar.buy4 .buy4-acc:nth-of-type(1) summary::before {
      content: "🧾 ";
    }

    #comprar.buy4 .buy4-acc:nth-of-type(2) summary::before {
      content: "🔁 ";
    }

    #comprar.buy4 .buy4-list {
      list-style: none;
      padding: 0;
      margin: 10px 0 0;
      display: grid;
      gap: 8px;
    }

    #comprar.buy4 .buy4-list li {
      display: grid;
      grid-template-columns: 16px 1fr;
      grid-template-rows: auto auto;
      column-gap: 8px;
      row-gap: 2px;
      align-items: start;
    }

    #comprar.buy4 .buy4-list li::before {
      content: "✨";
      grid-column: 1;
      grid-row: 1;
      opacity: .40;
      transform: translateY(4px);
    }

    #comprar.buy4 .buy4-list li>strong {
      grid-column: 2;
      grid-row: 1;
      display: block;
      font-weight: 900;
      color: rgba(31, 31, 31, .92);
    }

    #comprar.buy4 .buy4-list li>span {
      grid-column: 2;
      grid-row: 2;
      display: block;
      opacity: .84;
      font-weight: 500;
      font-size: .92rem;
      line-height: 1.45;
      margin-left: 0 !important;
    }

    /* ===== Tienda dentro del TAB (mapa + dirección) ===== */
    #comprar.buy4 .store-mini {
      margin-top: 12px;
      display: grid;
      gap: 10px;
    }

    #comprar.buy4 .store-kv {
      display: grid;
      grid-template-columns: 34px 1fr;
      gap: 10px;
      align-items: start;
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px solid rgba(31, 31, 31, .08);
      background: rgba(255, 255, 255, .70);
    }

    #comprar.buy4 .store-ico {
      width: 34px;
      height: 34px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      background: rgba(237, 199, 255, .32);
      border: 1px solid rgba(31, 31, 31, .08);
      box-shadow: 0 10px 18px rgba(0, 0, 0, .05);
      font-size: 18px;
    }

    #comprar.buy4 .store-kv strong {
      font-weight: 900;
      color: rgba(31, 31, 31, .92);
      display: block;
    }

    #comprar.buy4 .store-kv span,
    #comprar.buy4 .store-kv a {
      font-weight: 500;
      font-size: .92rem;
      opacity: .86;
      line-height: 1.45;
      color: rgba(31, 31, 31, .88);
      text-decoration: none;
    }

    #comprar.buy4 .store-kv a:hover {
      text-decoration: underline;
    }

    #comprar.buy4 .store-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }

    #comprar.buy4 .store-map {
      margin-top: 10px;
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid rgba(31, 31, 31, .10);
      background: rgba(237, 199, 255, .10);
      height: 260px;
      position: relative;
    }

    #comprar.buy4 #map-store {
      width: 100%;
      height: 100%;
    }

    #comprar.buy4 .leaflet-container {
      font-family: var(--font-secondary);
      letter-spacing: 0;
    }

    #comprar.buy4 .leaflet-control-zoom {
      border: 1px solid rgba(31, 31, 31, .10);
      box-shadow: 0 10px 22px rgba(0, 0, 0, .12);
      border-radius: 14px;
      overflow: hidden;
    }

    /* Reduce motion */
    @media (prefers-reduced-motion: reduce) {
      * {
        scroll-behavior: auto !important;
      }

      .bf-pin::after {
        animation: none !important;
      }

      #comprar.buy4 .buy3-panel {
        animation: none !important;
      }
    }

    /* ========================= FOOTER PRO (BootyFitness) ========================= */
    .site-footer {
      position: relative;
      width: 100%;
      padding: 3.2rem 0 1.4rem;
      text-align: left;
      overflow: hidden;

      background:
        radial-gradient(900px 420px at 14% 6%, rgba(237, 199, 255, .26), transparent 60%),
        radial-gradient(720px 380px at 88% 12%, rgba(201, 173, 214, .20), transparent 58%),
        linear-gradient(180deg, rgba(31, 31, 31, .96), rgba(31, 31, 31, .92));
      color: rgba(255, 255, 255, .92);
    }

    .site-footer * {
      font-family: var(--font-secondary);
      letter-spacing: 0;
      box-sizing: border-box;
    }

    .site-footer .footer-container {
      width: 100%;
      max-width: none;
      padding: 0 clamp(14px, 3.6vw, 56px);
    }

    .site-footer .footer-top {
      display: grid;
      gap: 1.2rem;
      grid-template-columns: 1.2fr .9fr .9fr;
      align-items: start;
    }

    @media (max-width: 980px) {
      .site-footer .footer-top {
        grid-template-columns: 1fr;
      }
    }

    .site-footer .footer-brand {
      display: grid;
      gap: .85rem;
      padding: 16px 16px;
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, .10);
      background: rgba(255, 255, 255, .06);
      box-shadow: 0 18px 46px rgba(0, 0, 0, .28);
    }

    .site-footer .footer-brand h3 {
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: 1.75rem;
      margin: 0;
      line-height: 1;
    }

    .site-footer .footer-brand p {
      margin: 0;
      opacity: .86;
      line-height: 1.55;
      font-size: .98rem;
    }

    .site-footer .footer-cta {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 4px;
    }

    .site-footer .fbtn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .55rem;
      padding: 10px 12px;
      border-radius: 14px;
      text-decoration: none;
      font-weight: 900;
      border: 1px solid rgba(255, 255, 255, .12);
      transition: transform .12s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
      will-change: transform;
      color: rgba(255, 255, 255, .92);
      background: rgba(255, 255, 255, .06);
    }

    .site-footer .fbtn:hover {
      transform: translateY(-1px);
    }

    .site-footer .fbtn:active {
      transform: translateY(0px) scale(.99);
    }

    .site-footer .fbtn--solid {
      background: rgba(237, 199, 255, .18);
      border-color: rgba(237, 199, 255, .28);
      box-shadow: 0 12px 24px rgba(0, 0, 0, .20);
    }

    .site-footer .fbtn--wa {
      background: rgba(37, 211, 102, .18);
      border-color: rgba(37, 211, 102, .28);
    }

    .site-footer .footer-col {
      padding: 16px 16px;
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, .10);
      background: rgba(255, 255, 255, .05);
    }

    .site-footer .footer-col h4 {
      margin: 0 0 .75rem;
      font-weight: 900;
      font-size: 1rem;
      color: rgba(255, 255, 255, .96);
    }

    .site-footer .footer-links {
      list-style: none;
      margin: 0;
      padding: 0;
      display: grid;
      gap: .55rem;
    }

    .site-footer .footer-links a {
      text-decoration: none;
      color: rgba(255, 255, 255, .86);
      opacity: .92;
      transition: opacity .18s ease, transform .18s ease;
      display: inline-flex;
      gap: .55rem;
      align-items: center;
    }

    .site-footer .footer-links a:hover {
      opacity: 1;
      transform: translateX(2px);
    }

    .site-footer .badge-row {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: .65rem;
    }

    .site-footer .badge {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, .10);
      background: rgba(255, 255, 255, .06);
      font-size: .88rem;
      opacity: .92;
    }

    .site-footer .footer-bottom {
      margin-top: 1.1rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255, 255, 255, .10);
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      justify-content: space-between;
      opacity: .9;
    }

    .site-footer .footer-bottom small {
      font-size: .9rem;
      color: rgba(255, 255, 255, .76);
    }

    .site-footer .footer-mini {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .site-footer .footer-mini a {
      color: rgba(255, 255, 255, .72);
      text-decoration: none;
      font-weight: 700;
    }

    .site-footer .footer-mini a:hover {
      color: rgba(255, 255, 255, .92);
      text-decoration: underline;
    }

    /* ========================= SOCIAL STRIP (con íconos) ========================= */
    #social.social-strip {
      width: 100vw;
      margin-left: calc(50% - 50vw);
      margin-right: calc(50% - 50vw);
      padding: 2.2rem 0;
      background:
        radial-gradient(900px 420px at 10% 20%, rgba(237, 199, 255, .22), transparent 60%),
        radial-gradient(720px 380px at 90% 10%, rgba(201, 173, 214, .16), transparent 58%),
        linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(245, 227, 250, .55));
    }

    #social.social-strip .social-wrap {
      padding: 0 clamp(14px, 3.6vw, 56px);
      display: grid;
      gap: 10px;
      justify-items: center;
      text-align: center;
    }

    #social.social-strip h2 {
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: clamp(1.55rem, 2vw, 2.1rem);
      margin: 0;
      color: var(--oscuro);
    }

    #social.social-strip p {
      margin: 0;
      max-width: 70ch;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      color: rgba(31, 31, 31, .75);
      line-height: 1.5;
      font-size: .98rem;
    }

    #social.social-strip .socials {
      margin-top: 10px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }

    #social.social-strip .socials a {
      display: inline-flex;
      align-items: center;
      gap: .55rem;
      padding: 10px 12px;
      border-radius: 14px;
      text-decoration: none;
      font-family: var(--font-secondary);
      letter-spacing: 0;
      font-weight: 900;
      color: rgba(31, 31, 31, .92);
      background: rgba(255, 255, 255, .85);
      border: 1px solid rgba(31, 31, 31, .10);
      box-shadow: 0 12px 26px rgba(0, 0, 0, .08);
      transition: transform .12s ease, box-shadow .18s ease;
    }

    #social.social-strip .socials a:hover {
      transform: translateY(-1px);
      box-shadow: 0 18px 42px rgba(0, 0, 0, .12);
    }

    #social.social-strip .socials svg {
      width: 18px;
      height: 18px;
      display: block;
    }

    /* Accesibilidad: texto solo para lectores de pantalla */
    .sr-only {
      position: absolute !important;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }

    /* =========================
   ✅ FOOTER SOCIAL (arreglo total)
   ========================= */

    .site-footer .footer-social {
      margin-bottom: 1.2rem;
      padding: 16px 16px 18px;
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, .10);
      background: rgba(255, 255, 255, .06);
      box-shadow: 0 18px 46px rgba(0, 0, 0, .28);
    }

    .site-footer .footer-social h2 {
      margin: 0 0 .45rem;
      font-family: var(--font-primary);
      letter-spacing: 1px;
      font-size: 1.6rem;
      color: rgba(255, 255, 255, .96);
      line-height: 1.05;
    }

    .site-footer .footer-social p {
      margin: 0;
      max-width: 80ch;
      color: rgba(255, 255, 255, .84);
      line-height: 1.55;
      font-size: .98rem;
    }

    .site-footer .footer-social .socials {
      margin-top: 12px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: flex-start;
    }

    .site-footer .footer-social .socials a {
      display: inline-flex;
      align-items: center;
      gap: .55rem;
      padding: 10px 12px;
      border-radius: 14px;
      text-decoration: none;
      font-weight: 900;
      font-family: var(--font-secondary);
      letter-spacing: 0;

      color: rgba(255, 255, 255, .92);
      background: rgba(255, 255, 255, .06);
      border: 1px solid rgba(255, 255, 255, .12);

      transition: transform .12s ease, box-shadow .18s ease, background .18s ease;
    }

    .site-footer .footer-social .socials a:hover {
      transform: translateY(-1px);
      background: rgba(255, 255, 255, .09);
      box-shadow: 0 12px 24px rgba(0, 0, 0, .22);
    }

    .site-footer .footer-social .socials a:active {
      transform: translateY(0) scale(.99);
    }

    .site-footer .footer-social .socials svg {
      width: 18px;
      height: 18px;
      flex: 0 0 18px;
      display: block;
    }

    /* ==========================================================
   ✅ RESPONSIVE (solo CSS, sin cambiar contenido / funcionalidad)
   ========================================================== */

    /* Tablets y abajo */
    @media (max-width: 1024px) {
      header {
        padding: 0 1rem;
      }

      section {
        padding: 3.25rem 1.25rem;
      }

      .container {
        padding: 1rem 1.25rem;
      }

      .hero {
        padding: 5rem 1.25rem;
      }

      .news-section {
        padding: 3.25rem 1.25rem;
      }
    }

    /* Móvil: header y nav 100% usable sin romper */
    @media (max-width: 820px) {
      header {
        height: auto;
        min-height: 70px;
        padding: .75rem 1rem;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
      }

      .brand {
        width: 100%;
        justify-content: flex-start;
        gap: .65rem;
      }

      /* Evita que el logo grande “se salga” en móvil */
      .brand img {
        transform: translateY(0);
        height: 74px;
        margin-left: 0;
      }

      .brand-name {
        font-size: 1.55rem;
        letter-spacing: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      /* Nav en una sola fila con scroll horizontal (sin cambiar contenido) */
      nav {
        width: 100%;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        gap: .85rem;
        padding-bottom: 4px;
      }

      nav::-webkit-scrollbar {
        height: 0px;
      }

      nav a {
        white-space: nowrap;
        font-size: .98rem;
      }

      /* Botones de nav más compactos en móvil */
      .btn_log {
        padding: .6rem 1rem;
        border-radius: 999px;
        font-size: .95rem;
      }
    }

    /* Móvil pequeño: tipografías y espacios */
    @media (max-width: 520px) {
      section {
        padding: 2.6rem 1rem;
      }

      .news-section {
        padding: 2.6rem 1rem;
      }

      .hero {
        padding: 4.2rem 1rem;
      }

      .hero h1 {
        font-size: clamp(2.05rem, 7vw, 2.65rem);
        max-width: 20ch;
      }

      .hero p {
        font-size: 1.05rem;
        max-width: 55ch;
      }

      .btn {
        padding: .7rem 1.35rem;
      }

      .lines-grid {
        gap: 1.25rem;
      }

      .line-card {
        height: 280px;
        padding: 1.2rem;
      }

      .line-card h3 {
        font-size: .95rem;
      }

      /* Mapas: reduce altura para pantallas pequeñas */
      #nutricion .ship-map {
        height: clamp(260px, 42vh, 420px) !important;
        max-height: 420px !important;
      }

      /* Comprar: evita que full-width genere “saltos” en móviles (mantiene diseño) */
      #comprar.buy4 {
        width: 100%;
        margin-left: 0;
        margin-right: 0;
      }
    }

    /* Extra pequeño: todo cabe sin overflow */
    @media (max-width: 380px) {
      .brand img {
        height: 64px;
      }

      .brand-name {
        font-size: 1.4rem;
        letter-spacing: 1.5px;
      }

      nav a {
        font-size: .94rem;
      }

      .btn_log {
        padding: .55rem .9rem;
        font-size: .92rem;
      }

      #comprar.buy4 .buy4-steps li {
        grid-template-columns: 40px 1fr;
      }

      #comprar.buy4 .buy4-steps li::before {
        width: 40px;
        height: 40px;
      }
    }

    .buy3-tab .bi {
      font-size: 30px;
      line-height: 1;
    }

    
  </style>
</head>

<body>
  <header>
    <div class="brand">
      <img src="{{ asset('imagenes/logo_booty.svg') }}" alt="Logo">
      <span class="brand-name">BOOTYFITNESS</span>
    </div>

    <nav>
      <a href="#lineas">Ropa</a>
      <a href="#nutricion">Nutrición</a>
      <a href="#comprar">Cómo comprar</a>
      <a href="#noticias">Noticias</a>
      <a href="#social">Redes</a>
      <a href="/login" class="btn_log">Iniciar Sesión</a>
      <!-- <a href="/tienda" class="btn_log">Ir a la Tienda</a> -->
      <a href="{{ route('catalogo.descargar') }}" class="btn_log">📥Catálogo PDF</a>
    </nav>
  </header>

  <section class="hero">
    <h1>Más que fitness, somos tu segunda piel</h1>
    <p>Descubre la ropa que se adapta a ti, planes que te empoderan y una comunidad que te inspira.</p>
    <a href="#lineas" class="btn">Explorar Colección</a>
  </section>

  <section id="lineas">
    @php
      $lineasMap = ($lineasLanding ?? collect())->keyBy(function ($linea) {
        return \Illuminate\Support\Str::of($linea->nombre_linea)->lower()->ascii()->value();
      });

      $lineaOner = $lineasMap->first(fn ($linea, $k) => str_contains($k, 'oner'));
      $lineaGymshark = $lineasMap->first(fn ($linea, $k) => str_contains($k, 'gymshark'));
      $lineaAmericana = $lineasMap->first(fn ($linea, $k) => str_contains($k, 'americana'));
      $lineaBrasilena = $lineasMap->first(fn ($linea, $k) => str_contains($k, 'brasileno') || str_contains($k, 'brasilena') || str_contains($k, 'brasil'));
    @endphp
    <h2>Nuestras líneas destacadas</h2>
    <div class="lines-grid">
      <a class="line-card line-1" href="{{ route('ecommerce.productos.index', ['linea' => optional($lineaOner)->id]) }}">
        <h3>Oner Active</h3>
        <p>“Oner your body for you”</p>
      </a>

      <a class="line-card line-2" href="{{ route('ecommerce.productos.index', ['linea' => optional($lineaGymshark)->id]) }}">
        <h3>GYMSHARK</h3>
        <p>“We Do Gym.”</p>
      </a>

      <a class="line-card line-3" href="{{ route('ecommerce.productos.index', ['linea' => optional($lineaAmericana)->id]) }}">
        <h3>LÍNEA AMERICANA</h3>
        <p>“Hecho para darlo todo.”</p>
      </a>

      <a class="line-card line-4" href="{{ route('ecommerce.productos.index', ['linea' => optional($lineaBrasilena)->id]) }}">
        <h3>LÍNEA ESTILO BRASILEÑO</h3>
        <p>“Muévete con actitud.”</p>
      </a>
    </div>
  </section>

  <section id="nutricion" class="highlight-section">
    <div class="container">
      <h2 class="section-title">🚚 Envíos a todo Ecuador, con entrega segura (incluido Galápagos)</h2>
      <p class="section-subtitle">
        Tu pedido viaja <strong>protegido</strong>, con <strong>seguimiento</strong> y cobertura nacional real.
        Recíbelo con confianza, sin sorpresas.
      </p>

      <div class="nutrition-grid shipping-grid">
        <!-- Columna 1 -->
        <div class="shipping-panel">
          <h3 class="shipping-title">Entrega seria, rápida y bien empacada</h3>

          <ul class="shipping-list">
            <li>
              <span class="ship-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4z" stroke="currentColor" stroke-width="1.8" />
                  <path d="M9.5 12l1.8 1.8L15 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </span>
              <div>
                <strong>Entrega segura</strong>
                <span>— empaque protegido y control de despacho.</span>
              </div>
            </li>

            <li>
              <span class="ship-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M6 18c2.5 0 2.5-12 5-12s2.5 12 5 12 2.5-12 5-12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                  <path d="M6 18a2 2 0 110-4 2 2 0 010 4zM11 6a2 2 0 110-4 2 2 0 010 4zM16 18a2 2 0 110-4 2 2 0 010 4zM21 6a2 2 0 110-4 2 2 0 010 4z" stroke="currentColor" stroke-width="1.2" />
                </svg>
              </span>
              <div>
                <strong>Seguimiento</strong>
                <span>— te compartimos el estado para que sepas dónde va.</span>
              </div>
            </li>

            <li>
              <span class="ship-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M21 8l-9 5-9-5 9-5 9 5z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                  <path d="M3 8v10l9 5 9-5V8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                  <path d="M12 13v10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg>
              </span>
              <div>
                <strong>Protección real</strong>
                <span>— cuidamos el producto para que llegue como debe.</span>
              </div>
            </li>

            <li>
              <span class="ship-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 22s7-5.1 7-12a7 7 0 10-14 0c0 6.9 7 12 7 12z" stroke="currentColor" stroke-width="1.8" />
                  <path d="M12 11.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z" stroke="currentColor" stroke-width="1.8" />
                </svg>
              </span>
              <div>
                <strong>Cobertura total</strong>
                <span>— Costa, Sierra, Amazonía y <strong>Galápagos</strong>.</span>
              </div>
            </li>
          </ul>

          <div class="shipping-cta">
            <a href="https://www.instagram.com/stories/highlights/18008112887321965/" class="btn-ghost" target="_blank" rel="noopener noreferrer">
              Entregas seguras
            </a>

            <a class="btn-solid"
              href="https://wa.me/593978879775?text=Hola%20%20Quiero%20consultar%20tiempos%20de%20entrega%20y%20cobertura."
              target="_blank" rel="noopener noreferrer">
              Consultar por WhatsApp
            </a>
          </div>
        </div>

        <!-- Columna 2: Seguimiento -->
        <aside class="shipping-panel shipping-panel--accent ship-track" aria-label="Seguimiento de envío Servientrega">
          <div class="track-top">
            <div>
              <h3 class="track-title">📦 Seguimiento Servientrega</h3>
              <p class="track-sub">
                Ingresa tu <strong>número de guía</strong> y abre el estado en una nueva pestaña.
              </p>
            </div>
            <span class="track-pill">✅ Cobertura nacional</span>
          </div>

          <form class="track-form" action="https://www.servientrega.com.ec/Tracking/" method="GET" target="_blank" rel="noopener noreferrer">
            <input type="hidden" name="tipo" value="GUIA">
            <label class="sr-only" for="servi-guia">Número de guía</label>
            <input
              id="servi-guia"
              name="guia"
              class="track-input"
              inputmode="numeric"
              placeholder="Número de guía (ej: 123456789)"
              value=""
              required>
            <div class="track-actions">
              <button type="submit" class="track-btn track-btn--solid">Ver seguimiento</button>
            </div>
          </form>

          <div class="shipping-note">
            <p>
              Si estás en una zona de difícil acceso, te lo resolvemos:
              <strong>confirmamos ruta</strong> y te damos la mejor opción de entrega.
            </p>
          </div>
        </aside>

        <!-- Columna 3: MAPA -->
        <div class="ship-map-card" aria-label="Cobertura por provincia">
          <div class="ship-map-wrap">
            <div class="ship-map">
              <div id="map-ecuador"></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ✅ SECCIÓN FULL WIDTH: POLÍTICAS (COL 1) + RUTAS (COL 2) -->
  <section id="comprar" class="news-section buy4">
    <div class="buy4-container">

      <div class="buy4-header">
        <p class="buy4-kicker">Cómo comprar · Apartado · Cambios · Tienda</p>
        <h2 class="buy4-title">Elige tu ruta y compra rápido</h2>
        <p class="buy4-sub">
          Te asesoramos en talla/contextura, confirmamos stock y te enviamos tu guía.
          Reglas claras, cero estrés.
        </p>

        <div class="buy4-ctas">
          <a class="buy4-btn buy4-btn--solid"
            href="https://wa.me/593978879775?text=Hola%20BootyFitness%20😊%20Quiero%20asesoría%20en%20tallas%20y%20cómo%20comprar."
            target="_blank" rel="noopener noreferrer">Pedir asesoría</a>

          <!--  <a class="buy4-btn buy4-btn--ghost" href="/tienda">Ir a la tienda</a> -->
        </div>

        <ul class="buy4-meta">
          <li>Envío nacional</li>
          <li>Apartado 50%</li>
          <li>Cambios 24/48h</li>
          <li>Tienda física</li>
        </ul>
      </div>

      <div class="buy4-grid">

        <!-- ✅ COL 1: POLÍTICAS -->
        <aside class="buy4-card buy4-card--policies">
          <div class="buy4-card-head">
            <span class="buy4-card-tag">📌 Políticas <small>· claras y simples</small></span>
          </div>

          <details class="buy4-acc" open>
            <summary>Sistema de apartado</summary>

            <ul class="buy4-list">
              <li><strong>50% para apartar</strong><span> Se reserva con la mitad del valor total.</span></li>
              <li><strong>1 abono = 15 días</strong><span> Si solo haces un abono, se mantiene 15 días.</span></li>
              <li><strong>Abonos constantes</strong><span> Cualquier monto extiende el tiempo (hasta el límite).</span></li>
            </ul>

            <div class="buy4-note">
              <strong>Reglas de apartado:</strong>
              <ul class="buy4-list" style="margin-top:.6rem;">
                <li><strong>Máximo 1 mes</strong><span> (no se puede extender más).</span></li>
                <li><strong>Sin abonos</strong><span> si no realizas abonos, se pierde el apartado automáticamente.</span></li>
                <li><strong>No reembolsable</strong><span> y <strong>sin cambios</strong> una vez hecho el apartado.</span></li>
              </ul>
            </div>
          </details>

          <details class="buy4-acc">
            <summary>Cambios y devoluciones</summary>

            <ul class="buy4-list">
              <li><strong>Dentro de la ciudad</strong><span> 24 horas.</span></li>
              <li><strong>Fuera de la ciudad</strong><span> 48 horas.</span></li>
              <li><strong>Estado perfecto</strong><span> prenda sin uso, en perfecto estado y condición.</span></li>
            </ul>

            <div class="buy4-note">
              En caso de devolución, los <strong>gastos operativos</strong> corren por parte del <strong>cliente</strong>.
            </div>
          </details>
        </aside>

        <!-- ✅ COL 2: RUTAS -->
        <div class="buy4-card buy4-card--routes">
          <div class="buy4-card-head">
            <span class="buy4-card-tag" id="buy4Tag" >🧭 Rutas de compra <small>· elige una</small></span>

            <div class="buy4-tabs" role="tablist" aria-label="Rutas de compra">
              <button id="tab-wa" class="buy3-tab is-active" type="button" data-panel="buy3-wa" aria-selected="true" aria-controls="buy3-wa">
                <i class="bi bi-whatsapp"></i>
              </button>
              <button id="tab-store" class="buy3-tab" type="button" data-panel="buy3-store" aria-selected="false" aria-controls="buy3-store">
                <i class="bi bi-shop"></i>
              </button>
            </div>
          </div>

          <!-- PANEL WA -->
          <div id="buy3-wa" class="buy3-panel" role="tabpanel" aria-labelledby="tab-wa">
            <h3 class="buy4-h">Comprar por WhatsApp</h3>
            <p class="buy4-p">Ideal si quieres asesoría de talla y cerrar el pedido en minutos.</p>

            <ol class="buy4-steps">
              <li>
                <div><strong>Escríbenos</strong><span>Envíanos la prenda + color + talla (o captura).</span></div>
              </li>
              <li>
                <div><strong>Asesoría + disponibilidad</strong><span>Te recomendamos talla según tu contextura y confirmamos stock.</span></div>
              </li>
              <li>
                <div><strong>Destino + costo de envío</strong><span>Nos indicas ciudad/sector (de dónde a dónde) y te cotizamos el envío.</span></div>
              </li>
              <li>
                <div><strong>Pago y confirmación</strong><span>Al contado, apartado 50% o tarjeta con PayPhone. Verificamos y confirmamos.</span></div>
              </li>
              <li>
                <div><strong>Empaque + guía</strong><span>Empacamos y te enviamos la guía. Pagos antes de las 3pm salen el mismo día; después, al día siguiente.</span></div>
              </li>
            </ol>

            <div class="buy4-note">Si estás entre tallas (S–M / M–L), escríbenos y te recomendamos la mejor opción.</div>

            <a class="buy4-btn buy4-btn--solid buy4-btn--full"
              href="https://wa.me/593978879775?text=Hola%20BootyFitness%20😊%20Quiero%20comprar%20por%20WhatsApp%20y%20necesito%20asesoría."
              target="_blank" rel="noopener noreferrer">Empezar por WhatsApp</a>
          </div>

          <!-- PANEL TIENDA -->
          <div id="buy3-store" class="buy3-panel" role="tabpanel" aria-labelledby="tab-store" hidden>
            <h3 class="buy4-h">Comprar en tienda</h3>
            <p class="buy4-p">Perfecto si quieres probar fit y tela en persona.</p>

            <ol class="buy4-steps">
              <li>
                <div><strong>Escríbenos antes</strong><span>Confirmamos stock/tallas.</span></div>
              </li>
              <li>
                <div><strong>En tienda puedes</strong><span>Probar fit/tela,Te ayudamos a confirmar tu talla.</span></div>
              </li>
              <li>
                <div><strong>Pagas y listo</strong><span>Te la llevas o te lo enviamos.</span></div>
              </li>
            </ol>

            <div class="store-mini">
              <div class="store-kv">
                <div class="store-ico">📍</div>
                <div>
                  <strong>Ubicación</strong>
                  <span><a id="storeMapsLink" href="#" target="_blank" rel="noopener noreferrer">Abrir en Google Maps</a></span>
                </div>
              </div>

              <div class="store-map">
                <div id="map-store"></div>
              </div>

              <div class="buy4-note">
                Tip: si vienes por una prenda específica, envíanos <strong>foto/captura</strong> + <strong>talla</strong> para confirmarte rápido.
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </section>

  <!-- ✅ NOTICIAS
  <section id="noticias" class="news-section">
    <h2 class="section-title-2">📰 Noticias fitness</h2>
    <p style="max-width: 720px; margin: 0 auto 1.25rem; font-family: var(--font-secondary); letter-spacing: 0; color: rgba(31,31,31,.75);">
      Tips y novedades para entrenar mejor, cuidarte y mantenerte motivada.
    </p>
    <div id="newsGrid" class="news-grid"></div>
  </section> -->

  <!-- ✅ FOOTER (Redes + Footer combinados) -->
  <footer class="site-footer" id="footer">
    <div class="footer-container">

      <!-- ✅ REDES MOVIDAS AQUÍ -->
      <div class="footer-social" aria-label="Redes sociales">
        <h2>Síguenos en nuestras redes sociales</h2>
        <p>
          Contenido real, nuevos ingresos, disponibilidad y tips.
          Escríbenos por WhatsApp si quieres asesoría rápida de talla y stock.
        </p>

        <div class="socials">
          <!-- Instagram -->
          <a href="#" target="_blank" rel="noopener" aria-label="Instagram">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path fill="currentColor" d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm10 2H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3zm-5 4a5 5 0 1 1 0 10a5 5 0 0 1 0-10zm0 2a3 3 0 1 0 0 6a3 3 0 0 0 0-6zm5.5-.9a1.1 1.1 0 1 1 0 2.2a1.1 1.1 0 0 1 0-2.2z" />
            </svg>
            Instagram
          </a>

          <!-- WhatsApp -->
          <a href="https://wa.me/593978879775?text=Hola%20BootyFitness%20😊%20Quiero%20asesoría%20de%20tallas%20y%20stock."
            target="_blank" rel="noopener" aria-label="WhatsApp">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path fill="currentColor" d="M20.5 3.5A11 11 0 0 0 3.9 18.9L3 22l3.2-.8A11 11 0 1 0 20.5 3.5zM12 21a9 9 0 0 1-4.6-1.3l-.3-.2-1.9.5.5-1.8-.2-.3A9 9 0 1 1 12 21zm5.2-6.6c-.3-.1-1.7-.9-2-.9s-.5-.1-.7.2-.8.9-1 1.1-.4.2-.7.1a7.4 7.4 0 0 1-2.2-1.3 8.3 8.3 0 0 1-1.5-1.9c-.2-.3 0-.5.1-.7l.5-.5c.2-.2.2-.3.3-.5a.6.6 0 0 0 0-.6c-.1-.1-.7-1.7-.9-2.3-.2-.6-.4-.5-.7-.5h-.6c-.2 0-.6.1-.9.4s-1.1 1-1.1 2.5 1.1 2.9 1.3 3.1a10.8 10.8 0 0 0 4.2 3.7c1.6.7 1.6.5 1.9.5s1.7-.7 2-1.3.3-1.2.2-1.3-.2-.2-.5-.3z" />
            </svg>
            WhatsApp
          </a>

          <!-- Google Maps -->
          <a href="https://www.google.com/maps/place/BootyFitness/@-0.2625677,-79.1645869,17z/data=!3m1!4b1!4m6!3m5!1s0x91d547a0a7feff63:0xe8be4aa828b9700d!8m2!3d-0.2625677!4d-79.162012!16s%2Fg%2F11yxk44z9w?entry=ttu"
            target="_blank" rel="noopener" aria-label="Google Maps (Tienda física)">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path fill="currentColor" d="M12 22s7-4.6 7-11a7 7 0 1 0-14 0c0 6.4 7 11 7 11zm0-9.2a2.8 2.8 0 1 1 0-5.6 2.8 2.8 0 0 1 0 5.6z" />
            </svg>
            Google Maps
          </a>

          <!-- TikTok -->
          <a href="#" target="_blank" rel="noopener" aria-label="TikTok">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path fill="currentColor" d="M14 3v10.2a3.8 3.8 0 1 1-3-3.7V7.1a6 6 0 1 0 5 5.9V8.7c1.2 1 2.7 1.6 4 1.7V7.7c-2-.2-3.6-1.8-4-3.7H14z" />
            </svg>
            TikTok
          </a>
        </div>
      </div>

      <div class="footer-top">
        <!-- Col 1: Marca + CTA -->
        <div class="footer-brand">
          <h3>BOOTYFITNESS</h3>
          <p>
            Ropa fitness para mujer — asesoría real de talla, stock confirmado y envío seguro a todo Ecuador.
            <br>Compra sin estrés: claro, rápido y con guía.
          </p>

          <div class="footer-cta">
            <a class="fbtn fbtn--wa"
              href="https://wa.me/593978879775?text=Hola%20BootyFitness%20😊%20Quiero%20asesor%C3%ADa%20de%20tallas%20y%20stock."
              target="_blank" rel="noopener">
              💬 WhatsApp
            </a>

            <a class="fbtn fbtn--solid"
              href="https://www.instagram.com/"
              target="_blank" rel="noopener">
              📸 Instagram
            </a>

            <a class="fbtn"
              href="https://www.google.com/maps/place/BootyFitness/@-0.2625677,-79.1645869,17z/data=!3m1!4b1!4m6!3m5!1s0x91d547a0a7feff63:0xe8be4aa828b9700d!8m2!3d-0.2625677!4d-79.162012!16s%2Fg%2F11yxk44z9w?entry=ttu"
              target="_blank" rel="noopener">
              📍 Google Maps
            </a>
          </div>

          <div class="badge-row" aria-label="Marcas que trabajamos">
            <span class="badge">⭐ Oner Active</span>
            <span class="badge">⭐ Gymshark</span>
            <span class="badge">⭐ Línea Americana</span>
            <span class="badge">⭐ Estilo Brasileño</span>
          </div>
        </div>

        <!-- Col 2: Navegación -->
        <div class="footer-col">
          <h4>Navegación</h4>
          <ul class="footer-links">
            <li><a href="#lineas">👚 Ropa</a></li>
            <li><a href="#nutricion">🚚 Envíos y cobertura</a></li>
            <li><a href="#comprar">🧭 Cómo comprar</a></li>
            <!-- ✅ CAMBIO: redes apunta al footer -->
            <li><a href="#footer">📲 Redes</a></li>
            <li><a href="{{ route('catalogo.descargar') }}">📥 Catálogo PDF</a></li>
          </ul>
        </div>

        <!-- Col 3: Tienda / Ayuda -->
        <div class="footer-col">
          <h4>Tienda física</h4>
          <ul class="footer-links">
            <li>
              <a href="https://www.google.com/maps/place/BootyFitness/@-0.2625677,-79.1645869,17z/data=!3m1!4b1!4m6!3m5!1s0x91d547a0a7feff63:0xe8be4aa828b9700d!8m2!3d-0.2625677!4d-79.162012!16s%2Fg%2F11yxk44z9w?entry=ttu"
                target="_blank" rel="noopener">
                📍 Cómo llegar (Maps)
              </a>
            </li>
            <li><a href="#comprar">🧭 Cómo comprar</a></li>
            <li><a href="#nutricion">🚚 Envíos y cobertura</a></li>
            <li><a href="/tienda">🛒 Ir a la tienda online</a></li>
            <li><a href="/login">🔐 Iniciar sesión</a></li>
          </ul>
        </div>
      </div>

      <div class="footer-bottom">
        <small>&copy; 2025 BOOTYFITNESS. Todos los derechos reservados.</small>

        <div class="footer-mini">
          <a href="#comprar">Políticas</a>
          <a href="#comprar">Cambios</a>
          <a href="#comprar">Apartado</a>
        </div>
      </div>

    </div>
  </footer>

  <!-- ✅ Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    window.addEventListener('load', () => {
      if (!window.L) return;

      const makePinIcon = (isGalapagos = false) => {
        const cls = isGalapagos ? "bf-pin bf-pin--galapagos" : "bf-pin";
        const html = `
          <div class="${cls}">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path class="pin-body" d="M12 22s8-4.8 8-12a8 8 0 10-16 0c0 7.2 8 12 8 12z"></path>
              <circle class="pin-hole" cx="12" cy="10" r="3.2"></circle>
            </svg>
          </div>
        `;
        return L.divIcon({
          className: "",
          html,
          iconSize: [26, 26],
          iconAnchor: [13, 26],
          tooltipAnchor: [0, -24]
        });
      };

      /* ================= ECUADOR MAP ================= */
      const ecuEl = document.getElementById('map-ecuador');
      if (ecuEl) {
        const map = L.map('map-ecuador', {
          scrollWheelZoom: false,
          zoomControl: true,
          dragging: true,
          tap: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        map.on('click', () => map.scrollWheelZoom.enable());
        map.on('mouseout', () => map.scrollWheelZoom.disable());

        const TIEMPO_PAQUETES = {
          label: "24–72 horas laborables"
        };
        const RUTAS_DOCUMENTOS = [24, 48, 72, 96, 120];

        const PROVINCIAS = [{
            provincia: "Azuay",
            capital: "Cuenca",
            lat: -2.9001,
            lng: -79.0059
          },
          {
            provincia: "Bolívar",
            capital: "Guaranda",
            lat: -1.5940,
            lng: -79.0000
          },
          {
            provincia: "Cañar",
            capital: "Azogues",
            lat: -2.7396,
            lng: -78.8486
          },
          {
            provincia: "Carchi",
            capital: "Tulcán",
            lat: 0.8119,
            lng: -77.7173
          },
          {
            provincia: "Chimborazo",
            capital: "Riobamba",
            lat: -1.6640,
            lng: -78.6543
          },
          {
            provincia: "Cotopaxi",
            capital: "Latacunga",
            lat: -0.9352,
            lng: -78.6155
          },
          {
            provincia: "El Oro",
            capital: "Machala",
            lat: -3.2581,
            lng: -79.9554
          },
          {
            provincia: "Esmeraldas",
            capital: "Esmeraldas",
            lat: 0.9592,
            lng: -79.6539
          },
          {
            provincia: "Galápagos",
            capital: "Puerto Baquerizo Moreno",
            lat: -0.9016,
            lng: -89.6133,
            isGalapagos: true
          },
          {
            provincia: "Guayas",
            capital: "Guayaquil",
            lat: -2.170998,
            lng: -79.922359
          },
          {
            provincia: "Imbabura",
            capital: "Ibarra",
            lat: 0.3517,
            lng: -78.1223
          },
          {
            provincia: "Loja",
            capital: "Loja",
            lat: -3.9931,
            lng: -79.2042
          },
          {
            provincia: "Los Ríos",
            capital: "Babahoyo",
            lat: -1.8010,
            lng: -79.5340
          },
          {
            provincia: "Manabí",
            capital: "Portoviejo",
            lat: -1.0546,
            lng: -80.4545
          },
          {
            provincia: "Morona Santiago",
            capital: "Macas",
            lat: -2.3097,
            lng: -78.1111
          },
          {
            provincia: "Napo",
            capital: "Tena",
            lat: -0.9938,
            lng: -77.8129
          },
          {
            provincia: "Orellana",
            capital: "Puerto Francisco de Orellana",
            lat: -0.4620,
            lng: -76.9868
          },
          {
            provincia: "Pastaza",
            capital: "Puyo",
            lat: -1.4927,
            lng: -77.9990
          },
          {
            provincia: "Pichincha",
            capital: "Quito",
            lat: -0.1807,
            lng: -78.4678
          },
          {
            provincia: "Santa Elena",
            capital: "Santa Elena",
            lat: -2.2267,
            lng: -80.8587
          },
          {
            provincia: "Santo Domingo de los Tsáchilas",
            capital: "Santo Domingo",
            lat: -0.2522,
            lng: -79.1754
          },
          {
            provincia: "Sucumbíos",
            capital: "Nueva Loja",
            lat: 0.0936,
            lng: -76.8890
          },
          {
            provincia: "Tungurahua",
            capital: "Ambato",
            lat: -1.2491,
            lng: -78.6167
          },
          {
            provincia: "Zamora Chinchipe",
            capital: "Zamora",
            lat: -4.0669,
            lng: -78.9560
          },
        ];

        const markers = [];

        PROVINCIAS.forEach(p => {
          const icon = makePinIcon(!!p.isGalapagos);
          const tooltipTxt = `${p.provincia} — ${p.capital} • ⏱ ${TIEMPO_PAQUETES.label}`;

          const popupHtml = `
            <strong>${p.provincia}</strong><br/>
            Capital: ${p.capital}<br/><br/>
            <strong>📦 Paquetes (estimado):</strong> ${TIEMPO_PAQUETES.label} <em>(según trayecto)</em>.<br/>
            <small><strong>📄 Documentos:</strong> ${RUTAS_DOCUMENTOS.join(" / ")} h <em>(según destino)</em>.</small>
          `;

          const marker = L.marker([p.lat, p.lng], {
              icon
            })
            .addTo(map)
            .bindTooltip(tooltipTxt, {
              direction: 'top',
              sticky: true,
              opacity: 1,
              className: 'bf-tooltip'
            })
            .bindPopup(popupHtml);

          markers.push(marker);
        });

        const group = L.featureGroup(markers).addTo(map);
        map.fitBounds(group.getBounds(), {
          padding: [28, 28],
          maxZoom: 7
        });
        setTimeout(() => map.invalidateSize(), 250);
        window.addEventListener('resize', () => map.invalidateSize());
      }
    });
  </script>

  <!-- ✅ Noticias (RSS) -->
  <script>
    const feeds = [
      "https://www.vitonica.com/rss",
      "https://www.sportlife.es/rss"
    ];

    const grid = document.getElementById("newsGrid");

    async function loadNews() {
      if (!grid) return;
      grid.innerHTML = "";

      for (let feed of feeds) {
        const url = `https://api.rss2json.com/v1/api.json?rss_url=${encodeURIComponent(feed)}`;
        const res = await fetch(url);
        const data = await res.json();

        (data.items || []).slice(0, 4).forEach(item => {
          const card = document.createElement("a");
          card.href = item.link;
          card.target = "_blank";
          card.rel = "noopener noreferrer";
          card.className = "news-card";

          card.innerHTML = `
            <img src="${item.thumbnail || 'https://picsum.photos/500/300'}" alt="">
            <div class="news-content">
              <h4>${item.title}</h4>
              <p>${(item.description || '').replace(/<[^>]*>/g,'').substring(0,90)}...</p>
            </div>
          `;

          grid.appendChild(card);
        });
      }
    }

    loadNews();
  </script>

  <!-- ✅ Tabs script + init mapa tienda cuando abras TIENDA -->
  <script>
    (function() {
      const root = document.querySelector('#comprar.buy4');
      if (!root) return;

      const tabs = Array.from(root.querySelectorAll('.buy3-tab'));
      const panels = Array.from(root.querySelectorAll('.buy3-panel'));
      const tabsWrap = root.querySelector('.buy4-tabs');

      // Tienda
      let storeMap = null;
      let storeMapReady = false;

      const initStoreMapIfNeeded = () => {
        const storeEl = document.getElementById('map-store');
        if (!storeEl || storeMapReady || !window.L) return;

        // ✅ Cambia aquí si necesitas otra ubicación
        const STORE = {
          lat: -0.2625677,
          lng: -79.162012
        };

        const mapsLink =
          "https://www.google.com/maps/place/BootyFitness/@-0.2625677,-79.1645869,17z/data=!3m1!4b1!4m6!3m5!1s0x91d547a0a7feff63:0xe8be4aa828b9700d!8m2!3d-0.2625677!4d-79.162012!16s%2Fg%2F11yxk44z9w?entry=ttu";

        const a1 = document.getElementById('storeMapsLink');
        const a2 = document.getElementById('storeMapsBtn');
        if (a1) a1.href = mapsLink;
        if (a2) a2.href = mapsLink;

        const makePinIcon = () => {
          const html = `
            <div class="bf-pin">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path class="pin-body" d="M12 22s8-4.8 8-12a8 8 0 10-16 0c0 7.2 8 12 8 12z"></path>
                <circle class="pin-hole" cx="12" cy="10" r="3.2"></circle>
              </svg>
            </div>
          `;
          return L.divIcon({
            className: "",
            html,
            iconSize: [26, 26],
            iconAnchor: [13, 26],
            tooltipAnchor: [0, -24]
          });
        };

        storeMap = L.map('map-store', {
          scrollWheelZoom: false,
          zoomControl: true,
          dragging: true,
          tap: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(storeMap);

        L.marker([STORE.lat, STORE.lng], {
            icon: makePinIcon()
          })
          .addTo(storeMap)
          .bindPopup(`
            <strong>BootyFitness</strong><br/>
            <a href="${mapsLink}" target="_blank" rel="noopener noreferrer">Abrir en Google Maps</a>
          `);

        storeMap.setView([STORE.lat, STORE.lng], 16);
        storeMapReady = true;

        setTimeout(() => storeMap.invalidateSize(), 200);
        window.addEventListener('resize', () => storeMap && storeMap.invalidateSize());
      };

      const setIndicator = (btn) => {
        if (!tabsWrap || !btn) return;
        const rect = tabsWrap.getBoundingClientRect();
        const brect = btn.getBoundingClientRect();
        tabsWrap.style.setProperty('--buy4-ind-left', (brect.left - rect.left) + 'px');
        tabsWrap.style.setProperty('--buy4-ind-width', brect.width + 'px');
      };

      const activate = (panelId) => {
        tabs.forEach(t => {
          const active = t.dataset.panel === panelId;
          t.classList.toggle('is-active', active);
          t.setAttribute('aria-selected', active ? 'true' : 'false');
          if (active) setIndicator(t);
        });

        panels.forEach(p => {
          p.hidden = (p.id !== panelId);
        });

        if (panelId === 'buy3-store') {
          initStoreMapIfNeeded();
          setTimeout(() => storeMap && storeMap.invalidateSize(), 200);
        }
      };

      tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.panel)));

      const initial = tabs.find(t => t.classList.contains('is-active'))?.dataset.panel || tabs[0]?.dataset.panel;
      activate(initial);

      window.addEventListener('resize', () => {
        const activeBtn = tabs.find(t => t.classList.contains('is-active'));
        if (activeBtn) setIndicator(activeBtn);
      });

      requestAnimationFrame(() => {
        const activeBtn = tabs.find(t => t.classList.contains('is-active'));
        if (activeBtn) setIndicator(activeBtn);
      });
    })();
  </script>

</body>

</html>
