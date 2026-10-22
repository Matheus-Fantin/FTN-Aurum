<x-app-layout titulo="Proventos">
    <div class="page-head">
        <div>
            <div class="eyebrow">Dividendos, JCP e rendimentos</div>
            <h1>Proventos</h1>
        </div>
        <x-olho class="btn-olho" />
    </div>

    <div class="grid g3">
        <div class="bloco kpi"><span class="label">Recebido em 12 meses</span><span class="value num"><x-valor :v="brl($total12)" /></span></div>
        <div class="bloco kpi"><span class="label">Média por mês</span><span class="value num"><x-valor :v="brl($mediaMensal)" /></span></div>
        <div class="bloco kpi"><span class="label">Yield on cost</span><span class="value">{{ pct($yieldOnCost) }}</span><span class="sub">proventos ÷ quanto você pagou</span></div>
    </div>

    @if (collect($grafico)->sum('valor') > 0)
        <section class="bloco" aria-labelledby="t-graf">
            <h2 id="t-graf">Por mês</h2>
            <div class="chart-box short" data-sensivel><canvas data-grafico="dividendos" data-dados='@json($grafico)' role="img" aria-label="Proventos recebidos por mês"></canvas></div>
        </section>
    @endif

    <div class="grid g-side">
        <section class="bloco" aria-labelledby="t-novo">
            <h2 id="t-novo">Registrar provento</h2>
            <form method="POST" action="{{ route('proventos.store') }}" class="stack" style="gap:12px">
                @csrf
                <div class="field">
                    <label for="p-ticker">Ativo</label>
                    <input class="in @error('ticker') invalido @enderror" id="p-ticker" name="ticker" list="meus-tickers" value="{{ old('ticker') }}" placeholder="ITUB4" autocapitalize="characters" required>
                    <datalist id="meus-tickers">@foreach ($tickers as $t)<option value="{{ $t }}">@endforeach</datalist>
                    @error('ticker')<span class="erro">{{ $message }}</span>@enderror
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label for="p-tipo">Tipo</label>
                        <select class="in" id="p-tipo" name="tipo">
                            @foreach (\App\Models\Dividend::TIPOS as $id => $nome)
                                <option value="{{ $id }}" @selected(old('tipo') === $id)>{{ $nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="p-data">Data de pagamento</label>
                        <input class="in @error('data') invalido @enderror" id="p-data" type="date" name="data" value="{{ old('data', now()->toDateString()) }}" required>
                        @error('data')<span class="erro">{{ $message }}</span>@enderror
                    </div>
                </div>
                <x-campo nome="valor" rotulo="Valor total recebido" pre="R$" :valor="old('valor')" required dica="Data futura = provento a receber." />
                <button class="btn primary" type="submit">Registrar</button>
            </form>
        </section>

        <div class="stack">
            @if ($aReceber->isNotEmpty())
                <section class="bloco borda-ouro" aria-labelledby="t-receber">
                    <h2 id="t-receber">A receber</h2>
                    @foreach ($aReceber as $p)
                        <div class="list-row">
                            <span><strong>{{ $p->ticker }}</strong> <span class="small muted">{{ $p->nomeTipo() }} · {{ $p->data->format('d/m/Y') }}</span></span>
                            <strong class="num"><x-valor :v="brl($p->valor)" /></strong>
                        </div>
                    @endforeach
                </section>
            @endif
            <section class="bloco" aria-labelledby="t-recebidos">
                <h2 id="t-recebidos">Recebidos</h2>
                @forelse ($proventos as $p)
                    <div class="list-row">
                        <span><strong>{{ $p->ticker }}</strong> <span class="small muted">{{ $p->nomeTipo() }} · {{ $p->data->format('d/m/Y') }}</span></span>
                        <span class="row" style="gap:6px">
                            <strong class="num"><x-valor :v="brl($p->valor)" /></strong>
                            <form method="POST" action="{{ route('proventos.destroy', $p) }}">@csrf @method('DELETE')
                                <button class="btn icon ghost" aria-label="Excluir provento de {{ $p->ticker }}"><x-icone nome="trash" /></button>
                            </form>
                        </span>
                    </div>
                @empty
                    <p class="small muted">Nenhum provento registrado ainda.</p>
                @endforelse
                {{ $proventos->links('partials.paginacao') }}
            </section>
        </div>
    </div>
</x-app-layout>
