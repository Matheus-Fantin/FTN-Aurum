<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use Illuminate\Foundation\Http\FormRequest;

/** Metas de alocação por classe (somando 100%) e meta de aporte mensal. */
class MetasRequest extends FormRequest
{
    use NormalizaNumeros;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarNumeros([...array_map(fn ($c) => "metas.$c", array_keys(config('aurum.classes'))), 'meta_aporte_mensal']);
    }

    public function rules(): array
    {
        $regras = ['metas' => ['required', 'array'], 'meta_aporte_mensal' => ['nullable', 'numeric', 'min:0', 'max:100000000']];
        foreach (array_keys(config('aurum.classes')) as $classe) {
            $regras["metas.$classe"] = ['required', 'numeric', 'between:0,100'];
        }

        return $regras;
    }

    public function attributes(): array
    {
        return ['meta_aporte_mensal' => 'meta de aporte'];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $soma = array_sum(array_map('floatval', (array) $this->input('metas')));
                if (abs($soma - 100) > 0.01) {
                    $validator->errors()->add('metas', 'As metas precisam somar 100%. Hoje somam '.pct($soma, 0).'.');
                }
            },
        ];
    }
}
