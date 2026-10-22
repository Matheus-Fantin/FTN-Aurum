@props(['v', 'casas' => 2, 'chip' => true])
{{-- Variação com seta e sinal (não depende só da cor). --}}
@if ($v === null)
    <span {{ $attributes->merge(['class' => 'faint']) }}>—</span>
@else
    <span {{ $attributes->merge(['class' => ($chip ? 'chip ' : '').($v >= 0 ? ($chip ? 'good' : 'up') : ($chip ? 'bad' : 'down'))]) }}>{{ $v >= 0 ? '▲ +' : '▼ −' }}{{ pct(abs($v), $casas) }}</span>
@endif
