@props(['termo' => '', 'manter' => []])

{{-- Busca de ativos com sugestões enquanto digita (lista local + brapi). --}}
@php
    $conhecidos = collect(config('ativos_b3'))->map(fn ($a, $t) => ['ticker' => $t, 'nome' => $a[0], 'tipo' => $a[1]])->values();
    $meus = auth()->user()->assets()->where('quantidade', '>', 0)->pluck('ticker')->all();
@endphp
<form method="GET" action="{{ route('buscar') }}" class="busca-ativos" role="search"
      x-data="buscaAtivos(@js(['termo' => $termo, 'conhecidos' => $conhecidos, 'meus' => $meus, 'url' => route('buscar.sugestoes'), 'rotaAtivo' => route('ativo.show', '__T__')]))"
      x-on:submit="enviar($event)" x-on:click.outside="aberto = false" x-on:keydown.escape="aberto = false">
    <div class="search-box">
        <div class="in-group">
            <span class="affix"><x-icone nome="search" width="16" height="16" /></span>
            <input class="in" name="q" x-model="q" x-on:input="digitar()" x-on:focus="aberto = q.trim() !== ''"
                   x-on:keydown.arrow-down.prevent="mover(1)" x-on:keydown.arrow-up.prevent="mover(-1)"
                   placeholder="Nome, código ou setor: Petrobras, PETR4, bancos…" aria-label="Buscar ativo"
                   autocomplete="off" role="combobox" aria-autocomplete="list" :aria-expanded="aberto && sugestoes.length > 0" aria-controls="sugestoes-ativos">
        </div>
        <button class="btn primary" type="submit">Buscar</button>
    </div>
    @foreach ($manter as $nome => $valor)
        @foreach ((array) $valor as $v)
            <input type="hidden" name="{{ is_array($valor) ? $nome.'[]' : $nome }}" value="{{ $v }}">
        @endforeach
    @endforeach

    <div class="sugestoes" id="sugestoes-ativos" role="listbox" x-show="aberto && (sugestoes.length || carregando)" x-cloak x-transition.opacity.duration.100ms>
        <template x-for="(a, i) in sugestoes" :key="a.ticker">
            <a class="sugestao" role="option" :href="'{{ url('ativo') }}/' + encodeURIComponent(a.ticker)" :aria-selected="i === ativo"
               x-on:mouseenter="ativo = i" x-on:click.prevent="escolher(a)">
                <img class="ativo-logo" x-show="a.logo" :src="a.logo" alt="" referrerpolicy="no-referrer">
                <span class="ativo-logo sugestao-sigla" x-show="!a.logo" x-text="a.ticker.slice(0, 2)"></span>
                <span class="sugestao-texto">
                    <strong x-html="destacar(a.ticker)"></strong>
                    <span class="small faint" x-html="destacar(a.nome)"></span>
                </span>
                <span class="sugestao-dir">
                    <span class="chip good" x-show="a.meu">na carteira</span>
                    <span class="chip" x-show="!a.meu && a.tipo" x-text="a.tipo"></span>
                    <span class="small num" x-show="a.preco != null" x-text="fmt.brl(a.preco)"></span>
                    <span class="small num" x-show="a.variacao != null" :class="a.variacao >= 0 ? 'up' : 'down'" x-text="(a.variacao >= 0 ? '▲ ' : '▼ ') + fmt.pct(Math.abs(a.variacao), 2)"></span>
                </span>
            </a>
        </template>
        <div class="sugestao-rodape small faint" x-show="carregando">Buscando mais na B3…</div>
    </div>
</form>
