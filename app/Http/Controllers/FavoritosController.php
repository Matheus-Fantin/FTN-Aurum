<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\MarketAsset;
use App\Models\PriceAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoritosController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $favoritos = $user->favorites()->orderBy('ticker')->get();
        $mercado = MarketAsset::whereIn('ticker', $favoritos->pluck('ticker'))->get()->keyBy('ticker');
        $alertas = $user->priceAlerts()->orderByRaw('disparado_em is null')->orderByDesc('disparado_em')->orderBy('ticker')->get();
        $novos = $alertas->filter(fn (PriceAlert $a) => $a->disparado_em && ! $a->visto)->pluck('id')->all();

        // Abrir a página conta como "visto": o contador do sino zera.
        if ($novos) {
            $user->priceAlerts()->whereIn('id', $novos)->update(['visto' => true]);
        }

        return view('favoritos', [
            'favoritos' => $favoritos->map(fn (Favorite $f) => ['ticker' => $f->ticker, 'info' => $mercado[$f->ticker] ?? null]),
            'alertas' => $alertas,
            'novos' => $novos,
            'precos' => MarketAsset::whereIn('ticker', $alertas->pluck('ticker'))->pluck('preco', 'ticker'),
        ]);
    }

    public function excluirAlerta(Request $request, PriceAlert $alert): RedirectResponse
    {
        abort_unless($alert->user_id === $request->user()->id, 404);
        $alert->delete();

        return back()->with('sucesso', 'Alerta excluído.');
    }
}
