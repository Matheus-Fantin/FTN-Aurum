<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MarketAsset;
use App\Services\CarteiraService;
use App\Services\Mercado\MercadoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function index(Request $request, CarteiraService $carteira, MercadoService $mercado): View
    {
        $user = $request->user();
        $ativos = $user->assets()->where('quantidade', '>', 0)->get();
        $carteira->fotografar($user);

        $minhas = $ativos->sortByDesc(fn (Asset $a) => abs($a->variacao_dia ?? 0))->take(4)->values();

        return view('painel', [
            'resumo' => $carteira->resumo($ativos, $user->metas()),
            'temAtivos' => $ativos->isNotEmpty(),
            'evolucao' => $user->snapshots()->where('data', '>=', now()->subDays(90)->toDateString())->orderBy('data')->pluck('valor')->all(),
            'atualizadoEm' => $ativos->max('cotacao_atualizada_em') ?? MarketAsset::max('atualizado_em'),
            'rankings' => $mercado->rankings(4),
            'radar' => $mercado->radar(3),
            'indices' => $mercado->indices(),
            'minhas' => $minhas,
            'proximoProvento' => $user->dividends()->where('data', '>=', now()->toDateString())->orderBy('data')->first(),
            'ultimoProvento' => $user->dividends()->where('data', '<', now()->toDateString())->orderByDesc('data')->first(),
            'alertas' => $user->priceAlerts()->whereNotNull('disparado_em')->where('visto', false)->latest('disparado_em')->limit(3)->get(),
            'mercadoVazio' => ! MarketAsset::exists(),
            'semToken' => blank(config('cotacoes.brapi.token')),
        ]);
    }
}
