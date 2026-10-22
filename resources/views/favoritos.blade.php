<x-app-layout titulo="Favoritos">
    <div class="page-head">
        <div>
            <div class="eyebrow">Acompanhar de perto</div>
            <h1>Favoritos e alertas</h1>
        </div>
    </div>

    <section class="bloco" aria-labelledby="t-fav">
        <h2 id="t-fav">Favoritos</h2>
        @if ($favoritos->isEmpty())
            <div class="empty">
                <p>Toque na estrela <x-icone nome="star" width="16" height="16" /> na página de um ativo para acompanhá-lo aqui.</p>
                <a class="btn" href="{{ route('buscar') }}"><x-icone nome="search" />Buscar ativos</a>
            </div>
        @else
            <div class="lista-ativos">
                @foreach ($favoritos as $f)
                    @php($i = $f['info'])
                    <div class="ativo-linha sem-pos com-acao">
                        <x-logo-ativo :logo="$i?->logo" :ticker="$f['ticker']" />
                        <a class="ativo-nome" href="{{ route('ativo.show', $f['ticker']) }}"><strong>{{ $f['ticker'] }}</strong><span class="small faint">{{ $i?->nome }}</span></a>
                        <span class="ativo-preco num">
                            <strong>{{ $i?->preco !== null ? brl($i->preco) : '—' }}</strong>
                            <x-variacao :v="$i?->variacao" />
                        </span>
                        <form method="POST" action="{{ route('ativo.favorito', $f['ticker']) }}">@csrf
                            <button class="btn icon ghost btn-estrela" type="submit" aria-pressed="true" aria-label="Remover {{ $f['ticker'] }} dos favoritos"><x-icone nome="star-cheia" /></button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="bloco" aria-labelledby="t-alertas" id="alertas">
        <h2 id="t-alertas">Alertas de preço</h2>
        @forelse ($alertas as $alerta)
            <div class="list-row">
                <span class="stack" style="gap:2px">
                    <span>
                        <a class="fw7" href="{{ route('ativo.show', $alerta->ticker) }}">{{ $alerta->ticker }}</a>
                        — avisar ao {{ $alerta->descricao() }}
                        @if (in_array($alerta->id, $novos, true))<span class="chip warn">novo</span>@endif
                    </span>
                    @if ($alerta->disparado_em)
                        <span class="small up">Disparou em {{ $alerta->disparado_em->format('d/m H:i') }} a {{ brl($alerta->preco_disparo) }}</span>
                    @else
                        <span class="small muted">Aguardando · preço agora {{ isset($precos[$alerta->ticker]) ? brl($precos[$alerta->ticker]) : '—' }}</span>
                    @endif
                </span>
                <form method="POST" action="{{ route('alertas.destroy', $alerta) }}">@csrf @method('DELETE')
                    <button class="btn icon ghost" aria-label="Excluir alerta de {{ $alerta->ticker }}"><x-icone nome="trash" /></button>
                </form>
            </div>
        @empty
            <p class="small muted">Nenhum alerta. Crie na página de um ativo: “avise quando cair até R$ 30”.</p>
        @endforelse
        <p class="note">Os alertas são conferidos a cada 30 minutos durante o pregão.</p>
    </section>
</x-app-layout>
