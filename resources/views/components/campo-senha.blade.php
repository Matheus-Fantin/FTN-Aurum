@props(['disabled' => false])

{{-- Campo de senha com olho para mostrar/esconder e aviso de Caps Lock. --}}
<div class="senha" x-data="{ ver: false, caps: false }">
    <input @disabled($disabled) type="password" :type="ver ? 'text' : 'password'"
           x-on:keyup="caps = $event.getModifierState && $event.getModifierState('CapsLock')"
           x-on:blur="caps = false"
           {{ $attributes->except('type')->merge(['class' => 'in']) }}>
    <button type="button" class="senha-olho" x-on:click="ver = !ver; $el.previousElementSibling.focus()"
            aria-label="Mostrar senha" :aria-label="ver ? 'Esconder senha' : 'Mostrar senha'" :aria-pressed="ver.toString()" aria-pressed="false">
        <x-icone nome="eye" x-show="!ver" />
        <x-icone nome="eye-off" x-show="ver" x-cloak />
    </button>
    <span class="senha-caps" x-show="caps" x-cloak role="status">Caps Lock ativado</span>
</div>
