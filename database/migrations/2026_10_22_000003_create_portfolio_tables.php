<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ticker', 20);
            $table->string('classe', 10);
            $table->decimal('quantidade', 20, 8)->default(0);
            $table->decimal('preco_medio', 18, 6)->default(0);
            $table->decimal('cotacao', 18, 6)->default(0);
            $table->decimal('variacao_dia', 9, 4)->nullable();
            $table->boolean('atualizacao_automatica')->default(true);
            $table->timestamp('cotacao_atualizada_em')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'ticker']);
        });

        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 10); // compra | venda
            $table->date('data');
            $table->decimal('quantidade', 20, 8);
            $table->decimal('preco', 18, 6);
            $table->decimal('taxas', 12, 2)->default(0);
            $table->decimal('resultado', 15, 2)->nullable(); // lucro/prejuízo realizado na venda
            $table->timestamps();

            $table->index(['user_id', 'data']);
        });

        Schema::create('dividends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticker', 20);
            $table->string('tipo', 20); // dividendo | jcp | rendimento
            $table->date('data');
            $table->decimal('valor', 15, 2);
            $table->timestamps();

            $table->index(['user_id', 'data']);
        });

        Schema::create('allocation_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('classe', 10);
            $table->decimal('percentual', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'classe']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ticker', 20);
            $table->timestamps();

            $table->unique(['user_id', 'ticker']);
        });

        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ticker', 20);
            $table->string('condicao', 10); // acima | abaixo
            $table->decimal('preco', 18, 6);
            $table->timestamp('disparado_em')->nullable();
            $table->decimal('preco_disparo', 18, 6)->nullable();
            $table->boolean('visto')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'disparado_em']);
        });

        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('data');
            $table->decimal('valor', 15, 2);
            $table->decimal('custo', 15, 2);
            $table->timestamps();

            $table->unique(['user_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
        Schema::dropIfExists('price_alerts');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('allocation_targets');
        Schema::dropIfExists('dividends');
        Schema::dropIfExists('operations');
        Schema::dropIfExists('assets');
    }
};
