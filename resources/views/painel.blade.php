<x-app-layout titulo="Painel">
    <div class="page-head">
        <div>
            <div class="eyebrow">{{ now()->locale('pt_BR')->translatedFormat('l, d \d\e F') }}</div>
            <h1>Olá, {{ explode(' ', Auth::user()->name)[0] }}</h1>
        </div>
        <x-cotacoes-status />
    </div>

    @if ($semToken)
        <div class="banner warn">
            <span><strong>Falta o token da brapi.</strong> Sem ele não chegam cotações, altas e baixas. Crie um token gratuito em brapi.dev e coloque em <code>BRAPI_TOKEN</code> no arquivo <code>.env</code>.</span>
        </div>
    @elseif ($mercadoVazio)
        <div class="banner">
            <span>A lista do mercado ainda não foi carregada. Rode <code>php artisan mercado:sincronizar --fundamentos</code> (ou deixe o agendador fazer isso no próximo pregão).</span>
        </div>
    @endif

    @foreach ($alertas as $alerta)
        <a class="banner ouro-banner" href="{{ route('ativo.show', $alerta->ticker) }}">
            <span><x-icone nome="bell" width="16" height="16" /> <strong>{{ $alerta->ticker }}</strong> chegou a {{ brl($alerta->preco_disparo) }} — seu alerta era {{ $alerta->descricao() }}.</span>
            <span class="link">Ver ativo</span>
        </a>
    @endforeach

    <div class="bento">
        {{-- Patrimônio: bloco escuro com olho de privacidade --}}
        <section class="bloco escuro largo" aria-labelledby="t-patrimonio">
            <div class="row between">
                <span id="t-patrimonio" class="bloco-rotulo">Patrimônio em bolsa</span>
                <x-olho class="btn-olho claro" />
            </div>
            <div class="bloco-valor-grande num"><x-valor :v="brl($resumo['total'])" /></div>
            @if ($temAtivos)
                <div class="row" style="gap:8px">
                    <span class="{{ $resumo['hoje'] >= 0 ? 'up' : 'down' }} fw7">
                        {{ $resumo['hoje'] >= 0 ? '▲ +' : '▼ −' }}<x-valor :v="brl(abs($resumo['hoje']))" mascara="•••" /> ({{ pct(abs($resumo['hoje_pct']), 2) }}) hoje
                    </span>
                    <span class="bloco-sub">· {{ $resumo['resultado'] >= 0 ? '+' : '−' }}{{ pct(abs($resumo['resultado_pct'])) }} no total</span>
                </div>
                <x-sparkline :pontos="$evolucao" cor="var(--gold-line)" class="sparkline-hero" data-sensivel />
            @else
                <p class="bloco-sub">Você ainda não tem ativos. <a class="link-ouro" href="{{ route('buscar') }}">Busque uma ação</a> e registre sua primeira compra.</p>
            @endif
            <div class="bloco-sub small">
                @if ($atualizadoEm)Atualizado {{ $atualizadoEm->locale('pt_BR')->diffForHumans() }} · @endif
                Cotações com até 30 min de atraso
            </div>
        </section>

        {{-- Maior alta e maior baixa do dia --}}
        @php($alta = $rankings['altas']->first())
        @php($baixa = $rankings['baixas']->first())
        <a class="bloco verde" href="{{ $alta ? route('ativo.show', $alta->ticker) : route('buscar', ['ordem' => 'alta']) }}">
            <span class="bloco-rotulo up">▲ Maior alta</span>
            <span class="bloco-ticker">{{ $alta?->ticker ?? '—' }}</span>
            <span class="small muted">{{ $alta ? brl($alta->preco) : 'sem dados' }}</span>
            @if ($alta)<span class="bloco-pct up">+{{ pct($alta->variacao, 2) }}</span>@endif
        </a>
        <a class="bloco vermelho" href="{{ $baixa ? route('ativo.show', $baixa->ticker) : route('buscar', ['ordem' => 'baixa']) }}">
            <span class="bloco-rotulo down">▼ Maior baixa</span>
            <span class="bloco-ticker">{{ $baixa?->ticker ?? '—' }}</span>
            <span class="small muted">{{ $baixa ? brl($baixa->preco) : 'sem dados' }}</span>
            @if ($baixa)<span class="bloco-pct down">−{{ pct(abs($baixa->variacao), 2) }}</span>@endif
        </a>

        {{-- Índices --}}
        <section class="bloco">
            <span class="bloco-rotulo">Ibovespa</span>
            <span class="bloco-num num">{{ isset($indices['ibovespa']) ? numero($indices['ibovespa']['valor'], 0) : '—' }}</span>
            <x-variacao :v="$indices['ibovespa']['variacao'] ?? null" :chip="false" class="fw7 small" />
        </section>
        <section class="bloco">
            <span class="bloco-rotulo">Dólar</span>
            <span class="bloco-num num">{{ isset($indices['dolar']) ? brl($indices['dolar']['valor']) : '—' }}</span>
            <x-variacao :v="$indices['dolar']['variacao'] ?? null" :chip="false" class="fw7 small" />
        </section>

        {{-- Ranking do dia com abas --}}
        <section class="bloco largo" x-data="{ aba: 'altas' }" aria-labelledby="t-ranking">
            <div class="row between">
                <h2 id="t-ranking">Ranking do dia</h2>
                <div class="seg" role="group" aria-label="Escolher ranking">
                    <button type="button" x-on:click="aba = 'altas'" :aria-pressed="(aba === 'altas').toString()" aria-pressed="true">Altas</button>
                    <button type="button" x-on:click="aba = 'baixas'" :aria-pressed="(aba === 'baixas').toString()" aria-pressed="false">Baixas</button>
                    <button type="button" x-on:click="aba = 'negociadas'" :aria-pressed="(aba === 'negociadas').toString()" aria-pressed="false">Volume</button>
                </div>
            </div>
            @foreach (['altas', 'baixas', 'negociadas'] as $aba)
                @php($lista = $rankings[$aba])
                @php($maior = max(0.01, $lista->max(fn ($a) => abs($a->variacao ?? 0))))
                <div class="ranking" x-show="aba === '{{ $aba }}'" @if ($aba !== 'altas') x-cloak @endif>
                    @forelse ($lista as $a)
                        <a class="ranking-linha" href="{{ route('ativo.show', $a->ticker) }}">
                            <strong>{{ $a->ticker }}</strong>
                            <span class="ranking-barra"><span class="{{ $a->variacao >= 0 ? 'b-up' : 'b-down' }}" style="width: {{ round(abs($a->variacao) / $maior * 100) }}%"></span></span>
                            <x-variacao :v="$a->variacao" :chip="false" class="fw7 small" />
                        </a>
                    @empty
                        <p class="small muted">Sem dados do pregão ainda.</p>
                    @endforelse
                </div>
            @endforeach
            <a class="link small" href="{{ route('buscar', ['ordem' => 'alta']) }}">Ver todas</a>
        </section>

        {{-- Radar de possíveis altas --}}
        <a class="bloco borda-ouro" href="{{ route('buscar', ['f' => ['radar'], 'ordem' => 'radar']) }}">
            <span class="bloco-rotulo ouro">Radar</span>
            <span class="small muted">possíveis altas</span>
            <span class="radar-mini">
                @forelse ($radar as $r)
                    <span><strong>{{ $r->ticker }}</strong> <span class="ouro fw7">{{ numero($r->radar_nota, 1) }}</span></span>
                @empty
                    <span class="small muted">Aguardando indicadores.</span>
                @endforelse
            </span>
            <span class="nota-legal">Não é recomendação</span>
        </a>

        {{-- Próximo provento --}}
        <a class="bloco" href="{{ route('proventos') }}">
            @if ($proximoProvento)
                <span class="bloco-rotulo">Próximo provento</span>
                <span class="bloco-ticker">{{ $proximoProvento->ticker }}</span>
                <span class="small muted">dia {{ $proximoProvento->data->format('d/m') }}</span>
                <span class="bloco-num num"><x-valor :v="brl($proximoProvento->valor)" mascara="R$ •••" /></span>
            @elseif ($ultimoProvento)
                <span class="bloco-rotulo">Último provento</span>
                <span class="bloco-ticker">{{ $ultimoProvento->ticker }}</span>
                <span class="small muted">{{ $ultimoProvento->data->format('d/m') }}</span>
                <span class="bloco-num num"><x-valor :v="brl($ultimoProvento->valor)" mascara="R$ •••" /></span>
            @else
                <span class="bloco-rotulo">Proventos</span>
                <span class="small muted">Registre dividendos e JCP para acompanhar sua renda.</span>
                <span class="link small">Registrar</span>
            @endif
        </a>

        {{-- Suas ações que mais se mexeram hoje --}}
        @if ($minhas->isNotEmpty())
            <section class="bloco cheio" aria-labelledby="t-minhas">
                <div class="row between">
                    <h2 id="t-minhas">Suas ações hoje</h2>
                    <a class="link small" href="{{ route('carteira') }}">Carteira</a>
                </div>
                <div class="minhas">
                    @foreach ($minhas as $a)
                        <a class="minha" href="{{ route('ativo.show', $a->ticker) }}">
                            <strong>{{ $a->ticker }}</strong>
                            <span class="small muted num">{{ brl($a->cotacao) }}</span>
                            <x-variacao :v="$a->variacao_dia" :chip="false" class="fw7 small" />
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
