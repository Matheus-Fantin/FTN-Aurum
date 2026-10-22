<?php

namespace App\Http\Controllers;

use App\Models\MarketAsset;
use App\Services\Cotacoes\BrapiCliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BuscarController extends Controller
{
    private const ORDENS = [
        'volume' => ['Mais negociadas', 'volume', 'desc'],
        'alta' => ['Maior alta hoje', 'variacao', 'desc'],
        'baixa' => ['Maior baixa hoje', 'variacao', 'asc'],
        'radar' => ['Nota do radar', 'radar_nota', 'desc'],
        'dy' => ['Maior dividend yield', 'dy', 'desc'],
        'pl' => ['Menor P/L', 'pl', 'asc'],
    ];

    public function index(Request $request): View
    {
        $termo = trim((string) $request->query('q', ''));
        $filtros = array_values(array_intersect((array) $request->query('f', []), array_keys(config('aurum.filtros'))));
        $classe = array_key_exists((string) $request->query('classe'), config('aurum.classes')) ? (string) $request->query('classe') : null;
        $ordem = array_key_exists((string) $request->query('ordem'), self::ORDENS) ? (string) $request->query('ordem') : 'volume';
        [, $coluna, $direcao] = self::ORDENS[$ordem];

        $resultados = MarketAsset::query()
            ->busca($termo)
            ->filtros($filtros)
            ->when($classe, fn ($q) => $q->where('classe', $classe))
            // Nulos por último em qualquer direção (ex.: ativos ainda sem P/L).
            ->orderByRaw("$coluna is null")
            ->when($ordem === 'pl', fn ($q) => $q->where('pl', '>', 0))
            ->orderBy($coluna, $direcao)
            ->paginate(24)
            ->withQueryString();

        // Mercado ainda não sincronizado: a busca por nome usa a brapi direto.
        $externos = [];
        if ($termo !== '' && $resultados->total() === 0 && ! $filtros) {
            $externos = Cache::remember('buscar:externo:'.md5(mb_strtolower($termo)), now()->addMinutes(30), fn () => app(BrapiCliente::class)->pesquisar($termo, 12));
        }

        return view('buscar', [
            'termo' => $termo,
            'filtros' => $filtros,
            'classe' => $classe,
            'ordem' => $ordem,
            'ordens' => array_map(fn ($o) => $o[0], self::ORDENS),
            'resultados' => $resultados,
            'externos' => $externos,
            'favoritos' => $request->user()->favorites()->pluck('ticker')->all(),
            'mercadoVazio' => ! MarketAsset::exists(),
        ]);
    }

    /** Sugestões enquanto digita: primeiro a lista do mercado no banco, depois a brapi. */
    public function sugestoes(Request $request, BrapiCliente $brapi): JsonResponse
    {
        $termo = trim((string) $request->query('q', ''));
        if (mb_strlen($termo) < 2 || mb_strlen($termo) > 40) {
            return response()->json([]);
        }

        $locais = MarketAsset::query()->busca($termo)->orderByDesc('volume')->limit(8)->get()
            ->map(fn (MarketAsset $a) => [
                'ticker' => $a->ticker,
                'nome' => $a->nome,
                'preco' => $a->preco,
                'variacao' => $a->variacao,
                'logo' => $a->logo,
                'tipo' => $a->nomeClasse(),
            ])->all();

        if (count($locais) >= 5) {
            return response()->json($locais);
        }

        $chave = 'buscar:sugestoes:'.md5(mb_strtolower($termo));
        $remotos = Cache::get($chave);
        if ($remotos === null) {
            $remotos = array_slice($brapi->pesquisar($termo, 10), 0, 10);
            if ($remotos !== []) {
                Cache::put($chave, $remotos, now()->addMinutes(30));
            }
        }

        $vistos = array_column($locais, 'ticker');
        foreach ($remotos as $a) {
            if (! in_array($a['ticker'], $vistos, true)) {
                $locais[] = ['ticker' => $a['ticker'], 'nome' => $a['nome'], 'preco' => $a['preco'], 'variacao' => $a['variacao'], 'logo' => $a['logo'], 'tipo' => null];
            }
        }

        return response()->json(array_slice($locais, 0, 10));
    }
}
