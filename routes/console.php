<?php

use Illuminate\Support\Facades\Schedule;

// Cotações da carteira durante o pregão (seg. a sex., 10h às 18h30 de Brasília).
Schedule::command('cotacoes:atualizar')
    ->everyFifteenMinutes()
    ->weekdays()
    ->between('10:00', '18:30')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

// Lista do mercado (altas, baixas, filtros) e alertas de preço durante o pregão.
Schedule::command('mercado:sincronizar')
    ->everyThirtyMinutes()
    ->weekdays()
    ->between('10:00', '18:30')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

// Depois do fechamento: indicadores e nota do radar dos mais negociados.
Schedule::command('mercado:sincronizar --fundamentos')
    ->weekdays()
    ->dailyAt('19:00')
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();

// Foto diária da carteira para o gráfico de evolução.
Schedule::command('carteira:fotografar')
    ->dailyAt('19:30')
    ->timezone('America/Sao_Paulo');
