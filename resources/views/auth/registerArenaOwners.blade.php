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

            @php
                // Erros que NÃO pertencem a nenhuma etapa (ex.: falha geral do envio).
                // Os demais aparecem dentro da própria etapa, junto do campo — ver o
                // partial _erros-da-etapa. Um bloco único aqui fora "seguia" o usuário
                // por todas as etapas, mesmo depois de ele já ter corrigido o campo.
                $camposDasEtapas = [
                    'name', 'email', 'owner_phone', 'password', 'password_confirmation',
                    'company_name', 'tax_id',
                    'name_arena', 'description', 'address_rua', 'address_bairro',
                    'address_numero', 'phone', 'email_arena',
                    'horarios', 'pagamentos', 'cancellation_fee_value',
                    'cancellation_fee_window_hours', 'quadras', 'terms',
                ];
                $errosSoltos = collect($errors->keys())
                    ->reject(fn ($campo) => in_array(explode('.', $campo)[0], $camposDasEtapas, true))
                    ->flatMap(fn ($campo) => $errors->get($campo));
            @endphp

            @if ($errosSoltos->isNotEmpty())
                <div class="alert alert-danger">
                    <strong>Não foi possível concluir o cadastro.</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errosSoltos as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif ($errors->any())
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Faltou corrigir alguns campos.</strong>
                    Abrimos a etapa onde está o primeiro erro — ele aparece marcado em vermelho.
                </div>
            @endif

            <form method="POST" action="{{ route('register.arena.owners.store') }}" data-wizard novalidate
                  enctype="multipart/form-data">
                @csrf

                {{-- ETAPA 1 — Você e sua empresa --}}
                <div data-etapa data-campos="name,email,owner_phone,password,password_confirmation,company_name,tax_id" class="d-none">

                    @include("auth._erros-da-etapa", ["campos" => explode(",", "name,email,owner_phone,password,password_confirmation,company_name,tax_id")])

                    @if (! empty($ehClienteLogado))
                        {{-- Cliente logado escolhe: virar proprietário com a conta atual
                             (encerra o cliente) ou criar dados de acesso novos. --}}
                        <div class="alert alert-info">
                            <p class="fw-semibold mb-2">Você já tem uma conta de cliente. Como quer cadastrar sua arena?</p>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="modo_conta" id="modo_atual"
                                       value="atual" data-modo-conta
                                       {{ old('modo_conta', 'atual') === 'atual' ? 'checked' : '' }}>
                                <label class="form-check-label" for="modo_atual">
                                    <strong>Usar minha conta atual</strong>
                                    ({{ auth()->user()->email }}) — viro proprietário com esta conta.
                                    Minha conta de cliente é encerrada.
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="modo_conta" id="modo_novos"
                                       value="novos" data-modo-conta
                                       {{ old('modo_conta') === 'novos' ? 'checked' : '' }}>
                                <label class="form-check-label" for="modo_novos">
                                    <strong>Criar novos dados de acesso</strong> — mantenho minha conta de
                                    cliente e crio uma conta de proprietário separada (com outro e-mail).
                                </label>
                            </div>
                        </div>
                    @endif

                    <div data-bloco-acesso>
                    <h2 class="h5 fw-bold mb-3">Seus dados de acesso</h2>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="name">Nome completo</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                   value="{{ old('name') }}" maxlength="100" required autofocus autocomplete="name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                                   value="{{ old('email') }}" maxlength="150" required autocomplete="username">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="owner_phone">Telefone</label>
                            <input type="text" class="form-control @error('owner_phone') is-invalid @enderror" id="owner_phone" name="owner_phone"
                                   value="{{ old('owner_phone') }}" data-mask="telefone" inputmode="numeric"
                                   placeholder="(11) 91234-5678" maxlength="20" required autocomplete="tel">
                            @error('owner_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password">Senha</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                                   minlength="8" required autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Mínimo de 8 caracteres.</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password_confirmation">Confirmar senha</label>
                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation"
                                   name="password_confirmation" minlength="8" required autocomplete="new-password">
                            @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    </div>{{-- /data-bloco-acesso --}}

                    <hr class="my-4">
                    <h2 class="h5 fw-bold mb-3">Sua empresa</h2>

                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label" for="company_name">Nome da empresa</label>
                            <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name"
                                   value="{{ old('company_name') }}" maxlength="150" required>
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label" for="tax_id">CPF ou CNPJ</label>
                            <input type="text" class="form-control @error('tax_id') is-invalid @enderror" id="tax_id" name="tax_id"
                                   value="{{ old('tax_id') }}" data-mask="cpfcnpj" inputmode="numeric"
                                   placeholder="000.000.000-00" required>
                            @error('tax_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="form-text">Pessoa física (CPF) ou jurídica (CNPJ).</div>
                            @enderror
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
                <div data-etapa data-campos="name_arena,description,fotos,address_rua,address_bairro,address_numero,phone,email_arena" class="d-none">

                    @include("auth._erros-da-etapa", ["campos" => explode(",", "name_arena,description,fotos,address_rua,address_bairro,address_numero,phone,email_arena")])
                    <h2 class="h5 fw-bold mb-3">Dados da arena</h2>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="name_arena">Nome da arena</label>
                            <input type="text" class="form-control @error('name_arena') is-invalid @enderror" id="name_arena" name="name_arena"
                                   value="{{ old('name_arena') }}" maxlength="120" required>
                            @error('name_arena')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">
                                Descrição <span class="text-muted fw-normal">(opcional)</span>
                            </label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      maxlength="300" placeholder="Conte o que sua arena tem de melhor.">{{ old('description') }}</textarea>
                            <div class="form-text">Até 300 caracteres.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="fotos">
                                Fotos da arena <span class="text-muted fw-normal">(opcional · até 15)</span>
                            </label>
                            <input type="file" class="form-control @error('fotos.*') is-invalid @enderror"
                                   id="fotos" name="fotos[]"
                                   accept="image/jpeg,image/png,image/webp" multiple>
                            @error('fotos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                JPG, PNG ou WEBP. Aparecem no carrossel do card e da página da arena.
                                <strong>Prefira fotos horizontais (paisagem), proporção 16:9</strong>
                                (ex.: 1920×1080). Você também pode adicionar/reordenar depois em
                                "Fotos da arena".
                            </div>
                        </div>

                        <div class="col-12 col-md-7">
                            <label class="form-label" for="address_rua">Rua</label>
                            <input type="text" class="form-control @error('address_rua') is-invalid @enderror" id="address_rua" name="address_rua"
                                   value="{{ old('address_rua') }}" maxlength="120" required>
                            @error('address_rua')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label" for="address_numero">Número</label>
                            <input type="text" class="form-control @error('address_numero') is-invalid @enderror" id="address_numero" name="address_numero"
                                   value="{{ old('address_numero') }}" maxlength="15" required>
                            @error('address_numero')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label" for="address_bairro">Bairro</label>
                            <input type="text" class="form-control @error('address_bairro') is-invalid @enderror" id="address_bairro" name="address_bairro"
                                   value="{{ old('address_bairro') }}" maxlength="120" required>
                            @error('address_bairro')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                                   value="{{ old('phone') }}" data-mask="telefone" inputmode="numeric"
                                   placeholder="(11) 3456-7890" maxlength="20" required>
                            @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
                            <input type="email" class="form-control @error('email_arena') is-invalid @enderror" id="email_arena" name="email_arena"
                                   value="{{ old('email_arena') }}" maxlength="150" required>
                            @error('email_arena')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

                    @include("auth._erros-da-etapa", ["campos" => explode(",", "horarios,pagamentos,charges_cancellation_fee,cancellation_fee_type,cancellation_fee_value,cancellation_fee_mode,cancellation_fee_window_hours")])
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

                    @include("auth._erros-da-etapa", ["campos" => explode(",", "quadras,terms")])
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

    /*
     * Cliente logado: ao escolher "usar minha conta atual", esconde e DESABILITA
     * os campos de dados de acesso (o assistente pula campos disabled e eles não
     * são enviados — o servidor reaproveita a conta logada). Em "novos dados",
     * mostra e reabilita.
     */
    (function () {
        var radios = document.querySelectorAll('[data-modo-conta]');
        var bloco = document.querySelector('[data-bloco-acesso]');
        if (!radios.length || !bloco) return;

        var campos = bloco.querySelectorAll('input, select, textarea');

        function aplicar() {
            var escolhido = document.querySelector('[data-modo-conta]:checked');
            var usarAtual = escolhido && escolhido.value === 'atual';

            bloco.classList.toggle('d-none', usarAtual);
            campos.forEach(function (c) { c.disabled = usarAtual; });
        }

        radios.forEach(function (r) { r.addEventListener('change', aplicar); });
        aplicar();
    })();
</script>
<script src="/js/masks.js" defer></script>
<script src="/js/form-wizard.js" defer></script>
@endsection
