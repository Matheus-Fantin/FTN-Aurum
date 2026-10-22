<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ativos da B3 (lista do mercado), compartilhados por todos os usuários. */
    public function up(): void
    {
        Schema::create('market_assets', function (Blueprint $table) {
            $table->id();
            $table->string('ticker', 20)->unique();
            $table->string('nome')->default('');
            $table->string('classe', 10); // acao | fii | etf | bdr
            $table->string('setor')->nullable();
            $table->string('logo')->nullable();
            $table->decimal('preco', 18, 6)->nullable();
            $table->decimal('variacao', 9, 4)->nullable();
            $table->decimal('volume', 22, 2)->nullable();       // volume financeiro do dia (R$)
            $table->decimal('valor_mercado', 22, 2)->nullable();
            $table->timestamp('atualizado_em')->nullable();

            // Indicadores calculados a partir do histórico e dos fundamentos.
            $table->decimal('pl', 12, 4)->nullable();
            $table->decimal('dy', 9, 4)->nullable();             // dividend yield em 12 meses (%)
            $table->decimal('media_20', 18, 6)->nullable();
            $table->decimal('media_50', 18, 6)->nullable();
            $table->decimal('maxima_periodo', 18, 6)->nullable();
            $table->decimal('minima_periodo', 18, 6)->nullable();
            $table->decimal('volume_medio', 22, 2)->nullable();
            $table->decimal('variacao_mes', 9, 4)->nullable();
            $table->boolean('tendencia_alta')->default(false);
            $table->decimal('radar_nota', 4, 1)->nullable();
            $table->json('radar_sinais')->nullable();
            $table->json('historico')->nullable();               // [[data, fechamento], ...]
            $table->timestamp('fundamentos_em')->nullable();
            $table->timestamps();

            $table->index(['classe', 'volume']);
            $table->index('radar_nota');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_assets');
    }
};
