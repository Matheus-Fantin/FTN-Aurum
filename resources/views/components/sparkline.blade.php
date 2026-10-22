@props(['pontos' => [], 'cor' => 'currentColor', 'altura' => 48])
{{-- Mini gráfico de linha (sem eixos) para blocos e listas. --}}
@php
    $pontos = array_values(array_filter($pontos, fn ($p) => $p !== null));
    $n = count($pontos);
    $linha = '';
    if ($n >= 2) {
        $min = min($pontos);
        $max = max($pontos);
        $faixa = $max - $min ?: 1;
        $linha = collect($pontos)->map(fn ($p, $i) => round($i / ($n - 1) * 300, 1).','.round(($altura - 4) - ($p - $min) / $faixa * ($altura - 8), 1))->implode(' ');
    }
@endphp
@if ($linha)
    <svg viewBox="0 0 300 {{ $altura }}" preserveAspectRatio="none" aria-hidden="true" {{ $attributes->merge(['class' => 'sparkline']) }}>
        <polyline points="{{ $linha }}" fill="none" stroke="{{ $cor }}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
    </svg>
@endif
