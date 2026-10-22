<!DOCTYPE html>
<html lang="pt-BR" translate="no">
<head>
    @include('partials.head')
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <a href="/" class="brand">
            <span class="brand-mark">A</span>
            <span class="brand-name">FTN <span class="ouro">Aurum</span></span>
        </a>
        <div class="card">
            {{ $slot }}
        </div>
    </div>
</div>
@if (session('aviso'))
    <div class="flash" role="status" x-data="{ v: true }" x-show="v" x-init="setTimeout(() => v = false, 6000)" x-transition.opacity>{{ session('aviso') }}</div>
@endif
</body>
</html>
