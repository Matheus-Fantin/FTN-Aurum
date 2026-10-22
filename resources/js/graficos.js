import Chart from 'chart.js/auto';
import { brl, brlK } from './formato';

// Cores vêm dos tokens CSS, então os gráficos acompanham o tema claro/escuro.
export const css = (nome) => getComputedStyle(document.documentElement).getPropertyValue(nome).trim();

export function opcoesBase(extra = {}) {
    const tick = css('--faint');
    const base = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 350 },
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: css('--surface'),
                titleColor: css('--fg'),
                bodyColor: css('--muted'),
                borderColor: css('--line'),
                borderWidth: 1,
                padding: 10,
                cornerRadius: 10,
                boxPadding: 4,
                usePointStyle: true,
                titleFont: { family: 'Figtree', weight: '700' },
                bodyFont: { family: 'Figtree' },
            },
        },
        scales: {
            x: { grid: { display: false }, border: { color: css('--line') }, ticks: { color: tick, font: { family: 'IBM Plex Mono', size: 11 }, maxRotation: 0, autoSkipPadding: 12 } },
            y: { grid: { color: css('--grid') }, border: { display: false }, ticks: { color: tick, font: { family: 'IBM Plex Mono', size: 11 }, callback: (v) => brlK(v) } },
        },
    };
    return { ...base, ...extra, plugins: { ...base.plugins, ...(extra.plugins || {}), tooltip: { ...base.plugins.tooltip, ...(extra.plugins?.tooltip || {}) } } };
}

export const tooltipValor = (formato = brl, eixo = 'y') => ({ plugins: { tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${formato(c.parsed[eixo])}` } } } });

const nomeMes = (m) => { const [y, mo] = m.split('-').map(Number); return new Date(y, mo - 1, 1).toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' }).replace('.', ''); };
const diaMes = (d) => d.split('-').reverse().slice(0, 2).join('/');
const escalaReais = () => ({ ...opcoesBase().scales, y: { ...opcoesBase().scales.y, ticks: { ...opcoesBase().scales.y.ticks, callback: (v) => brl(v) } } });

// Gráficos das páginas (dados vêm do servidor em data-dados).
const construtores = {
    // Preço de fechamento de um ativo (página do ativo).
    historico(canvas, d) {
        const subiu = d.length > 1 && d[d.length - 1].fechamento >= d[0].fechamento;
        const cor = css(subiu ? '--good' : '--bad');
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: d.map((x) => diaMes(x.data)),
                datasets: [{ label: 'Fechamento', data: d.map((x) => x.fechamento), borderColor: cor, backgroundColor: cor + '1a', fill: true, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5, tension: 0.2 }],
            },
            options: opcoesBase({ ...tooltipValor(), scales: escalaReais() }),
        });
    },
    // Valor da carteira × quanto foi investido, dia a dia.
    evolucao(canvas, d) {
        const ouro = css('--gold-line');
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: d.map((x) => diaMes(x.data)),
                datasets: [
                    { label: 'Valor da carteira', data: d.map((x) => x.valor), borderColor: ouro, backgroundColor: ouro + '22', fill: true, borderWidth: 2, pointRadius: d.length < 3 ? 4 : 0, pointHoverRadius: 5, tension: 0.25 },
                    { label: 'Investido', data: d.map((x) => x.custo), borderColor: css('--faint'), borderWidth: 1.5, borderDash: [5, 4], pointRadius: 0, pointHoverRadius: 4, tension: 0.25 },
                ],
            },
            options: opcoesBase(tooltipValor()),
        });
    },
    dividendos(canvas, d) {
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: d.map((x) => nomeMes(x.mes)),
                datasets: [{ label: 'Proventos', data: d.map((x) => x.valor), backgroundColor: css('--gold-line'), borderRadius: 4, borderSkipped: 'start' }],
            },
            options: opcoesBase(tooltipValor()),
        });
    },
};

const ativos = new Map();

export function desenharGraficos() {
    document.querySelectorAll('canvas[data-grafico]').forEach((canvas) => {
        const fn = construtores[canvas.dataset.grafico];
        if (!fn) return;
        ativos.get(canvas)?.destroy();
        ativos.set(canvas, fn(canvas, JSON.parse(canvas.dataset.dados || '[]')));
    });
}

// Redesenha os gráficos quando o tema muda.
export function aoMudarTema(callback) {
    window.addEventListener('aurum:tema', callback);
}

export function iniciarGraficos() {
    desenharGraficos();
    aoMudarTema(desenharGraficos);
    document.fonts?.ready.then(desenharGraficos);
}

export { Chart };
