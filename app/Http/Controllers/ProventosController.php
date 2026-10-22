<?php

namespace App\Http\Controllers;

use App\Models\Dividend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProventosController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $hoje = now()->toDateString();
        $inicio = now()->subMonths(11)->startOfMonth();
        $recebidos = $user->dividends()->where('data', '>=', $inicio->toDateString())->where('data', '<=', $hoje)->get();

        $porMes = [];
        for ($m = $inicio->copy(); $m->lte(now()); $m->addMonth()) {
            $porMes[$m->format('Y-m')] = 0.0;
        }
        foreach ($recebidos as $d) {
            $porMes[$d->data->format('Y-m')] = ($porMes[$d->data->format('Y-m')] ?? 0) + $d->valor;
        }

        $custo = $user->assets()->where('quantidade', '>', 0)->get()->sum(fn ($a) => $a->custo());
        $total12 = $recebidos->sum('valor');

        return view('proventos', [
            'aReceber' => $user->dividends()->where('data', '>', $hoje)->orderBy('data')->get(),
            'proventos' => $user->dividends()->where('data', '<=', $hoje)->orderByDesc('data')->paginate(20),
            'total12' => $total12,
            'mediaMensal' => $total12 / 12,
            'yieldOnCost' => $custo > 0 ? $total12 / $custo * 100 : 0,
            'grafico' => collect($porMes)->map(fn ($v, $mes) => ['mes' => $mes, 'valor' => round($v, 2)])->values(),
            'tickers' => $user->assets()->orderBy('ticker')->pluck('ticker'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'valor' => ler_numero((string) $request->input('valor', '')),
            'ticker' => mb_strtoupper(preg_replace('/[^A-Za-z0-9.]/', '', (string) $request->input('ticker'))),
        ]);
        $dados = $request->validate([
            'ticker' => ['required', 'string', 'max:20'],
            'tipo' => ['required', Rule::in(array_keys(Dividend::TIPOS))],
            'data' => ['required', 'date'],
            'valor' => ['required', 'numeric', 'gt:0'],
        ], [], ['ticker' => 'ativo']);

        $user = $request->user();
        $user->dividends()->create([...$dados, 'asset_id' => $user->assets()->where('ticker', $dados['ticker'])->value('id')]);

        return back()->with('sucesso', 'Provento de '.$dados['ticker'].' registrado.');
    }

    public function destroy(Request $request, Dividend $dividend): RedirectResponse
    {
        abort_unless($dividend->user_id === $request->user()->id, 404);
        $dividend->delete();

        return back()->with('sucesso', 'Provento excluído.');
    }
}
