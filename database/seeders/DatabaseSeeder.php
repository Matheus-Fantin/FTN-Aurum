<?php

namespace Database\Seeders;

use App\Models\MarketAsset;
use App\Models\User;
use App\Services\CarteiraService;
use App\Services\Mercado\Radar;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Conta de demonstração com carteira, favoritos, alertas e um mercado de exemplo
     * (para ver as telas sem token da brapi). Os preços são ilustrativos.
     * Login: demo@ftnaurum.test / senha: password
     */
    public function run(CarteiraService $carteira): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@ftnaurum.test'],
            ['name' => 'Conta de demonstração', 'password' => 'password', 'email_verified_at' => now(), 'meta_aporte_mensal' => 1000],
        );
        $user->forceFill(['is_admin' => true])->save();
        foreach (['dividends', 'operations', 'assets', 'favorites', 'priceAlerts', 'snapshots', 'allocationTargets'] as $relacao) {
            $user->$relacao()->delete();
        }

        foreach (config('aurum.metas_padrao') as $classe => $percentual) {
            $user->allocationTargets()->create(['classe' => $classe, 'percentual' => $percentual]);
        }

        // Mercado de exemplo: [ticker, nome, classe, setor, preço, variação, volume (R$), valor de mercado, P/L, DY]
        $mercado = [
            ['PETR4', 'Petrobras PN', 'acao', 'Petróleo e gás', 37.12, 0.92, 2.1e9, 480e9, 5.1, 8.7],
            ['VALE3', 'Vale ON', 'acao', 'Mineração', 58.44, -3.76, 1.8e9, 260e9, 6.3, 7.7],
            ['ITUB4', 'Itaú Unibanco PN', 'acao', 'Bancos', 36.90, 1.98, 1.1e9, 340e9, 8.6, 6.4],
            ['BBAS3', 'Banco do Brasil ON', 'acao', 'Bancos', 27.35, 2.65, 0.9e9, 155e9, 4.2, 9.1],
            ['WEGE3', 'WEG ON', 'acao', 'Bens industriais', 54.10, 3.12, 0.6e9, 227e9, 28.4, 1.6],
            ['PRIO3', 'PRIO ON', 'acao', 'Petróleo e gás', 46.82, 5.41, 0.7e9, 41e9, 6.9, 0.0],
            ['MGLU3', 'Magazine Luiza ON', 'acao', 'Varejo', 9.74, 4.87, 0.3e9, 7e9, null, 0.0],
            ['CSNA3', 'CSN ON', 'acao', 'Siderurgia', 11.02, -3.10, 0.2e9, 15e9, null, 4.1],
            ['GGBR4', 'Gerdau PN', 'acao', 'Siderurgia', 17.85, -2.44, 0.3e9, 36e9, 6.8, 5.2],
            ['ABEV3', 'Ambev ON', 'acao', 'Bebidas', 12.61, -1.62, 0.5e9, 199e9, 14.1, 5.8],
            ['TAEE11', 'Taesa Unit', 'acao', 'Energia elétrica', 35.80, 0.35, 0.1e9, 12e9, 8.8, 7.9],
            ['ITSA4', 'Itaúsa PN', 'acao', 'Holdings', 10.45, 1.10, 0.3e9, 105e9, 7.4, 7.2],
            ['HGLG11', 'CSHG Logística', 'fii', 'Logística', 160.50, 0.20, 0.02e9, 4.8e9, null, 8.2],
            ['MXRF11', 'Maxi Renda', 'fii', 'Papel', 9.92, -0.30, 0.03e9, 3.1e9, null, 12.1],
            ['BOVA11', 'iShares Ibovespa', 'etf', 'Índice', 128.00, 0.58, 0.5e9, 15e9, null, null],
            ['IVVB11', 'iShares S&P 500', 'etf', 'Índice exterior', 352.40, 0.40, 0.2e9, 9e9, null, null],
            ['AAPL34', 'Apple DRN', 'bdr', 'Tecnologia', 68.30, -0.85, 0.05e9, 3.4e12, 31.0, 0.5],
        ];
        foreach ($mercado as $i => [$ticker, $nome, $classe, $setor, $preco, $var, $volume, $vm, $pl, $dy]) {
            // Histórico sintético: tendência suave na direção da variação do dia.
            $historico = [];
            for ($d = 60; $d >= 1; $d--) {
                $fator = 1 - ($var / 100) * ($d / 20) + sin($d / 4 + $i) * 0.012;
                $historico[] = [now()->subWeekdays($d)->toDateString(), round($preco * $fator, 2)];
            }
            $fech = array_column($historico, 1);
            $media20 = array_sum(array_slice($fech, -20)) / 20;
            $media50 = array_sum(array_slice($fech, -50)) / 50;
            $ind = [
                'preco' => $preco, 'media_20' => $media20, 'media_50' => $media50,
                'maxima_periodo' => max([...$fech, $preco]), 'minima_periodo' => min([...$fech, $preco]),
                'volume_medio' => $volume / (1 + $var / 20), 'volume_dia' => $volume,
                'variacao_mes' => ($preco / $fech[count($fech) - 22] - 1) * 100, 'pl' => $pl,
            ];
            $radar = Radar::pontuar($ind);
            MarketAsset::updateOrCreate(['ticker' => $ticker], [
                'nome' => $nome, 'classe' => $classe, 'setor' => $setor, 'preco' => $preco, 'variacao' => $var,
                'volume' => $volume, 'valor_mercado' => $vm, 'pl' => $pl, 'dy' => $dy,
                'media_20' => $media20, 'media_50' => $media50, 'maxima_periodo' => $ind['maxima_periodo'], 'minima_periodo' => $ind['minima_periodo'],
                'volume_medio' => $ind['volume_medio'], 'variacao_mes' => $ind['variacao_mes'],
                'tendencia_alta' => $preco > $media20 && $media20 > $media50,
                'radar_nota' => $radar['nota'], 'radar_sinais' => $radar['sinais'], 'historico' => $historico,
                'atualizado_em' => now(), 'fundamentos_em' => now(),
            ]);
        }

        // Carteira: [ticker, quantidade, preço médio, dias atrás]
        foreach ([['ITUB4', 200, 29.10, 300], ['WEGE3', 110, 41.80, 250], ['VALE3', 90, 66.20, 200], ['HGLG11', 40, 162.30, 180], ['MXRF11', 380, 10.05, 120], ['BOVA11', 35, 118.40, 90], ['AAPL34', 40, 61.20, 40]] as [$ticker, $qtd, $pm, $dias]) {
            $carteira->registrar($user, $ticker, ['tipo' => 'compra', 'data' => now()->subDays($dias)->toDateString(), 'quantidade' => $qtd, 'preco' => $pm]);
            $m = MarketAsset::firstWhere('ticker', $ticker);
            $user->assets()->where('ticker', $ticker)->update(['cotacao' => $m->preco, 'variacao_dia' => $m->variacao, 'cotacao_atualizada_em' => now()]);
        }
        $carteira->registrar($user, 'ITUB4', ['tipo' => 'compra', 'data' => now()->startOfMonth()->toDateString(), 'quantidade' => 10, 'preco' => 35.40]);
        $carteira->registrar($user, 'BOVA11', ['tipo' => 'compra', 'data' => now()->toDateString(), 'quantidade' => 3, 'preco' => 127.50]);

        // Evolução dos últimos 60 dias (ilustrativa).
        $total = $user->assets()->get()->sum(fn ($a) => $a->valor());
        $custo = $user->assets()->get()->sum(fn ($a) => $a->custo());
        for ($d = 60; $d >= 0; $d--) {
            $user->snapshots()->create(['data' => now()->subDays($d)->toDateString(), 'valor' => round($total * (0.93 + (60 - $d) / 60 * 0.07) + sin($d) * 250, 2), 'custo' => round($custo, 2)]);
        }

        foreach (['WEGE3', 'BBAS3', 'PETR4', 'HGLG11'] as $ticker) {
            $user->favorites()->create(['ticker' => $ticker]);
        }
        $user->priceAlerts()->create(['ticker' => 'PETR4', 'condicao' => 'abaixo', 'preco' => 35]);
        $user->priceAlerts()->create(['ticker' => 'PRIO3', 'condicao' => 'acima', 'preco' => 45, 'disparado_em' => now()->subHour(), 'preco_disparo' => 46.82]);

        foreach ([['ITUB4', 'jcp', -40, 38.20], ['HGLG11', 'rendimento', -25, 44.00], ['MXRF11', 'rendimento', -14, 38.00], ['ITUB4', 'jcp', 10, 42.60], ['MXRF11', 'rendimento', 23, 38.00]] as [$ticker, $tipo, $dias, $valor]) {
            $user->dividends()->create(['ticker' => $ticker, 'tipo' => $tipo, 'data' => now()->addDays($dias)->toDateString(), 'valor' => $valor]);
        }
    }
}
