<?php

return [

    /*
    | brapi.dev — ações, FIIs, ETFs e BDRs da B3.
    | Crie um token gratuito em https://brapi.dev/dashboard e coloque em BRAPI_TOKEN no .env.
    | Plano gratuito: 1 ticker por requisição, histórico de até 3 meses e ~30 min de atraso.
    | Em planos pagos, aumente BRAPI_TICKERS_POR_REQUISICAO (10 no Startup, 20 no Pro).
    */
    'brapi' => [
        'url' => env('BRAPI_URL', 'https://brapi.dev/api'),
        'token' => env('BRAPI_TOKEN'),
        'tickers_por_requisicao' => (int) env('BRAPI_TICKERS_POR_REQUISICAO', 1),
        // Período do histórico usado no gráfico do ativo e nos sinais do radar.
        'periodo_historico' => env('BRAPI_PERIODO_HISTORICO', '3mo'),
    ],

    // Classes da carteira com cotação automática (todas vêm da B3 pela brapi).
    'fontes' => [
        'acao' => 'brapi',
        'fii' => 'brapi',
        'etf' => 'brapi',
        'bdr' => 'brapi',
    ],

    /*
    | Lista do mercado (tela Buscar, rankings e radar). A cada sincronização são lidas
    | as primeiras páginas de cada tipo, em ordem de volume: com 2 páginas de 100,
    | entram os 200 ativos mais negociados de cada tipo.
    */
    'mercado' => [
        'tipos' => ['stock', 'fund', 'bdr'],
        'por_pagina' => (int) env('MERCADO_POR_PAGINA', 100),
        'paginas' => (int) env('MERCADO_PAGINAS', 2),
        // Quantos ativos (os mais negociados) recebem indicadores e nota do radar por rodada.
        'fundamentos_por_rodada' => (int) env('MERCADO_FUNDAMENTOS_POR_RODADA', 60),
        // Indicadores mais antigos que isso são refeitos na próxima rodada.
        'fundamentos_validade_horas' => 20,
    ],

    // Por quanto tempo uma cotação buscada é reaproveitada (evita gastar cota à toa).
    'cache_minutos' => (int) env('COTACOES_CACHE_MINUTOS', 10),

    // A página pede atualização sozinha quando a cotação mais antiga passa desse tempo.
    'idade_maxima_minutos' => (int) env('COTACOES_IDADE_MAXIMA_MINUTOS', 15),

    'timeout_segundos' => 10,
];
