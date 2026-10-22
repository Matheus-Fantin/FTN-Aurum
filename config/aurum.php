<?php

return [
    /*
    | Com false (padrão), novas contas só são criadas por convite de um administrador.
    | A primeira conta do sistema sempre pode ser criada e vira administradora.
    */
    'cadastro_aberto' => (bool) env('CADASTRO_ABERTO', false),

    /*
    | Classes de ativo da carteira. A ordem define a cor de cada classe (tokens --s1…--s4).
    */
    'classes' => [
        'acao' => ['nome' => 'Ações', 'cor' => 's1'],
        'fii' => ['nome' => 'FIIs', 'cor' => 's2'],
        'etf' => ['nome' => 'ETFs', 'cor' => 's3'],
        'bdr' => ['nome' => 'BDRs', 'cor' => 's4'],
    ],

    'metas_padrao' => ['acao' => 50, 'fii' => 30, 'etf' => 15, 'bdr' => 5],

    /*
    | Filtros prontos da tela Buscar. Cada um vira um bloco que liga/desliga;
    | os ligados se somam (o ativo precisa passar em todos).
    */
    'filtros' => [
        'dividendos' => ['nome' => 'Dividendos acima de 7%', 'icone' => 'coins'],
        'barata' => ['nome' => 'Baratas (P/L até 7)', 'icone' => 'tag'],
        'alta' => ['nome' => 'Em tendência de alta', 'icone' => 'growth'],
        'radar' => ['nome' => 'No radar (nota 6+)', 'icone' => 'target'],
        'grande' => ['nome' => 'Empresas grandes', 'icone' => 'layers'],
        'liquida' => ['nome' => 'Muito negociadas', 'icone' => 'swap'],
    ],

    'limites' => [
        'dividendos' => 7.0,        // DY mínimo (%)
        'pl_maximo' => 7.0,         // P/L máximo (acima de zero)
        'radar_minimo' => 6.0,      // nota mínima para "No radar"
        'valor_mercado' => 20e9,    // "Empresas grandes": acima de R$ 20 bilhões
        'volume' => 50e6,           // "Muito negociadas": acima de R$ 50 milhões/dia
    ],

    /*
    | Radar de possíveis altas: nota de 0 a 10 somando sinais técnicos e de preço.
    | Não é recomendação de investimento — é um filtro para estudar com calma.
    */
    'radar' => [
        'acima_media_curta' => ['peso' => 2.0, 'texto' => 'Preço acima da média de 20 dias'],
        'medias_cruzadas' => ['peso' => 2.0, 'texto' => 'Média de 20 dias acima da de 50'],
        'volume_forte' => ['peso' => 1.5, 'texto' => 'Volume acima da média'],
        'perto_da_maxima' => ['peso' => 1.5, 'texto' => 'Perto da máxima do período'],
        'mes_positivo' => ['peso' => 1.5, 'texto' => 'Subiu no último mês'],
        'pl_razoavel' => ['peso' => 1.5, 'texto' => 'P/L entre 0 e 15'],
    ],
];
