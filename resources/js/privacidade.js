// Olho de privacidade: esconde todos os valores em reais (R$ •••••) e mostra de novo.
// A escolha fica salva neste aparelho; o <head> aplica antes de pintar a página.
const CHAVE = 'aurum.ocultar';

function aplicar(oculto) {
    document.documentElement.classList.toggle('ocultar', oculto);
    document.querySelectorAll('[data-alternar-valores]').forEach((botao) => {
        botao.setAttribute('aria-pressed', String(oculto));
        botao.setAttribute('aria-label', oculto ? 'Mostrar valores' : 'Ocultar valores');
    });
    document.querySelectorAll('[data-valores-rotulo]').forEach((el) => (el.textContent = oculto ? 'Mostrar valores' : 'Ocultar valores'));
}

export function iniciarPrivacidade() {
    aplicar(document.documentElement.classList.contains('ocultar'));
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-alternar-valores]')) return;
        const oculto = !document.documentElement.classList.contains('ocultar');
        try { localStorage.setItem(CHAVE, oculto ? '1' : '0'); } catch {}
        aplicar(oculto);
    });
}
