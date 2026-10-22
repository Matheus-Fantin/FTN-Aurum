{{-- Olho de privacidade: esconde ou mostra todos os valores em reais (fica salvo neste aparelho). --}}
<button type="button" data-alternar-valores aria-pressed="false" aria-label="Ocultar valores" {{ $attributes->merge(['class' => 'btn-olho']) }}>
    <x-icone nome="eye" class="olho-aberto" />
    <x-icone nome="eye-off" class="olho-fechado" />
</button>
