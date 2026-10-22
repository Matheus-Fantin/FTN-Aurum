@props(['v', 'mascara' => 'R$ •••••'])
{{-- Valor em dinheiro que some quando o olho de privacidade está fechado. --}}
<span {{ $attributes->merge(['class' => 'valor']) }}><span class="valor-v">{{ $v }}</span><span class="valor-m" role="img" aria-label="valor oculto">{{ $mascara }}</span></span>
