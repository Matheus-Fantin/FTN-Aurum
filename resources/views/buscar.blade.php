@php
    // Link que liga/desliga um filtro mantendo os outros parâmetros da busca.
    $alternar = function (string $filtro) use ($filtros, $termo, $classe, $ordem) {
        $novos = in_array($filtro, $filtros, true) ? array_values(array_diff($filtros, [$filtro])) : [...$filtros, $filtro];

        return route('buscar', array_filter(['q' => $termo, 'f' => $novos, 'classe' => $classe, 'ordem' => $ordem !== 'volume' ? $ordem : null]));
    };
    $comClasse = fn (?string $c) => route('buscar', array_filter(['q' => $termo, 'f' => $filtros, 'classe' => $c, 'ordem' => $ordem !== 'volume' ? $ordem : null]));
@endphp
<x-app-layout titulo="Buscar">
    <div class="page-head">
        <div>
            <div class="eyebrow">Ações, FIIs, ETFs e BDRs</div>
            <h1>Encontrar ativos</h1>
        </div>
    </div>

    <x-busca-ativos :termo="$termo" :manter="array_filter(['f' => $filtros, 'classe' => $classe, 'ordem' => $ordem !== 'volume' ? $ordem : null])" />

    <section aria-labelledby="t-filtros" class="stack" style="gap:8px">
        <div class="row between">
            <h2 id="t-filtros" class="eyebrow">Filtros — toque para combinar</h2>
            @if ($filtros || $classe)
                <a class="link small" href="{{ route('buscar', array_filter(['q' => $termo])) }}">Limpar</a>
            @endif
        </div>
        <div class="filtros">
            @foreach (config('aurum.filtros') as $chave => $f)
                @php($ligado = in_array($chave, $filtros, true))
                <a href="{{ $alternar($chave) }}" class="filtro" aria-pressed="{{ $ligado ? 'true' : 'false' }}" role="button">
                    <x-icone :nome="$f['icone']" />
                    <span>{{ $f['nome'] }}</span>
                </a>
            @endforeach
        </div>
        <div class="presets" role="group" aria-label="Tipo de ativo">
            <a href="{{ $comClasse(null) }}" @class(['ativo' => ! $classe])>Todos</a>
            @foreach (config('aurum.classes') as $id => $c)
                <a href="{{ $comClasse($id) }}" @class(['ativo' => $classe === $id])>{{ $c['nome'] }}</a>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="t-resultados" class="stack" style="gap:10px">
        <div class="row between">
            <h2 id="t-resultados">
                {{ $resultados->total() }} {{ $resultados->total() === 1 ? 'resultado' : 'resultados' }}
                @if ($termo)<span class="muted small">para “{{ $termo }}”</span>@endif
            </h2>
            <form method="GET" action="{{ route('buscar') }}" class="row" style="gap:6px">
                @if ($termo)<input type="hidden" name="q" value="{{ $termo }}">@endif
                @foreach ($filtros as $f)<input type="hidden" name="f[]" value="{{ $f }}">@endforeach
                @if ($classe)<input type="hidden" name="classe" value="{{ $classe }}">@endif
                <label for="ordem" class="small muted">Ordenar</label>
                <select id="ordem" name="ordem" class="in in-sm" onchange="this.form.submit()">
                    @foreach ($ordens as $chave => $nome)
                        <option value="{{ $chave }}" @selected($ordem === $chave)>{{ $nome }}</option>
                    @endforeach
                </select>
                <noscript><button class="btn sm">Ok</button></noscript>
            </form>
        </div>

        @if ($resultados->isEmpty())
            @if ($externos)
                <div class="bloco">
                    <p class="small muted">Encontrados direto na B3 (a lista do mercado ainda não tem estes):</p>
                    <x-lista-ativos :itens="$externos" />
                </div>
            @else
                <div class="bloco empty">
                    <strong>Nenhum ativo com tudo isso</strong>
                    <p class="small muted">
                        @if ($mercadoVazio)
                            A lista do mercado ainda está vazia. Rode <code>php artisan mercado:sincronizar --fundamentos</code>.
                        @else
                            Desligue um filtro ou confira o código (ex.: PETR4).
                        @endif
                    </p>
                </div>
            @endif
        @else
            <div class="cartoes-ativos">
                @foreach ($resultados as $a)
                    <a class="cartao-ativo" href="{{ route('ativo.show', $a->ticker) }}">
                        <span class="row between" style="flex-wrap:nowrap">
                            <span class="row" style="gap:8px;flex-wrap:nowrap;min-width:0">
                                <x-logo-ativo :logo="$a->logo" :ticker="$a->ticker" />
                                <strong>{{ $a->ticker }}</strong>
                                @if (in_array($a->ticker, $favoritos, true))<x-icone nome="star-cheia" class="estrela" width="14" height="14" />@endif
                            </span>
                            <span class="chip">{{ $a->nomeClasse() }}</span>
                        </span>
                        <span class="small muted elipse">{{ $a->nome }}</span>
                        <span class="row between">
                            <span class="num">{{ $a->preco !== null ? brl($a->preco) : '—' }}</span>
                            <x-variacao :v="$a->variacao" :chip="false" class="fw7 small" />
                        </span>
                        <span class="indicadores small">
                            <span>DY {{ $a->dy !== null ? pct($a->dy) : '—' }}</span>
                            <span>P/L {{ $a->pl !== null && $a->pl > 0 ? numero($a->pl, 1) : '—' }}</span>
                            @if ($a->radar_nota)<span class="ouro fw7">Radar {{ numero($a->radar_nota, 1) }}</span>@endif
                        </span>
                    </a>
                @endforeach
            </div>
            {{ $resultados->links('partials.paginacao') }}
        @endif
        <p class="note">Dados da brapi.dev. Radar, DY e P/L são calculados uma vez por dia para os ativos mais negociados. Nada aqui é recomendação de compra.</p>
    </section>
</x-app-layout>
