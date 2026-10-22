@props(['action', 'ticker' => null, 'preco' => null, 'classe' => null])

{{-- Registrar compra ou venda. Sem ticker fixo, pede o código (botão "+ Operação" da carteira). --}}
<form method="POST" action="{{ $action }}" class="stack" style="gap:12px" x-data="{ tipo: @js(old('tipo', 'compra')), qtd: @js(old('quantidade', '')), preco: @js(old('preco', $preco !== null ? moeda_input($preco) : '')), taxas: @js(old('taxas', '')) }">
    @csrf
    <div class="seg seg-grande" role="group" aria-label="Tipo de operação">
        <button type="button" x-on:click="tipo = 'compra'" :aria-pressed="(tipo === 'compra').toString()">Compra</button>
        <button type="button" x-on:click="tipo = 'venda'" :aria-pressed="(tipo === 'venda').toString()">Venda</button>
    </div>
    <input type="hidden" name="tipo" :value="tipo">
    <div class="form-grid">
        @if (! $ticker)
            <div class="field">
                <label for="op-ticker">Código do ativo</label>
                <input class="in @error('ticker') invalido @enderror" id="op-ticker" name="ticker" value="{{ old('ticker') }}" placeholder="PETR4" autocapitalize="characters" autocomplete="off" required>
                @error('ticker')<span class="erro">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="op-classe">Tipo</label>
                <select class="in" id="op-classe" name="classe">
                    <option value="">Descobrir sozinho</option>
                    @foreach (config('aurum.classes') as $id => $c)
                        <option value="{{ $id }}" @selected(old('classe', $classe) === $id)>{{ $c['nome'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <x-campo nome="quantidade" rotulo="Quantidade" :valor="old('quantidade')" x-model="qtd" placeholder="100" required />
        <x-campo nome="preco" rotulo="Preço por cota" pre="R$" :valor="old('preco', $preco !== null ? moeda_input($preco) : '')" x-model="preco" required />
        <div class="field">
            <label for="op-data">Data</label>
            <input class="in @error('data') invalido @enderror" id="op-data" type="date" name="data" value="{{ old('data', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            @error('data')<span class="erro">{{ $message }}</span>@enderror
        </div>
        <x-campo nome="taxas" rotulo="Taxas (opcional)" pre="R$" :valor="old('taxas')" x-model="taxas" placeholder="0,00" />
    </div>
    <div class="row between">
        <span class="small muted">Total: <strong class="num" x-text="fmt.brl(fmt.parseNum(qtd) * fmt.parseNum(preco) + (tipo === 'compra' ? 1 : -1) * fmt.parseNum(taxas))">—</strong></span>
        <button class="btn primary btn-grande" type="submit" x-text="tipo === 'compra' ? 'Registrar compra' : 'Registrar venda'">Registrar</button>
    </div>
</form>
