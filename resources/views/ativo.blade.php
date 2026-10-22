<x-app-layout :titulo="$ticker">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('buscar') }}" class="link small voltar"><x-icone nome="left" width="16" height="16" />Voltar</a>

    @if (! $info)
        <div class="bloco empty">
            <strong>Não encontramos {{ $ticker }}</strong>
            <p class="small muted">Confira o código (ex.: PETR4, HGLG11, IVVB11) ou tente de novo em alguns minutos.</p>
            <a class="btn" href="{{ route('buscar', ['q' => $ticker]) }}">Buscar por “{{ $ticker }}”</a>
        </div>
    @else
        <section class="bloco cabecalho-ativo" aria-labelledby="t-ativo">
            <div class="row between" style="flex-wrap:nowrap;align-items:flex-start">
                <div class="row" style="gap:12px;flex-wrap:nowrap;min-width:0">
                    <x-logo-ativo :logo="$info->logo" :ticker="$ticker" />
                    <div style="min-width:0">
                        <h1 id="t-ativo">{{ $ticker }}</h1>
                        <div class="small muted elipse">{{ $info->nome }}@if ($info->setor) · {{ $info->setor }}@endif · {{ $info->nomeClasse() }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('ativo.favorito', $ticker) }}">
                    @csrf
                    <button class="btn icon btn-estrela" type="submit" aria-pressed="{{ $favorito ? 'true' : 'false' }}" aria-label="{{ $favorito ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}">
                        <x-icone :nome="$favorito ? 'star-cheia' : 'star'" />
                    </button>
                </form>
            </div>
            <div class="row" style="gap:10px;align-items:baseline">
                <span class="big-num num">{{ $info->preco !== null ? brl($info->preco) : '—' }}</span>
                <x-variacao :v="$info->variacao" />
                <span class="small faint">hoje</span>
            </div>
            @if ($historico->count() > 1)
                <div class="chart-box short"><canvas data-grafico="historico" data-dados='@json($historico)' role="img" aria-label="Gráfico de preço de {{ $ticker }} nos últimos meses"></canvas></div>
            @endif
            <div class="indicadores-grid">
                <div><span class="small muted">Dividend yield</span><strong>{{ $info->dy !== null ? pct($info->dy) : '—' }}</strong></div>
                <div><span class="small muted">P/L</span><strong>{{ $info->pl !== null && $info->pl > 0 ? numero($info->pl, 1) : '—' }}</strong></div>
                <div><span class="small muted">Mínima do período</span><strong class="num">{{ $info->minima_periodo ? brl($info->minima_periodo) : '—' }}</strong></div>
                <div><span class="small muted">Máxima do período</span><strong class="num">{{ $info->maxima_periodo ? brl($info->maxima_periodo) : '—' }}</strong></div>
                <div><span class="small muted">Média de 20 dias</span><strong class="num">{{ $info->media_20 ? brl($info->media_20) : '—' }}</strong></div>
                <div><span class="small muted">No mês</span><x-variacao :v="$info->variacao_mes" :chip="false" class="fw7" /></div>
            </div>
        </section>

        <div class="grid g2">
            {{-- Radar --}}
            <section class="bloco borda-ouro" aria-labelledby="t-radar">
                <div class="row between">
                    <h2 id="t-radar">Radar de possíveis altas</h2>
                    <span class="nota-radar">{{ $info->radar_nota !== null ? numero($info->radar_nota, 1) : '—' }}<small>/10</small></span>
                </div>
                @if ($info->radar_sinais)
                    <ul class="sinais">
                        @foreach (config('aurum.radar') as $sinal)
                            @php($tem = in_array($sinal['texto'], $info->radar_sinais, true))
                            <li @class(['tem' => $tem])><x-icone :nome="$tem ? 'check' : 'x'" width="16" height="16" />{{ $sinal['texto'] }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="small muted">Os sinais aparecem depois do cálculo diário de indicadores.</p>
                @endif
                <p class="nota-legal">Sinais técnicos e de preço para estudar. Não é recomendação de compra.</p>
            </section>

            {{-- Minha posição --}}
            <section class="bloco" aria-labelledby="t-posicao">
                <h2 id="t-posicao">Minha posição</h2>
                @if ($posicao)
                    <div class="indicadores-grid">
                        <div><span class="small muted">Quantidade</span><strong class="num">{{ numero($posicao->quantidade, 8) }}</strong></div>
                        <div><span class="small muted">Preço médio</span><strong class="num"><x-valor :v="brl($posicao->preco_medio)" /></strong></div>
                        <div><span class="small muted">Valor hoje</span><strong class="num"><x-valor :v="brl($posicao->valor())" /></strong></div>
                        <div><span class="small muted">Resultado</span><strong class="{{ $posicao->resultado() >= 0 ? 'up' : 'down' }}">{{ $posicao->resultado() >= 0 ? '▲ +' : '▼ −' }}{{ pct(abs($posicao->resultadoPercentual())) }}</strong></div>
                    </div>
                @else
                    <p class="small muted">Você ainda não tem {{ $ticker }}. Registre uma compra abaixo para acompanhar.</p>
                @endif
            </section>
        </div>

        <div class="grid g2">
            <section class="bloco" aria-labelledby="t-operacao" id="operacao">
                <h2 id="t-operacao">Registrar compra ou venda</h2>
                <x-form-operacao :action="route('ativo.operacao', $ticker)" :ticker="$ticker" :preco="$info->preco" :classe="$info->classe" />
            </section>

            <section class="bloco" aria-labelledby="t-alerta" id="alerta">
                <h2 id="t-alerta">Alerta de preço</h2>
                <p class="small muted">Avisamos no sino quando {{ $ticker }} chegar ao preço.</p>
                <form method="POST" action="{{ route('ativo.alerta', $ticker) }}" class="stack" style="gap:10px">
                    @csrf
                    <div class="form-grid">
                        <div class="field">
                            <label for="alerta-condicao">Quando</label>
                            <select class="in" id="alerta-condicao" name="condicao">
                                <option value="abaixo" @selected(old('condicao') === 'abaixo')>Cair até</option>
                                <option value="acima" @selected(old('condicao') === 'acima')>Subir até</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="alerta-preco">Preço</label>
                            <div class="in-group">
                                <span class="affix">R$</span>
                                <input class="in @error('preco', 'alerta') invalido @enderror" id="alerta-preco" name="preco" value="{{ old('condicao') ? old('preco') : '' }}" inputmode="decimal" autocomplete="off" data-moeda placeholder="{{ $info->preco ? moeda_input($info->preco) : '' }}" required>
                            </div>
                            @error('preco', 'alerta')<span class="erro">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <button class="btn" type="submit"><x-icone nome="bell" />Criar alerta</button>
                </form>
                @if ($alertas->isNotEmpty())
                    <div class="lista-simples">
                        @foreach ($alertas as $alerta)
                            <div class="list-row">
                                <span>{{ ucfirst($alerta->descricao()) }}</span>
                                <form method="POST" action="{{ route('alertas.destroy', $alerta) }}">@csrf @method('DELETE')
                                    <button class="btn icon ghost" aria-label="Excluir alerta de {{ $alerta->descricao() }}"><x-icone nome="trash" /></button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        @if ($operacoes->isNotEmpty() || $proventos->isNotEmpty())
            <div class="grid g2">
                <section class="bloco" aria-labelledby="t-hist">
                    <h2 id="t-hist">Minhas operações</h2>
                    @forelse ($operacoes as $op)
                        <div class="list-row">
                            <span class="row" style="gap:10px;flex-wrap:nowrap;min-width:0">
                                <span class="chip {{ $op->tipo === 'compra' ? 'good' : 'bad' }}">{{ $op->tipo === 'compra' ? 'Compra' : 'Venda' }}</span>
                                <span class="small">{{ $op->data->format('d/m/Y') }} · {{ numero($op->quantidade, 8) }} × <x-valor :v="brl($op->preco)" /></span>
                            </span>
                            <form method="POST" action="{{ route('carteira.desfazer', $op) }}" x-data x-on:submit="if (!confirm('Desfazer esta operação? O preço médio será recalculado.')) $event.preventDefault()">@csrf @method('DELETE')
                                <button class="btn sm ghost" type="submit">Desfazer</button>
                            </form>
                        </div>
                    @empty
                        <p class="small muted">Nenhuma operação.</p>
                    @endforelse
                </section>
                <section class="bloco" aria-labelledby="t-prov">
                    <h2 id="t-prov">Proventos recebidos</h2>
                    @forelse ($proventos as $p)
                        <div class="list-row"><span class="small">{{ $p->data->format('d/m/Y') }} · {{ $p->nomeTipo() }}</span><strong class="num"><x-valor :v="brl($p->valor)" /></strong></div>
                    @empty
                        <p class="small muted">Nenhum provento registrado. <a class="link" href="{{ route('proventos') }}">Registrar</a></p>
                    @endforelse
                </section>
            </div>
        @endif
        <p class="note">Dados da brapi.dev, com até 30 min de atraso. Histórico dos últimos meses.</p>
    @endif
</x-app-layout>
