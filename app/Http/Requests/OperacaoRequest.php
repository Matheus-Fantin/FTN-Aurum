<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizaNumeros;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Compra ou venda de um ativo (página do ativo ou botão "+ Operação" da carteira). */
class OperacaoRequest extends FormRequest
{
    use NormalizaNumeros;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizarNumeros(['quantidade', 'preco', 'taxas']);
        if ($this->has('ticker')) {
            $this->merge(['ticker' => mb_strtoupper(preg_replace('/[^A-Za-z0-9.]/', '', (string) $this->input('ticker')))]);
        }
    }

    public function rules(): array
    {
        return [
            'ticker' => [Rule::requiredIf(fn () => $this->route('ticker') === null), 'nullable', 'string', 'max:20'],
            'tipo' => ['required', Rule::in(['compra', 'venda'])],
            'classe' => ['nullable', Rule::in(array_keys(config('aurum.classes')))],
            'data' => ['required', 'date', 'before_or_equal:today'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'preco' => ['required', 'numeric', 'gt:0'],
            'taxas' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['preco' => 'preço', 'ticker' => 'código do ativo'];
    }
}
