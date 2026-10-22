# FTN Aurum

Organização da sua carteira na bolsa, pensada primeiro para o celular: painel em blocos, pesquisa de ações com filtros combináveis, maiores altas e baixas do dia, radar de possíveis altas, favoritos com alertas de preço e controle de compras, vendas e proventos.

Feito com a mesma base do FantInvest: **Laravel 12**, **Breeze** (login), **Blade + Alpine.js**, **Tailwind CSS 4**, **Vite**, **SQLite** e **PHPUnit**. Os gráficos usam **Chart.js** e as cotações vêm da **brapi.dev**.

## Telas

| Tela | O que faz |
|---|---|
| Painel | Patrimônio com o olho para esconder valores, variação do dia, maior alta e maior baixa, Ibovespa e dólar, ranking do dia (altas, baixas, volume), radar, próximo provento e suas ações que mais se mexeram. |
| Buscar | Busca por nome, código ou setor com sugestões enquanto digita. Filtros em blocos que se combinam: dividendos acima de 7%, P/L até 7, tendência de alta, nota do radar, empresas grandes, muito negociadas. Filtro por tipo (ações, FIIs, ETFs, BDRs) e ordenação. |
| Ativo | Gráfico de preço, DY, P/L, mínima e máxima do período, sinais do radar, sua posição, compra e venda, alerta de preço e favorito. |
| Carteira | Patrimônio, resultado, aporte do mês com meta, classes (tocar filtra as posições), metas de alocação, evolução e últimas operações (com desfazer). |
| Favoritos | Ativos favoritados com preço e variação, e alertas de preço (os disparados aparecem no sino). |
| Proventos | Dividendos, JCP e rendimentos: a receber, recebidos, média mensal e yield on cost. |

### Detalhes de uso

- **Olho na senha** em todas as telas de senha (login, cadastro, redefinição, perfil) e aviso de **Caps Lock ativado**.
- **Olho de privacidade**: troca todos os valores em reais por `R$ •••••` e desfoca os gráficos de valor. A escolha fica salva no aparelho e é aplicada antes de a página aparecer.
- Variações sempre com seta e sinal (▲ +, ▼ −), não só com cor.
- Horário da última atualização e aviso de atraso das cotações.
- Barra de navegação flutuante no celular, com botões de 44 px ou mais.
- Tema claro, escuro ou do sistema.

## Radar de possíveis altas

Nota de 0 a 10 somando sinais (pesos em `config/aurum.php`):

| Sinal | Peso |
|---|---|
| Preço acima da média de 20 dias | 2 |
| Média de 20 dias acima da de 50 | 2 |
| Volume do dia acima da média | 1,5 |
| Perto da máxima do período (até 5%) | 1,5 |
| Subiu no último mês | 1,5 |
| P/L entre 0 e 15 | 1,5 |

É um filtro para estudar, **não é recomendação de compra**.

## Dados de mercado

Tudo vem da [brapi.dev](https://brapi.dev). Crie um token gratuito em https://brapi.dev/dashboard e coloque no `.env`: `BRAPI_TOKEN=seu_token`.

| Comando | Quando roda | O que faz |
|---|---|---|
| `php artisan mercado:sincronizar` | a cada 30 min no pregão | Lista do mercado (preço, variação, volume) dos mais negociados de cada tipo e confere os alertas. |
| `php artisan mercado:sincronizar --fundamentos` | 19h nos dias úteis | Também calcula médias, DY, P/L e a nota do radar (uma requisição por ativo, começando pelos mais negociados). |
| `php artisan cotacoes:atualizar` | a cada 15 min no pregão | Cotação dos ativos da sua carteira. |
| `php artisan carteira:fotografar` | 19h30 | Valor do dia de cada carteira (gráfico de evolução). |

Os horários estão em `routes/console.php`. Para rodar o agendador: `php artisan schedule:work` (já incluído no `composer run dev`). Em produção, adicione ao cron: `* * * * * cd /caminho/do/ftn-aurum && php artisan schedule:run >> /dev/null 2>&1`.

Gasto aproximado da cota gratuita (15 mil requisições/mês): a lista usa 6 requisições por rodada (3 tipos × 2 páginas) e os indicadores usam até 60 por dia. Ajuste com `MERCADO_PAGINAS` e `MERCADO_FUNDAMENTOS_POR_RODADA`.

Primeira carga logo depois de instalar:

```bash
php artisan mercado:sincronizar --fundamentos
```

## Como rodar na sua máquina

Pré-requisitos: PHP 8.2+ (com `pdo_sqlite`), Composer e Node.js 20+.

```bash
git clone https://github.com/Matheus-Fantin/ftn-aurum.git
cd ftn-aurum
composer run setup        # instala dependências, cria .env, gera a chave, migra o banco e compila o front
php artisan db:seed       # opcional: conta de demonstração com carteira e mercado de exemplo
composer run dev          # servidor, fila, logs, Vite e agendador
```

Abra http://localhost:8000. Conta de demonstração (depois do `db:seed`): **demo@ftnaurum.test** / senha **password**. Os preços da demonstração são ilustrativos.

## Cadastro por convite

A primeira conta criada vira administradora. Depois disso, novas contas só entram por convite: em **Convites** você gera um link (opcionalmente preso a um e-mail) que vale uma vez. Para liberar o cadastro para qualquer pessoa, use `CADASTRO_ABERTO=true` no `.env`. Para dar acesso de administrador a uma conta:

```bash
php artisan usuario:admin seu@email.com
```

## Estrutura

```
app/
  Console/Commands/     mercado:sincronizar, cotacoes:atualizar, carteira:fotografar, usuario:admin
  Http/Controllers/     Painel, Buscar, Ativo, Carteira, Favoritos, Proventos, Cotacao
  Http/Requests/        OperacaoRequest, MetasRequest; Concerns/NormalizaNumeros aceita "1.234,56"
  Models/               Asset (posição), Operation, Dividend, MarketAsset (mercado), Favorite, PriceAlert, PortfolioSnapshot
  Services/             CarteiraService (resumo, operações, desfazer, alertas, foto diária)
  Services/Mercado/     MercadoService (lista, indicadores, rankings), Indicadores, Radar
  Services/Cotacoes/    BrapiCliente e CotacaoService (com cache)
config/aurum.php        Classes, metas padrão, filtros da busca, limites e pesos do radar
config/cotacoes.php     brapi, páginas da lista, rodadas de indicadores e cache
resources/
  css/app.css           Tokens de cor (claro/escuro, verde e dourado) e o layout em blocos
  js/privacidade.js     Olho de esconder valores
  js/graficos.js        Gráficos (Chart.js)
  views/                painel, buscar, ativo, carteira, favoritos, proventos
```

## Testes

```bash
php artisan test
```

## Deploy no Laravel Cloud

1. **New application** → importe o repositório `Matheus-Fantin/ftn-aurum`.
2. Adicione um banco Laravel Serverless Postgres (ou MySQL) ao ambiente. O SQLite não serve em produção: o disco é apagado a cada deploy.
3. No **App cluster**, ligue o **Scheduler** (lista do mercado, cotações e radar).
4. **Build commands:** `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`, `php artisan optimize`. **Deploy command:** `php artisan migrate --force`.
5. **Variáveis:** `APP_NAME="FTN Aurum"`, `APP_LOCALE=pt_BR`, `APP_TIMEZONE=America/Sao_Paulo`, `QUEUE_CONNECTION=sync`, `CADASTRO_ABERTO=false`, `BRAPI_TOKEN=seu_token`.
6. Depois do deploy, abra `/register` e crie a sua conta (a primeira vira administradora). Rode uma vez `php artisan mercado:sincronizar --fundamentos`.

Não rode `php artisan db:seed` em produção: ele cria a conta de demonstração com a senha `password`.
