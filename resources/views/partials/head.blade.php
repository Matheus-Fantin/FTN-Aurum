<meta charset="utf-8">
<meta name="theme-color" content="#0b7559">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($titulo) ? $titulo.' · ' : '' }}{{ config('app.name', 'FTN Aurum') }}</title>
<script>
    // Aplica o tema salvo antes de pintar a página (evita piscar no tema errado).
    // E esconde os valores antes de pintar, se o olho estava fechado neste aparelho.
    try { if (localStorage.getItem('aurum.ocultar') === '1') document.documentElement.classList.add('ocultar'); } catch (e) {}
    try { const t = localStorage.getItem('aurum.tema'); if (t === 'escuro') document.documentElement.dataset.theme = 'dark'; if (t === 'claro') document.documentElement.dataset.theme = 'light'; } catch (e) {}
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,650&family=Figtree:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
@vite(['resources/css/app.css', 'resources/js/app.js'])
