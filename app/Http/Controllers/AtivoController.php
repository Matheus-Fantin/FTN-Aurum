<?php

namespace App\Http\Controllers;

use App\Http\Requests\OperacaoRequest;
use App\Services\CarteiraService;
use App\Services\Mercado\MercadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AtivoController extends Controller
{
    public function show(Request $request, string $ticker, MercadoService $mercado): View
    {
        $ticker = $this->limpar($ticker);
        $user = $request->user();
        $info = $mercado->completo($ticker);
        $posicao = $user->assets()->where('ticker', $ticker)->first();

        return view('ativo', [
            'ticker' => $ticker,
            'info' => $info,
            'posicao' => $posicao && $posicao->quantidade > 0 ? $posicao : null,
            'operacoes' => $posicao ? $posicao->operations()->orderByDesc('data')->orderByDesc('id')->limit(20)->get() : collect(),
            'proventos' => $user->dividends()->where('ticker', $ticker)->orderByDesc('data')->limit(12)->get(),
            'favorito' => $user->favoritou($ticker),
            'alertas' => $user->priceAlerts()->where('ticker', $ticker)->whereNull('disparado_em')->orderBy('preco')->get(),
            'historico' => collect($info?->historico ?? [])->map(fn ($p) => ['data' => $p[0], 'fechamento' => (float) $p[1]])->values(),
        ]);
    }

    public function operacao(OperacaoRequest $request, string $ticker, CarteiraService $carteira): RedirectResponse
    {
        $ticker = $this->limpar($ticker);
        $dados = $request->validated();

        try {
            $carteira->registrar($request->user(), $ticker, $dados);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['quantidade' => $e->getMessage()])->withInput();
        }

        $verbo = $dados['tipo'] === 'compra' ? 'Compra' : 'Venda';

        return redirect()->route('ativo.show', $ticker)->with('sucesso', "$verbo de ".numero($dados['quantidade'], 8)." $ticker registrada.");
    }

    public function favorito(Request $request, string $ticker): RedirectResponse
    {
        $ticker = $this->limpar($ticker);
        $user = $request->user();
        $existente = $user->favorites()->where('ticker', $ticker)->first();

        if ($existente) {
            $existente->delete();
            $mensagem = "$ticker saiu dos favoritos.";
        } else {
            $user->favorites()->create(['ticker' => $ticker]);
            $mensagem = "$ticker foi para os favoritos.";
        }

        return back()->with('sucesso', $mensagem);
    }

    public function alerta(Request $request, string $ticker): RedirectResponse
    {
        $ticker = $this->limpar($ticker);
        $request->merge(['preco' => ler_numero((string) $request->input('preco', ''))]);
        $dados = $request->validateWithBag('alerta', [
            'condicao' => ['required', Rule::in(['acima', 'abaixo'])],
            'preco' => ['required', 'numeric', 'gt:0'],
        ], [], ['preco' => 'preço']);

        $request->user()->priceAlerts()->create(['ticker' => $ticker, ...$dados]);

        return back()->with('sucesso', "Alerta criado: avisaremos quando $ticker ".($dados['condicao'] === 'acima' ? 'subir até ' : 'cair até ').brl($dados['preco']).'.');
    }

    private function limpar(string $ticker): string
    {
        $ticker = mb_strtoupper(preg_replace('/[^A-Za-z0-9.]/', '', $ticker));
        abort_if($ticker === '' || mb_strlen($ticker) > 20, 404);

        return $ticker;
    }
}
