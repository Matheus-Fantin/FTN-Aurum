@props(['logo' => null, 'ticker'])
@if ($logo)
    <img class="ativo-logo" src="{{ $logo }}" alt="" loading="lazy" referrerpolicy="no-referrer">
@else
    <span class="ativo-logo sigla" aria-hidden="true">{{ mb_substr($ticker, 0, 2) }}</span>
@endif
