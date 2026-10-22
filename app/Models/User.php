<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'meta_aporte_mensal',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'meta_aporte_mensal' => 'float',
            'is_admin' => 'boolean',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    public function dividends(): HasMany
    {
        return $this->hasMany(Dividend::class);
    }

    public function allocationTargets(): HasMany
    {
        return $this->hasMany(AllocationTarget::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PortfolioSnapshot::class);
    }

    /** Metas por classe (percentual), com as metas padrão para classes ainda não salvas. */
    public function metas(): array
    {
        $salvas = $this->allocationTargets()->pluck('percentual', 'classe')->map(fn ($v) => (float) $v)->all();

        return array_merge(config('aurum.metas_padrao'), array_intersect_key($salvas, config('aurum.classes')));
    }

    public function favoritou(string $ticker): bool
    {
        return $this->favorites()->where('ticker', $ticker)->exists();
    }

    /** Alertas que dispararam e ainda não foram vistos (contador do sino). */
    public function alertasNovos(): int
    {
        return $this->priceAlerts()->whereNotNull('disparado_em')->where('visto', false)->count();
    }
}
