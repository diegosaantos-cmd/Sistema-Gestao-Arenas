{{--
    Formas de pagamento aceitas pela arena.

    Reutilizado pelos formulários de "Nova Arena" e "Cadastro de Proprietário".
    As opções vêm da tabela payment_methods (seedada em PaymentMethodSeeder),
    então para adicionar/remover uma forma de pagamento NÃO se mexe aqui:
    basta atualizar o seeder (e o enum da migration, se for um tipo novo).

    Envia os IDs marcados em pagamentos[] -> salvos no pivot arena_payment_methods.
--}}

@once
    <link rel="stylesheet" href="/css/payment-methods.css">
@endonce

@php
    $paymentMethods = $paymentMethods
        ?? \App\Models\PaymentMethod::where('active', true)->get();

    $marcados = collect(old('pagamentos', []));
@endphp

<div class="pm">

    <h5 class="pm__title">Formas de Pagamento Aceitas</h5>
    <h6 class="pm__subtitle">Precisa Informar ao menos uma forma de pagamento</h6>

    <div class="pm__options">
        @foreach ($paymentMethods as $method)
            <label class="pm__option">
                <input type="checkbox"
                       name="pagamentos[]"
                       value="{{ $method->id }}"
                       {{ $marcados->contains($method->id) ? 'checked' : '' }}>
                <span>{{ $method->label }}</span>
            </label>
        @endforeach
    </div>

    @error('pagamentos')
        <p class="pm__error">{{ $message }}</p>
    @enderror

</div>
