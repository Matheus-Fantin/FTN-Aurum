import './bootstrap';

import Alpine from 'alpinejs';
import { iniciarGraficos } from './graficos';
import { iniciarTema } from './tema';
import { iniciarPrivacidade } from './privacidade';
import * as formato from './formato';
import cotacoesAoVivo from './cotacoes';
import buscaAtivos from './busca-ativos';

window.Alpine = Alpine;
window.fmt = formato;

Alpine.data('cotacoesAoVivo', cotacoesAoVivo);
Alpine.data('buscaAtivos', buscaAtivos);

// Campos em reais: ao sair do campo, mostra 1.234,56 (ponto no milhar, vírgula nos centavos).
document.addEventListener('focusout', (e) => {
    const campo = e.target;
    if (!campo.matches?.('[data-moeda]') || campo.value.trim() === '') return;
    const formatado = formato.moeda(formato.parseNum(campo.value));
    if (formatado !== campo.value) {
        campo.value = formatado;
        campo.dispatchEvent(new Event('input', { bubbles: true }));
    }
});

iniciarTema();
iniciarPrivacidade();
Alpine.start();
iniciarGraficos();
