@php
    $secoes = [
        'painel' => ['rota' => 'dashboard', 'nome' => 'Painel', 'icone' => 'grid', 'ativa' => ['dashboard']],
        'buscar' => ['rota' => 'buscar', 'nome' => 'Buscar', 'icone' => 'search', 'ativa' => ['buscar', 'ativo.*']],
        'carteira' => ['rota' => 'carteira', 'nome' => 'Carteira', 'icone' => 'wallet', 'ativa' => ['carteira*']],
        'favoritos' => ['rota' => 'favoritos', 'nome' => 'Favoritos', 'icone' => 'star', 'ativa' => ['favoritos*', 'alertas.*']],
        'proventos' => ['rota' => 'proventos', 'nome' => 'Proventos', 'icone' => 'dollar', 'ativa' => ['proventos*']],
    ];
    if (Auth::user()->is_admin) {
        $secoes['admin'] = ['rota' => 'admin.convites.index', 'nome' => 'Convites', 'icone' => 'user', 'ativa' => ['admin.*']];
    }
    $atual = collect($secoes)->search(fn ($s) => request()->routeIs(...$s['ativa']));
    $barraCelular = ['painel', 'buscar', 'carteira', 'favoritos'];
    $alertasNovos = Auth::user()->alertasNovos();
@endphp
<!DOCTYPE html>
<html lang="pt-BR" translate="no">
<head>
    @include('partials.head', ['titulo' => $titulo ?? null])
</head>
<body>
<div class="app">
    <aside class="side">
        <x-marca />
        <nav class="nav" aria-label="Seções">
            @foreach ($secoes as $chave => $s)
                <a href="{{ route($s['rota']) }}" @if ($atual === $chave) aria-current="page" @endif>
                    <x-icone :nome="$s['icone']" /><span>{{ $s['nome'] }}</span>
                    @if ($chave === 'favoritos' && $alertasNovos)<span class="badge">{{ $alertasNovos }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="side-foot">
            <div class="side-user">
                <span class="side-name">{{ Auth::user()->name }}</span>
                <button type="button" data-alternar-valores aria-pressed="false"><x-icone nome="eye" class="olho-aberto" /><x-icone nome="eye-off" class="olho-fechado" /><span data-valores-rotulo>Ocultar valores</span></button>
                <a href="{{ route('profile.edit') }}"><x-icone nome="user" />Meu perfil</a>
                <button type="button" data-alternar-tema><x-icone nome="theme" /><span data-tema-rotulo>Tema do sistema</span></button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><x-icone nome="logout" />Sair</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Celular: topo com saudação, olho de privacidade e alertas --}}
    <header class="topo-celular">
        <x-marca />
        <div class="row" style="gap:6px">
            <x-olho class="btn-topo" />
            <a href="{{ route('favoritos') }}#alertas" class="btn-topo" aria-label="Alertas{{ $alertasNovos ? ", $alertasNovos novos" : '' }}">
                <x-icone nome="bell" />
                @if ($alertasNovos)<span class="ponto-alerta"></span>@endif
            </a>
        </div>
    </header>

    <main>
        <div class="wrap">
            {{ $slot }}
        </div>
    </main>
</div>

{{-- Celular: barra flutuante (layout E). A seção atual mostra o nome. --}}
<nav class="barra-celular" aria-label="Navegação" x-data="{ mais: false }">
    @foreach ($barraCelular as $chave)
        <a href="{{ route($secoes[$chave]['rota']) }}" @if ($atual === $chave) aria-current="page" @endif aria-label="{{ $secoes[$chave]['nome'] }}">
            <x-icone :nome="$secoes[$chave]['icone']" /><span>{{ $secoes[$chave]['nome'] }}</span>
            @if ($chave === 'favoritos' && $alertasNovos)<span class="ponto-alerta"></span>@endif
        </a>
    @endforeach
    <button type="button" x-on:click="mais = true" aria-label="Mais opções" @if (! in_array($atual, $barraCelular, true) && $atual) aria-current="page" @endif><x-icone nome="list" /><span>Mais</span></button>

    <div class="folha-fundo" x-show="mais" x-cloak x-transition.opacity x-on:click="mais = false"></div>
    <div class="folha" x-show="mais" x-cloak x-transition x-on:keydown.escape.window="mais = false" role="dialog" aria-label="Mais opções">
        <div class="row between"><strong>Mais opções</strong><button type="button" class="btn icon ghost" x-on:click="mais = false" aria-label="Fechar"><x-icone nome="x" /></button></div>
        @foreach ($secoes as $chave => $s)
            @continue(in_array($chave, $barraCelular, true))
            <a href="{{ route($s['rota']) }}"><x-icone :nome="$s['icone']" />{{ $s['nome'] }}</a>
        @endforeach
        <div class="divider"></div>
        <a href="{{ route('profile.edit') }}"><x-icone nome="user" />Meu perfil</a>
        <button type="button" data-alternar-tema><x-icone nome="theme" /><span data-tema-rotulo>Tema do sistema</span></button>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-icone nome="logout" />Sair</button></form>
    </div>
</nav>

@if (session('sucesso') || session('status') === 'profile-updated' || session('status') === 'password-updated')
    <div class="flash" role="status" x-data="{ v: true }" x-show="v" x-init="setTimeout(() => v = false, 3200)" x-transition.opacity>
        {{ session('sucesso') ?? 'Alterações salvas.' }}
    </div>
@endif
@if (session('aviso'))
    <div class="flash" role="status" x-data="{ v: true }" x-show="v" x-init="setTimeout(() => v = false, 6000)" x-transition.opacity>{{ session('aviso') }}</div>
@endif
</body>
</html>
