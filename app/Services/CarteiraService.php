<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MarketAsset;
use App\Models\Operation;
use App\Models\PriceAlert;
use App\Models\User;
use App\Services\Mercado\MercadoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CarteiraService
{
    /**
     * Totais da carteira: valor de mercado, custo, resultado, variação do dia e valor por classe.
     *
     * @param  Collection<int, Asset>  $ativos
     */
    public function resumo(Collection $ativos, array $metas = []): array
    {
        $porClasse = array_fill_keys(array_keys(config('aurum.classes')), 0.0);
        foreach ($ativos as $ativo) {
            $porClasse[$ativo->classe] = ($porClasse[$ativo->classe] ?? 0) + $ativo->valor();
        }

        $total = array_sum($porClasse);
        $custo = $ativos->sum(fn (Asset $a) => $a->custo());
        $hoje = $ativos->sum(fn (Asset $a) => $a->variacaoDiaValor());
        $ontem = $total - $hoje;

        $classes = [];
        foreach (config('aurum.classes') as $id => $info) {
            $atual = $total > 0 ? $porClasse[$id] / $total * 100 : 0;
            $meta = (float) ($metas[$id] ?? 0);
            $classes[$id] = [
                'id' => $id,
                'nome' => $info['nome'],
                'cor' => $info['cor'],
                'valor' => $porClasse[$id],
                'atual' => $atual,
                'meta' => $meta,
                // Mais de 3 pontos abaixo da meta: vale aportar nessa classe.
                'situacao' => $total <= 0 ? 'vazia' : ($atual < $meta - 3 ? 'abaixo' : ($atual > $meta + 3 ? 'acima' : 'ok')),
            ];
        }

        return [
            'total' => $total,
            'custo' => $custo,
            'resultado' => $total - $custo,
            'resultado_pct' => $custo > 0 ? ($total / $custo - 1) * 100 : 0,
            'hoje' => $hoje,
            'hoje_pct' => $ontem > 0 ? $hoje / $ontem * 100 : 0,
            'classes' => $classes,
        ];
    }

    /** Quanto foi investido em compras neste mês (para a barra "Aporte do mês"). */
    public function aporteDoMes(User $user): float
    {
        return (float) $user->operations()->where('tipo', 'compra')
            ->where('data', '>=', now()->startOfMonth()->toDateString())
            ->get()->sum(fn (Operation $o) => $o->total() + $o->taxas);
    }

    /**
     * Registra uma compra ou venda e atualiza a posição do ativo.
     * Compra: o preço médio vira a média ponderada (taxas entram no custo).
     * Venda: a quantidade cai, o preço médio se mantém e o lucro realizado fica na operação.
     */
    public function aplicarOperacao(Asset $ativo, string $tipo, string $data, float $quantidade, float $preco, float $taxas = 0): Operation
    {
        if ($tipo === 'venda' && $quantidade > $ativo->quantidade + 1e-9) {
            throw new \InvalidArgumentException('Você tem '.numero($ativo->quantidade, 8)." de {$ativo->ticker}; não dá para vender mais que isso.");
        }

        $resultado = null;
        if ($tipo === 'compra') {
            $novaQtd = $ativo->quantidade + $quantidade;
            $ativo->preco_medio = $novaQtd > 0 ? ($ativo->quantidade * $ativo->preco_medio + $quantidade * $preco + $taxas) / $novaQtd : 0;
            $ativo->quantidade = $novaQtd;
        } else {
            $resultado = round(($preco - $ativo->preco_medio) * $quantidade - $taxas, 2);
            $ativo->quantidade = max(0, $ativo->quantidade - $quantidade);
        }
        if ($ativo->cotacao <= 0) {
            $ativo->cotacao = $preco;
        }
        $ativo->save();

        return $ativo->user->operations()->create([
            'asset_id' => $ativo->id,
            'tipo' => $tipo,
            'data' => $data,
            'quantidade' => $quantidade,
            'preco' => $preco,
            'taxas' => $taxas,
            'resultado' => $resultado,
        ]);
    }

    /**
     * Registra uma operação vinda de um formulário: cria a posição se ainda não existir.
     *
     * @param  array{tipo: string, data: string, quantidade: float|string, preco: float|string, taxas?: float|string|null, classe?: string|null}  $dados
     *
     * @throws \InvalidArgumentException quando a venda é maior que a posição
     */
    public function registrar(User $user, string $ticker, array $dados): Operation
    {
        return DB::transaction(function () use ($user, $ticker, $dados) {
            $ativo = $user->assets()->where('ticker', $ticker)->first();
            if (! $ativo) {
                if ($dados['tipo'] === 'venda') {
                    throw new \InvalidArgumentException("Você não tem {$ticker} na carteira para vender.");
                }
                $mercado = MarketAsset::firstWhere('ticker', $ticker);
                $ativo = $user->assets()->create([
                    'ticker' => $ticker,
                    'classe' => $dados['classe'] ?? $mercado?->classe ?? MercadoService::classe($ticker, null),
                    'quantidade' => 0,
                    'preco_medio' => 0,
                    'cotacao' => $mercado?->preco ?? (float) $dados['preco'],
                    'variacao_dia' => $mercado?->variacao,
                    'cotacao_atualizada_em' => $mercado?->atualizado_em,
                ]);
            }

            return $this->aplicarOperacao($ativo, $dados['tipo'], $dados['data'], (float) $dados['quantidade'], (float) $dados['preco'], (float) ($dados['taxas'] ?? 0));
        });
    }

    /**
     * Desfaz uma operação refazendo a posição a partir das operações que sobram
     * (assim o preço médio volta a ser o que era).
     */
    public function desfazer(Operation $operacao): void
    {
        DB::transaction(function () use ($operacao) {
            $ativo = $operacao->asset;
            $operacao->delete();

            $ativo->quantidade = 0;
            $ativo->preco_medio = 0;
            foreach ($ativo->operations()->orderBy('data')->orderBy('id')->get() as $op) {
                if ($op->tipo === 'compra') {
                    $nova = $ativo->quantidade + $op->quantidade;
                    $ativo->preco_medio = $nova > 0 ? ($ativo->quantidade * $ativo->preco_medio + $op->quantidade * $op->preco + $op->taxas) / $nova : 0;
                    $ativo->quantidade = $nova;
                } else {
                    $op->update(['resultado' => round(($op->preco - $ativo->preco_medio) * $op->quantidade - $op->taxas, 2)]);
                    $ativo->quantidade = max(0, $ativo->quantidade - $op->quantidade);
                }
            }
            $ativo->save();
        });
    }

    /** Guarda a foto de hoje do valor da carteira (uma por dia; a última do dia vale). */
    public function fotografar(User $user): void
    {
        $ativos = $user->assets()->where('quantidade', '>', 0)->get();
        if ($ativos->isEmpty()) {
            return;
        }

        $valores = ['valor' => round($ativos->sum(fn (Asset $a) => $a->valor()), 2), 'custo' => round($ativos->sum(fn (Asset $a) => $a->custo()), 2)];
        // whereDate: a coluna date pode vir gravada com horário (SQLite).
        $foto = $user->snapshots()->whereDate('data', now()->toDateString())->first();
        $foto ? $foto->update($valores) : $user->snapshots()->create(['data' => now()->toDateString(), ...$valores]);
    }

    /**
     * Confere os alertas de preço ainda não disparados com o preço mais recente
     * (lista do mercado ou cotação da carteira). Retorna quantos dispararam.
     */
    public function verificarAlertas(): int
    {
        $alertas = PriceAlert::whereNull('disparado_em')->get();
        if ($alertas->isEmpty()) {
            return 0;
        }

        $precos = MarketAsset::whereIn('ticker', $alertas->pluck('ticker')->unique())->pluck('preco', 'ticker');
        $disparados = 0;
        foreach ($alertas as $alerta) {
            $preco = $precos[$alerta->ticker]
                ?? Asset::where('ticker', $alerta->ticker)->whereNotNull('cotacao_atualizada_em')->value('cotacao');
            if ($preco !== null && $alerta->atingidoPor((float) $preco)) {
                $alerta->update(['disparado_em' => now(), 'preco_disparo' => $preco, 'visto' => false]);
                $disparados++;
            }
        }

        return $disparados;
    }
}
