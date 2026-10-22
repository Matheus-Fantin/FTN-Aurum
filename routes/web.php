<?php

use App\Http\Controllers\Admin\InviteController;
use App\Http\Controllers\AtivoController;
use App\Http\Controllers\BuscarController;
use App\Http\Controllers\CarteiraController;
use App\Http\Controllers\CotacaoController;
use App\Http\Controllers\FavoritosController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProventosController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel');

Route::middleware('auth')->group(function () {
    Route::get('/painel', [PainelController::class, 'index'])->name('dashboard');

    Route::get('/buscar', [BuscarController::class, 'index'])->name('buscar');
    Route::get('/buscar/sugestoes', [BuscarController::class, 'sugestoes'])->middleware('throttle:90,1')->name('buscar.sugestoes');

    Route::get('/ativo/{ticker}', [AtivoController::class, 'show'])->name('ativo.show');
    Route::post('/ativo/{ticker}/operacao', [AtivoController::class, 'operacao'])->name('ativo.operacao');
    Route::post('/ativo/{ticker}/favorito', [AtivoController::class, 'favorito'])->name('ativo.favorito');
    Route::post('/ativo/{ticker}/alerta', [AtivoController::class, 'alerta'])->name('ativo.alerta');

    Route::get('/carteira', [CarteiraController::class, 'index'])->name('carteira');
    Route::post('/carteira/operacao', [CarteiraController::class, 'operacao'])->name('carteira.operacao');
    Route::put('/carteira/metas', [CarteiraController::class, 'metas'])->name('carteira.metas');
    Route::delete('/carteira/operacoes/{operation}', [CarteiraController::class, 'desfazer'])->name('carteira.desfazer');

    Route::get('/favoritos', [FavoritosController::class, 'index'])->name('favoritos');
    Route::delete('/alertas/{alert}', [FavoritosController::class, 'excluirAlerta'])->name('alertas.destroy');

    Route::get('/proventos', [ProventosController::class, 'index'])->name('proventos');
    Route::post('/proventos', [ProventosController::class, 'store'])->name('proventos.store');
    Route::delete('/proventos/{dividend}', [ProventosController::class, 'destroy'])->name('proventos.destroy');

    Route::post('/cotacoes/atualizar', [CotacaoController::class, 'atualizar'])
        ->middleware('throttle:6,1')
        ->name('cotacoes.atualizar');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/convites', [InviteController::class, 'index'])->name('convites.index');
        Route::post('/convites', [InviteController::class, 'store'])->name('convites.store');
        Route::delete('/convites/{invite}', [InviteController::class, 'destroy'])->name('convites.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
