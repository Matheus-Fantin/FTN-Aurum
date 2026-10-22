@props(['itens', 'vazio' => 'Sem dados agora.', 'numerar' => false])

{{-- Lista de ativos do mercado (rankings, busca, favoritos). Cada linha abre a página do ativo. --}}
@if (count($itens) === 0)
    <div class="empty"><p>{{ $vazio }}</p></div>
@else
    <div class="lista-ativos">
        @foreach ($itens as $i => $r)
            <a class="ativo-linha {{ $numerar ? '' : 'sem-pos' }}" href="{{ route('ativo.show', $r['ticker']) }}">
                @if ($numerar)<span class="ativo-pos num">{{ $i + 1 }}</span>@endif
                <x-logo-ativo :logo="$r['logo'] ?? null" :ticker="$r['ticker']" />
                <span class="ativo-nome"><strong>{{ $r['ticker'] }}</strong><span class="small faint">{{ $r['nome'] ?? '' }}</span></span>
                <span class="ativo-preco num">
                    <strong>{{ ($r['preco'] ?? null) !== null ? brl($r['preco']) : '—' }}</strong>
                    <x-variacao :v="$r['variacao'] ?? null" />
                </span>
            </a>
        @endforeach
    </div>
@endif
