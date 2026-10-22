<x-app-layout titulo="Carteira">
    <div class="page-head">
        <div>
            <div class="eyebrow">Minha carteira</div>
            <h1>Carteira</h1>
        </div>
        <div class="row" style="gap:8px">
            <x-cotacoes-status />
            <x-olho class="btn-olho" />
        </div>
    </div>

    <div x-data="{ classe: null, operacao: @js(session('abrirOperacao') || $errors->any() && old('ticker') !== null) }">
        <div class="bento">
            {{-- Patrimônio --}}
            <section class="bloco largo" aria-labelledby="t-pat">
                <div class="row between" style="align-items:flex-end">
                    <div>
                        <span id="t-pat" class="bloco-rotulo">Patrimônio</span>
                        <div class="bloco-valor-grande num"><x-valor :v="brl($resumo['total'])" /></div>
                        <span class="small muted">Investido <x-valor :v="brl($resumo['custo'])" /></span>
                    </div>
                    <div class="resultado-box {{ $resumo['resultado'] >= 0 ? 'good' : 'bad' }}">
                        <span class="small">Resultado</span>
                        <strong>{{ $resumo['resultado'] >= 0 ? '▲ +' : '▼ −' }}{{ pct(abs($resumo['resultado_pct'])) }}</strong>
                        <span class="small"><x-valor :v="brl($resumo['resultado'])" mascara="•••" /></span>
                    </div>
                </div>
                @if ($lucroRealizadoAno != 0)
                    <span class="small muted">Lucro com vendas em {{ now()->year }}: <x-valor :v="brl($lucroRealizadoAno)" /></span>
                @endif
            </section>

            {{-- Aporte do mês --}}
            <section class="bloco largo" aria-labelledby="t-aporte">
                <div class="row between">
                    <span id="t-aporte" class="fw7">Aporte do mês</span>
                    <span class="small muted">
                        @if ($metaAporte > 0)
                            <x-valor :v="brl($aporteMes, 0)" mascara="••••" /> de <x-valor :v="brl($metaAporte, 0)" mascara="••••" />
                        @else
                            <x-valor :v="brl($aporteMes, 0)" mascara="••••" /> investidos
                        @endif
                    </span>
                </div>
                @if ($metaAporte > 0)
                    @php($pctAporte = min(100, $aporteMes / $metaAporte * 100))
                    <div class="meter meter-ouro" role="progressbar" aria-valuenow="{{ round($pctAporte) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Aporte do mês"><div style="width: {{ $pctAporte }}%"></div></div>
                    <span class="small muted">{{ pct($pctAporte, 0) }} da meta · {{ $diasRestantes === 0 ? 'último dia do mês' : "faltam $diasRestantes dias" }}</span>
                @else
                    <span class="small muted">Defina uma meta mensal em “Metas” para acompanhar aqui.</span>
                @endif
            </section>

            {{-- Classes: tocar filtra a lista --}}
            @foreach ($resumo['classes'] as $c)
                <button type="button" class="bloco bloco-classe" x-on:click="classe = classe === '{{ $c['id'] }}' ? null : '{{ $c['id'] }}'" :aria-pressed="(classe === '{{ $c['id'] }}').toString()" aria-pressed="false">
                    <span class="row small muted" style="gap:6px"><span class="dot" style="background: var(--{{ $c['cor'] }})"></span>{{ $c['nome'] }}</span>
                    <span class="bloco-num">{{ pct($c['atual'], 0) }}</span>
                    <span @class(['small fw7', 'aviso-meta' => $c['situacao'] === 'abaixo', 'muted' => $c['situacao'] !== 'abaixo'])>
                        meta {{ pct($c['meta'], 0) }}@if ($c['situacao'] === 'abaixo') · aportar @elseif ($c['situacao'] === 'acima') · acima @endif
                    </span>
                </button>
            @endforeach

            {{-- Posições --}}
            <section class="bloco cheio" aria-labelledby="t-posicoes">
                <div class="row between">
                    <h2 id="t-posicoes">
                        <span x-show="!classe">Todas as posições</span>
                        @foreach (config('aurum.classes') as $id => $c)
                            <span x-show="classe === '{{ $id }}'" x-cloak>{{ $c['nome'] }} <button type="button" class="link-btn small" x-on:click="classe = null">ver todas</button></span>
                        @endforeach
                    </h2>
                    <button type="button" class="btn primary" x-on:click="operacao = true"><x-icone nome="plus" />Operação</button>
                </div>
                @if ($ativos->isEmpty())
                    <div class="empty">
                        <p>Nenhuma posição ainda.</p>
                        <a class="btn" href="{{ route('buscar') }}"><x-icone nome="search" />Buscar um ativo</a>
                    </div>
                @else
                    <div class="lista-posicoes">
                        @foreach ($ativos as $a)
                            <a class="posicao" href="{{ route('ativo.show', $a->ticker) }}" x-show="!classe || classe === '{{ $a->classe }}'">
                                <span class="posicao-nome">
                                    <strong>{{ $a->ticker }}</strong>
                                    <span class="small muted">{{ numero($a->quantidade, 8) }} cotas · PM <x-valor :v="brl($a->preco_medio)" mascara="•••" /></span>
                                </span>
                                <span class="posicao-valor">
                                    <strong class="num"><x-valor :v="brl($a->valor())" /></strong>
                                    <span class="small fw7 {{ $a->resultado() >= 0 ? 'up' : 'down' }}">{{ $a->resultado() >= 0 ? '▲ +' : '▼ −' }}{{ pct(abs($a->resultadoPercentual())) }}</span>
                                </span>
                                <span class="posicao-peso" aria-hidden="true"><span style="width: {{ $resumo['total'] > 0 ? round($a->valor() / $resumo['total'] * 100, 1) : 0 }}%; background: var(--{{ $a->cor() }})"></span></span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        @if ($evolucao->count() > 1)
            <section class="bloco" style="margin-top:16px" aria-labelledby="t-evolucao">
                <div class="row between">
                    <h2 id="t-evolucao">Evolução</h2>
                    <span class="legend"><span><i style="background:var(--gold-line)"></i>Valor</span><span><i style="background:var(--faint)"></i>Investido</span></span>
                </div>
                <div class="chart-box short" data-sensivel><canvas data-grafico="evolucao" data-dados='@json($evolucao)' role="img" aria-label="Evolução do valor da carteira"></canvas></div>
            </section>
        @endif

        <div class="grid g2" style="margin-top:16px">
            <section class="bloco" aria-labelledby="t-ops">
                <h2 id="t-ops">Últimas operações</h2>
                @forelse ($operacoes as $op)
                    <div class="list-row">
                        <span class="row" style="gap:10px;flex-wrap:nowrap;min-width:0">
                            <span class="chip {{ $op->tipo === 'compra' ? 'good' : 'bad' }}">{{ $op->tipo === 'compra' ? 'Compra' : 'Venda' }}</span>
                            <span><strong>{{ $op->asset->ticker }}</strong> <span class="small muted">{{ $op->data->format('d/m') }} · {{ numero($op->quantidade, 8) }} × <x-valor :v="brl($op->preco)" mascara="•••" /></span></span>
                        </span>
                        <form method="POST" action="{{ route('carteira.desfazer', $op) }}" x-data x-on:submit="if (!confirm('Desfazer esta operação? O preço médio será recalculado.')) $event.preventDefault()">@csrf @method('DELETE')
                            <button class="btn sm ghost" type="submit">Desfazer</button>
                        </form>
                    </div>
                @empty
                    <p class="small muted">Nenhuma operação ainda.</p>
                @endforelse
            </section>

            <section class="bloco" aria-labelledby="t-metas" id="metas">
                <h2 id="t-metas">Metas</h2>
                <form method="POST" action="{{ route('carteira.metas') }}" class="stack" style="gap:12px">
                    @csrf @method('PUT')
                    <div class="form-grid">
                        @foreach (config('aurum.classes') as $id => $c)
                            <x-campo :nome="'metas['.$id.']'" :rotulo="$c['nome']" suf="%" :valor="old('metas.'.$id, numero($metas[$id] ?? 0, 2))" />
                        @endforeach
                    </div>
                    @error('metas')<span class="erro">{{ $message }}</span>@enderror
                    <x-campo nome="meta_aporte_mensal" rotulo="Meta de aporte por mês" pre="R$" :valor="old('meta_aporte_mensal', $metaAporte > 0 ? moeda_input($metaAporte) : '')" placeholder="1.000,00" dica="Quanto você quer investir todo mês." />
                    <button class="btn" type="submit">Salvar metas</button>
                </form>
            </section>
        </div>

        {{-- Folha "+ Operação" --}}
        <div class="modal-back" x-show="operacao" x-cloak x-transition.opacity x-on:click.self="operacao = false" x-on:keydown.escape.window="operacao = false">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="t-nova-op">
                <div class="row between">
                    <h2 id="t-nova-op">Nova operação</h2>
                    <button type="button" class="btn icon ghost" x-on:click="operacao = false" aria-label="Fechar"><x-icone nome="x" /></button>
                </div>
                <x-form-operacao :action="route('carteira.operacao')" />
            </div>
        </div>
    </div>
</x-app-layout>
