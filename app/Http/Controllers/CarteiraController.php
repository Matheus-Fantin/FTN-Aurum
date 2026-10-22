<?php

namespace App\Http\Controllers;

use App\Http\Requests\MetasRequest;
use App\Http\Requests\OperacaoRequest;
use App\Models\Asset;
use App\Models\Operation;
use App\Services\CarteiraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CarteiraController extends Controller
{
    public function index(Request $request, CarteiraService $carteira): View
    {
        $user = $request->user();
        $ativos = $user->assets()->where('quantidade', '>', 0)->get()->sortByDesc(fn (Asset $a) => $a->valor())->values();
        $resumo = $carteira->resumo($ativos, $user->metas());

        return view('carteira', [
            'ativos' => $ativos,
            'resumo' => $resumo,
            'metas' => $user->metas(),
            'metaAporte' => $user->meta_aporte_mensal,
            'aporteMes' => $carteira->aporteDoMes($user),
            'diasRestantes' => (int) now()->diffInDays(now()->endOfMonth()),
            'operacoes' => $user->operations()->with('asset')->orderByDesc('data')->orderByDesc('id')->limit(15)->get(),
            'lucroRealizadoAno' => (float) $user->operations()->where('tipo', 'venda')->where('data', '>=', now()->startOfYear()->toDateString())->sum('resultado'),
            'evolucao' => $user->snapshots()->orderBy('data')->get(['data', 'valor', 'custo'])
                ->map(fn ($s) => ['data' => $s->data->toDateString(), 'valor' => $s->valor, 'custo' => $s->custo])->values(),
        ]);
    }

    public function operacao(OperacaoRequest $request, CarteiraService $carteira): RedirectResponse
    {
        $dados = $request->validated();

        try {
            $carteira->registrar($request->user(), $dados['ticker'], $dados);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['quantidade' => $e->getMessage()])->withInput()->with('abrirOperacao', true);
        }

        $verbo = $dados['tipo'] === 'compra' ? 'Compra' : 'Venda';

        return redirect()->route('carteira')->with('sucesso', "$verbo de ".numero($dados['quantidade'], 8)." {$dados['ticker']} registrada.");
    }

    public function desfazer(Request $request, Operation $operation, CarteiraService $carteira): RedirectResponse
    {
        abort_unless($operation->user_id === $request->user()->id, 404);
        $ticker = $operation->asset->ticker;
        $carteira->desfazer($operation);

        return back()->with('sucesso', "Operação de $ticker desfeita.");
    }

    public function metas(MetasRequest $request): RedirectResponse
    {
        $user = $request->user();
        foreach ($request->validated('metas') as $classe => $percentual) {
            $user->allocationTargets()->updateOrCreate(['classe' => $classe], ['percentual' => $percentual]);
        }
        $user->forceFill(['meta_aporte_mensal' => (float) ($request->validated('meta_aporte_mensal') ?? 0)])->save();

        return back()->with('sucesso', 'Metas salvas.');
    }
}
