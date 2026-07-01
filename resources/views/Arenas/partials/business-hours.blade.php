{{--
    Campos de horário de funcionamento da arena.

    Reutilizado pelos formulários de "Nova Arena" e "Cadastro de Proprietário".
    O comportamento (mostrar/esconder os horários ao marcar o dia) fica em
    public/js/business-hours.js e o estilo em public/css/business-hours.css.

    Para alterar dias, períodos ou textos, edite APENAS este arquivo.
--}}

@once
    <link rel="stylesheet" href="/css/business-hours.css">
@endonce

@php
    $dias = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    // Valores atuais (modo edição). Vazio nos formulários de cadastro.
    $horariosAtuais = $horariosAtuais ?? [];
@endphp

<div class="bh" data-business-hours>

    <h5 class="bh__title">Horário de Funcionamento</h5>

    <p class="bh__hint">
        Marque <strong>ao menos um dia</strong> em que a arena abre e informe os
        horários. Preencha o <strong>2º período</strong> apenas se ela fecha para
        o almoço.
    </p>

    <p class="bh__hint">
        💡 <strong>Dica:</strong> use horários em <strong>hora cheia</strong>
        (ex.: 08:00, 22:00) para os agendamentos ficarem certinhos de 1 em 1 hora.
        Para abrir <strong>até a meia-noite</strong>, use o fechamento
        <strong>23:59</strong>. A arena precisa abrir e fechar <strong>no mesmo dia</strong>.
    </p>

    @foreach ($dias as $num => $nome)
        <div class="bh__row {{ old("horarios.$num.aberto", $horariosAtuais[$num]['aberto'] ?? false) ? 'is-open' : '' }}" data-bh-day>

            <label class="bh__day">
                <input type="checkbox"
                       name="horarios[{{ $num }}][aberto]"
                       value="1"
                       data-bh-toggle
                       {{ old("horarios.$num.aberto", $horariosAtuais[$num]['aberto'] ?? false) ? 'checked' : '' }}>
                <span>{{ $nome }}</span>
            </label>

            <div class="bh__times" data-bh-times>

                <div class="bh__period">
                    <input type="time"
                           name="horarios[{{ $num }}][p1_abre]"
                           value="{{ old("horarios.$num.p1_abre", $horariosAtuais[$num]['p1_abre'] ?? '') }}"
                           aria-label="{{ $nome }} - abre">
                    <span class="bh__sep">às</span>
                    <input type="time"
                           name="horarios[{{ $num }}][p1_fecha]"
                           value="{{ old("horarios.$num.p1_fecha", $horariosAtuais[$num]['p1_fecha'] ?? '') }}"
                           aria-label="{{ $nome }} - fecha">
                </div>

                <div class="bh__period bh__period--second">
                    <span class="bh__period-label">Após o almoço</span>
                    <input type="time"
                           name="horarios[{{ $num }}][p2_abre]"
                           value="{{ old("horarios.$num.p2_abre", $horariosAtuais[$num]['p2_abre'] ?? '') }}"
                           aria-label="{{ $nome }} - reabre">
                    <span class="bh__sep">às</span>
                    <input type="time"
                           name="horarios[{{ $num }}][p2_fecha]"
                           value="{{ old("horarios.$num.p2_fecha", $horariosAtuais[$num]['p2_fecha'] ?? '') }}"
                           aria-label="{{ $nome }} - fecha (2º)">
                </div>

            </div>

        </div>
    @endforeach

    @php
        $bhError = $errors->first('horarios')
            ?: ($errors->first('horarios.*.p1_abre')
            ?: $errors->first('horarios.*.p1_fecha'));
    @endphp
    @if ($bhError)
        <p class="bh__error">{{ $bhError }}</p>
    @endif

</div>

@once
    <script src="/js/business-hours.js" defer></script>
@endonce
