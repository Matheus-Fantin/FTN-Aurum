<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CarteiraService;
use Illuminate\Console\Command;

class FotografarCarteiras extends Command
{
    protected $signature = 'carteira:fotografar';

    protected $description = 'Guarda o valor de hoje da carteira de cada usuário (gráfico de evolução)';

    public function handle(CarteiraService $carteira): int
    {
        User::query()->each(fn (User $user) => $carteira->fotografar($user));
        $this->info('Carteiras registradas.');

        return self::SUCCESS;
    }
}
