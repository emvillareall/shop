<?php

return [
    'storefront_open' => (bool) env('SECURITY_STOREFRONT_OPEN', false),

    'features' => [
        // Compatibilidad legacy controlada
        'legacy_pdf_bridge' => (bool) env('SECURITY_LEGACY_PDF_BRIDGE', false),
        'legacy_event_subscribe' => (bool) env('SECURITY_LEGACY_EVENT_SUBSCRIBE', false),
        'legacy_cliente_busqueda' => (bool) env('SECURITY_LEGACY_CLIENTE_BUSQUEDA', false),
        'legacy_id_colores_public' => (bool) env('SECURITY_LEGACY_ID_COLORES_PUBLIC', false),
        'legacy_store_navigation' => (bool) env('SECURITY_LEGACY_STORE_NAVIGATION', false),
        'legacy_catalogo_general_public' => (bool) env('SECURITY_LEGACY_CATALOGO_GENERAL_PUBLIC', false),
        'legacy_catalogo_pdf_public' => (bool) env('SECURITY_LEGACY_CATALOGO_PDF_PUBLIC', false),
        'legacy_cart_bridge' => (bool) env('SECURITY_LEGACY_CART_BRIDGE', false),
    ],
];
