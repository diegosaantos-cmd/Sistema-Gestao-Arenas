<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="flex justify-end mb-3">
            <a href="{{ auth()->check() ? route('dashboard') : url('/') }}"
               class="inline-flex items-center justify-center w-9 h-9 rounded-full text-2xl leading-none text-gray-500 hover:text-gray-900 hover:bg-gray-100"
               aria-label="Fechar formulário" title="Fechar">
                &times;
            </a>
        </div>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register.arena.owners.store') }}">
            @csrf

            <div>
                <x-label for="name" value="{{ __('Nome') }}" />
                <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            </div>

            <div class="mt-4">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="company_name" value="{{ __('Nome da Empresa') }}" />
                <x-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required />
            </div>

            <div class="mt-4">
                <x-label for="tax_id" value="{{ __('CPF / CNPJ') }}" />
                <x-input id="tax_id" class="block mt-1 w-full" type="text" name="tax_id" :value="old('tax_id')" required />
            </div>

            <div class="mt-4">
                <x-label for="name_arena" value="{{ __('Nome da Arena') }}" />
                <x-input id="name_arena" class="block mt-1 w-full" type="text" name="name_arena" :value="old('name_arena')" required />
            </div>

            <div class="mt-4">
                <x-label for="description" value="{{ __('Descrição') }}" />
                <x-input id="description" class="block mt-1 w-full" type="text" name="description" :value="old('description')"/>
            </div>

            <div class="mt-4">
                <x-label for="address_rua" value="{{ __('Rua') }}" />
                <x-input id="address_rua" class="block mt-1 w-full" type="text" name="address_rua" :value="old('address_rua')" required />
            </div>

            <div class="mt-4">
                <x-label for="address_bairro" value="{{ __('Bairro') }}" />
                <x-input id="address_bairro" class="block mt-1 w-full" type="text" name="address_bairro" :value="old('address_bairro')" required />
            </div>

            <div class="mt-4">
                <x-label for="address_numero" value="{{ __('Número') }}" />
                <x-input id="address_numero" class="block mt-1 w-full" type="text" name="address_numero" :value="old('address_numero')" required />
            </div>

            <div class="mt-4">
                <x-label for="phone" value="{{ __('Telafone de Contato da Arena') }}" />
                <x-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required />
            </div>

            <div class="mt-4">
                <x-label for="email_arena" value="{{ __('Email da sua Arena') }}" />
                <x-input id="email_arena" class="block mt-1 w-full" type="email" name="email_arena" :value="old('email_arena')" required autocomplete="username" />
            </div>

            @include('arenas.partials.business-hours')

            @include('arenas.partials.courts')

            @include('arenas.partials.payment-methods')

            <div class="mt-4">
                <x-label for="password" value="{{ __('Senha') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirmar Senha') }}" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="mt-4">
                    <x-label for="terms">
                        <div class="flex items-center">
                            <x-checkbox name="terms" id="terms" required />
                        </div>
                    </x-label>
                </div>
            @endif

            <div class="flex items-center justify-end mt-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-button class="ms-4">
                    {{ __('Casdastrar') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
