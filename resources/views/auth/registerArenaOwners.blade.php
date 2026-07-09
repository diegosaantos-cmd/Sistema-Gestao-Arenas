@extends('layouts.main')

@section('title', 'Cadastrar Arena')

@section('content')
<div class="container py-5">
    <div class="card shadow border-0 mx-auto" style="max-width: 900px;">
        <div class="card-body p-4 p-md-5">

            {{-- Fica fora das etapas, então aparece em todas elas. --}}
            <div class="position-relative text-center mb-4">
                <a href="{{ url('/') }}" class="logo-marca">ArenaPlay</a>

                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}"
                   class="btn-close position-absolute top-0 end-0"
                   aria-label="Fechar" title="Fechar"></a>
            </div>

            <div class="text-center mb-4">
                <h1 class="fw-bold mb-1">Cadastre sua arena</h1>
                <p class="text-muted mb-0">
                    Em 4 etapas você cria sua conta e coloca sua arena no ar.
                </p>
            </div>

            {{-- Indicador de etapas --}}
            <ul class="wz-passos">
                <li class="wz-passo" data-indicador>
                    <span class="wz-passo__num">1</span><br>Você e sua empresa
                </li>
                <li class="wz-passo" data-indicador>
                    <span class="wz-passo__num">2</span><br>Sua arena
                </li>
                <li class="wz-passo" data-indicador>
                    <span class="wz-passo__num">3</span><br>Funcionamento
                </li>
                <li class="wz-passo" data-indicador>
                    <span class="wz-passo__num">4</span><br>Quadras
                </li>
            </ul>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Não foi possível concluir o cadastro.</strong>
                    Corrija o que está marcado abaixo:
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.arena.owners.store') }}" data-wizard novalidate>
                @csrf

                {{-- ETAPA 1 — Você e sua empresa --}}
                <div data-etapa data-campos="name,email,owner_phone,password,password_confirmation,company_name,tax_id" class="d-none">
                    <h2 class="h5 fw-bold mb-3">Seus dados de acesso</h2>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="name">Nome completo</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name') }}" maxlength="100" required autofocus autocomplete="name">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="{{ old('email') }}" maxlength="150" required autocomplete="username">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="owner_phone">Telefone</label>
                            <input type="text" class="form-control" id="owner_phone" name="owner_phone"
                                   value="{{ old('owner_phone') }}" data-mask="telefone" inputmode="numeric"
                                   placeholder="(11) 91234-5678" maxlength="20" required autocomplete="tel">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password">Senha</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   minlength="8" required autocomplete="new-password">
                            <div class="form-text">Mínimo de 8 caracteres.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password_confirmation">Confirmar senha</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" minlength="8" required autocomplete="new-password">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h2 class="h5 fw-bold mb-3">Sua empresa</h2>

                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label" for="company_name">Nome da empresa</label>
                            <input type="text" class="form-control" id="company_name" name="company_name"
                                   value="{{ old('company_name') }}" maxlength="150" required>
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label" for="tax_id">CPF ou CNPJ</label>
                            <input type="text" class="form-control" id="tax_id" name="tax_id"
                                   value="{{ old('tax_id') }}" data-mask="cpfcnpj" inputmode="numeric"
                                   placeholder="000.000.000-00" required>
                            <div class="form-text">Pessoa física (CPF) ou jurídica (CNPJ).</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-4">
                        <a href="{{ route('login') }}" class="small text-decoration-none">Já tem conta? Entrar</a>
                        <button type="button" class="btn btn-primary ms-auto" data-avancar>
                            Avançar <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                {{-- ETAPA 2 — Sua arena --}}
                <div data-etapa data-campos="name_arena,description,address_rua,address_bairro,address_numero,phone,email_arena" class="d-none">
                    <h2 class="h5 fw-bold mb-3">Dados da arena</h2>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="name_arena">Nome da arena</label>
                            <input type="text" class="form-control" id="name_arena" name="name_arena"
                                   value="{{ old('name_arena') }}" maxlength="120" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">
                                Descrição <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      maxlength="300" placeholder="Conte o que sua arena tem de melhor.">{{ old('description') }}</textarea>
                            <div class="form-text">Até 300 caracteres.</div>
                        </div>

                        <div class="col-12 col-md-7">
                            <label class="form-label" for="address_rua">Rua</label>
                            <input type="text" class="form-control" id="address_rua" name="address_rua"
                                   value="{{ old('address_rua') }}" maxlength="120" required>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="address_numero">Número</label>
                            <input type="text" class="form-control" id="address_numero" name="address_numero"
                                   value="{{ old('address_numero') }}" maxlength="15" required>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label" for="address_bairro">Bairro</label>
                            <input type="text" class="form-control" id="address_bairro" name="address_bairro"
                                   value="{{ old('address_bairro') }}" maxlength="120" required>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-light border small mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                O telefone e o e-mail da arena <strong>podem ser os mesmos</strong> que você
                                informou na etapa 1. Marque as opções abaixo para reaproveitá-los.
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="phone">Telefone de contato da arena</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                   value="{{ old('phone') }}" data-mask="telefone" inputmode="numeric"
                                   placeholder="(11) 3456-7890" maxlength="20" required>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="usar_meu_telefone"
                                       data-copiar-de="owner_phone" data-copiar-para="phone">
                                <label class="form-check-label small text-muted" for="usar_meu_telefone">
                                    Usar o mesmo telefone que informei
                                </label>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email_arena">E-mail da arena</label>
                            <input type="email" class="form-control" id="email_arena" name="email_arena"
                                   value="{{ old('email_arena') }}" maxlength="150" required>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="usar_meu_email"
                                       data-copiar-de="email" data-copiar-para="email_arena">
                                <label class="form-check-label small text-muted" for="usar_meu_email">
                                    Usar o mesmo e-mail que informei
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- d-grid no celular: botões empilhados e em largura total, pois
                         "Concluir cadastro" não cabe ao lado de "Voltar" numa tela
                         estreita. A partir de 576px voltam a ficar lado a lado. --}}
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-between mt-4">
                        <button type="button" class="btn btn-secondary" data-voltar>
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </button>
                        <button type="button" class="btn btn-primary" data-avancar>
                            Avançar <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                {{-- ETAPA 3 — Funcionamento --}}
                <div data-etapa class="d-none"
                     data-campos="horarios,pagamentos,charges_cancellation_fee,cancellation_fee_type,cancellation_fee_value,cancellation_fee_mode,cancellation_fee_window_hours">
                    <h2 class="h5 fw-bold mb-3">Funcionamento e pagamentos</h2>

                    @include('arenas.partials.business-hours')

                    <hr class="my-4">
                    @include('arenas.partials.payment-methods')

                    <hr class="my-4">
                    @include('arenas.partials.cancellation-fee')

                    {{-- d-grid no celular: botões empilhados e em largura total, pois
                         "Concluir cadastro" não cabe ao lado de "Voltar" numa tela
                         estreita. A partir de 576px voltam a ficar lado a lado. --}}
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-between mt-4">
                        <button type="button" class="btn btn-secondary" data-voltar>
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </button>
                        <button type="button" class="btn btn-primary" data-avancar>
                            Avançar <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                {{-- ETAPA 4 — Quadras e conclusão --}}
                <div data-etapa data-campos="quadras,terms" class="d-none">
                    <h2 class="h5 fw-bold mb-3">Quadras da arena</h2>

                    @include('arenas.partials.courts')

                    <hr class="my-4">

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="terms" id="terms" value="1"
                               {{ old('terms') ? 'checked' : '' }} required>
                        <label class="form-check-label" for="terms">
                            Li e aceito os
                            @if (Route::has('terms.show'))
                                <a href="{{ route('terms.show') }}" target="_blank">termos de uso</a>
                            @else
                                termos de uso
                            @endif
                            @if (Route::has('policy.show'))
                                e a <a href="{{ route('policy.show') }}" target="_blank">política de privacidade</a>
                            @endif
                            do ArenaPlay.
                        </label>
                        @error('terms')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- d-grid no celular: botões empilhados e em largura total, pois
                         "Concluir cadastro" não cabe ao lado de "Voltar" numa tela
                         estreita. A partir de 576px voltam a ficar lado a lado. --}}
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-between mt-4">
                        <button type="button" class="btn btn-secondary" data-voltar>
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Concluir cadastro
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    // Campos que o servidor recusou: o assistente abre a etapa onde está o erro.
    window.__camposComErro = @json($errors->keys());

    /*
     * Validações próprias de cada etapa (índice a partir de 0).
     *
     * Necessárias porque os partials de horários, formas de pagamento e taxa de
     * cancelamento não usam `required` — sem isto, "Avançar" passaria com a
     * etapa 3 inteiramente vazia. Espelham as regras do servidor, que continua
     * sendo a autoridade final.
     */
    window.__validacoesEtapa = {
        // Etapa 3 — funcionamento
        2: function (etapa) {
            var dias = etapa.querySelectorAll('input[name^="horarios"][name$="[aberto]"]');
            var algumDia = false;

            for (var i = 0; i < dias.length; i++) {
                if (!dias[i].checked) continue;
                algumDia = true;

                var linha = dias[i].closest('[data-bh-day]');
                var abre = linha.querySelector('input[name$="[p1_abre]"]');
                var fecha = linha.querySelector('input[name$="[p1_fecha]"]');

                if (!abre.value || !fecha.value) {
                    return 'Informe o horário de abertura e de fechamento dos dias marcados.';
                }
            }

            if (!algumDia) {
                return 'Marque ao menos um dia de funcionamento.';
            }

            if (!etapa.querySelector('input[name="pagamentos[]"]:checked')) {
                return 'Selecione ao menos uma forma de pagamento.';
            }

            var cobraTaxa = etapa.querySelector('input[name="charges_cancellation_fee"]');

            if (cobraTaxa && cobraTaxa.checked) {
                var valor = etapa.querySelector('input[name="cancellation_fee_value"]');

                if (!valor.value || Number(valor.value) <= 0) {
                    return 'Informe o valor da taxa de cancelamento.';
                }

                var modo = etapa.querySelector('select[name="cancellation_fee_mode"]');

                if (modo && modo.value === 'window') {
                    var horas = etapa.querySelector('input[name="cancellation_fee_window_hours"]');

                    if (!horas.value || Number(horas.value) < 1) {
                        return 'Informe quantas horas antes do início a taxa passa a valer.';
                    }
                }
            }

            return null;
        },

        // Etapa 4 — quadras
        3: function (etapa) {
            var quadras = etapa.querySelectorAll('[data-court-row]');

            if (!quadras.length) {
                return 'Cadastre ao menos uma quadra.';
            }

            for (var i = 0; i < quadras.length; i++) {
                if (!quadras[i].querySelector('input[name*="[esportes]"]:checked')) {
                    return 'Selecione ao menos um esporte em cada quadra.';
                }
            }

            return null;
        }
    };

    /* Atalho: copia o telefone/e-mail do proprietário para os campos da arena. */
    (function () {
        function vincular(caixa) {
            var origem = document.getElementById(caixa.getAttribute('data-copiar-de'));
            var destino = document.getElementById(caixa.getAttribute('data-copiar-para'));
            if (!origem || !destino) return;

            if (caixa.checked) {
                destino.value = origem.value;
                destino.readOnly = true;
                destino.classList.add('bg-light');
            } else {
                destino.readOnly = false;
                destino.classList.remove('bg-light');
            }
        }

        document.addEventListener('change', function (evento) {
            var caixa = evento.target;
            if (caixa.hasAttribute && caixa.hasAttribute('data-copiar-de')) vincular(caixa);
        });

        // Se a pessoa voltar e mudar o dado de origem, o destino acompanha.
        document.addEventListener('input', function (evento) {
            document.querySelectorAll('[data-copiar-de]').forEach(function (caixa) {
                if (caixa.checked && caixa.getAttribute('data-copiar-de') === evento.target.id) {
                    vincular(caixa);
                }
            });
        });
    })();
</script>
<script src="/js/masks.js" defer></script>
<script src="/js/form-wizard.js" defer></script>
@endsection
