<?php

namespace App\Console\Commands;

use App\Services\CarteiraService;
use App\Services\Mercado\MercadoService;
use Illuminate\Console\Command;

class SincronizarMercado extends Command
{
    protected $signature = 'mercado:sincronizar
                            {--fundamentos : Também refaz indicadores e a nota do radar}
                            {--limite= : Quantos ativos recebem indicadores nesta rodada}
                            {--so-fundamentos : Pula a lista e só refaz indicadores}';

    protected $description = 'Atualiza a lista do mercado (preços, altas e baixas) pela brapi e confere os alertas de preço';

    public function handle(MercadoService $mercado, CarteiraService $carteira): int
    {
        if (! $this->option('so-fundamentos')) {
            $this->info('Lista do mercado: '.$mercado->sincronizarLista().' ativos.');
        }

        if ($this->option('fundamentos') || $this->option('so-fundamentos')) {
            $limite = $this->option('limite') !== null ? (int) $this->option('limite') : null;
            $this->info('Indicadores e radar: '.$mercado->atualizarFundamentos($limite).' ativos.');
        }

        $disparados = $carteira->verificarAlertas();
        if ($disparados > 0) {
            $this->info("Alertas disparados: $disparados.");
        }

        return self::SUCCESS;
    }
}
