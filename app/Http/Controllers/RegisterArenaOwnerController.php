<?php

namespace App\Http\Controllers;

use App\Models\Arena;
use App\Models\Court;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterArenaOwnerController extends Controller
{
    /**
     * Mostra o formulário de cadastro de proprietário de arena.
     */
    public function create()
    {
        // Cliente logado pode virar proprietário reaproveitando a própria conta.
        $ehClienteLogado = auth()->check() && auth()->user()->type === 'client';

        return view('auth.registerArenaOwners', compact('ehClienteLogado'));
    }

    /**
     * Cria o proprietário e a arena.
     *
     * Dois caminhos, escolhidos na Etapa 1 (campo `modo_conta`):
     * - "atual": o cliente logado VIRA proprietário com a MESMA conta. O papel de
     *   cliente é encerrado (soft delete), o histórico de reservas permanece.
     * - "novos": cria uma conta de proprietário nova (dados de acesso próprios).
     *   Se for um cliente logado, a conta de cliente dele fica intacta.
     */
    public function store(Request $request)
    {
        $usarContaAtual = auth()->check()
            && auth()->user()->type === 'client'
            && $request->input('modo_conta') === 'atual';

        $request->merge([
            'company_name' => ArenaController::normalizarTexto($request->input('company_name')),
            'name_arena' => ArenaController::normalizarTexto($request->input('name_arena')),
            'email' => ArenaController::normalizarEmail($request->input('email')),
            'email_arena' => ArenaController::normalizarEmail($request->input('email_arena')),
            'owner_phone' => trim((string) $request->input('owner_phone')),
            // CPF/CNPJ: guarda só os dígitos (123.456.789-00 -> 12345678900).
            'tax_id' => preg_replace('/\D/', '', (string) $request->input('tax_id')),
            'quadras' => ArenaController::normalizarQuadras($request->input('quadras', [])),
        ]);

        // Dados de acesso só são validados ao criar conta NOVA. Ao usar a conta
        // atual, reaproveitamos nome/e-mail/telefone/senha do cliente logado.
        $regrasConta = $usarContaAtual ? [] : [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            // Telefone pessoal do proprietário (users.phone). É diferente do
            // `phone`, que é o telefone de contato da arena.
            'owner_phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];

        // Para o e-mail da arena: o e-mail do próprio dono é permitido.
        $emailDoDono = $usarContaAtual ? auth()->user()->email : $request->input('email');
        $ignoraUserId = $usarContaAtual ? auth()->id() : null;

        $validated = $request->validate(array_merge($regrasConta, [
            'terms' => ['required', 'accepted'],
            'company_name' => ['required', 'string', 'max:150', function ($attribute, $value, $fail) {
                $chave = ArenaController::chaveComparacao($value);
                if (Owner::whereRaw("REPLACE(LOWER(company_name), ' ', '') = ?", [$chave])->exists()) {
                    $fail('Já existe uma empresa cadastrada com esse nome.');
                }
            }],
            // whereNull('deleted_at'): o CPF/CNPJ de uma empresa EXCLUÍDA volta a
            // ficar livre. Sem isto, `unique:owners,tax_id` contaria a linha em
            // soft delete e travaria o documento para sempre.
            'tax_id' => [
                'required', 'string', 'regex:/^(\d{11}|\d{14})$/',
                Rule::unique('owners', 'tax_id')->whereNull('deleted_at'),
            ],
            'name_arena' => ['required', 'string', 'max:120', function ($attribute, $value, $fail) {
                $chave = ArenaController::chaveComparacao($value);
                if (Arena::whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$chave])->exists()) {
                    $fail('Já existe uma arena com esse nome.');
                }
            }],
            'description' => ['max:300'],
            'address_rua' => ['required', 'string', 'max:120'],
            'address_bairro' => ['required', 'string', 'max:120'],
            'address_numero' => ['required', 'string', 'max:15'],
            'phone' => ['required', 'string', 'max:20'],
            'email_arena' => ['required', 'email', 'max:150', function ($attribute, $value, $fail) use ($emailDoDono, $ignoraUserId) {
                if (ArenaController::emailDeArenaEmUsoPorOutroDono($value, null)) {
                    $fail('Este e-mail já está sendo usado por uma arena de outro proprietário.');
                } elseif ($value !== $emailDoDono
                    && ArenaController::emailPertenceAOutroUsuario($value, $ignoraUserId)) {
                    $fail('Este e-mail pertence à conta de outra pessoa. Use um e-mail que não seja de outro usuário.');
                }
            }],
            'horarios' => ['required', 'array', function ($attribute, $value, $fail) {
                $algumDia = collect($value)->contains(fn ($dia) => ! empty($dia['aberto']));
                if (! $algumDia) {
                    $fail('Marque ao menos um dia de funcionamento.');
                    return;
                }
                if ($erro = ArenaController::erroNosPeriodos($value)) {
                    $fail($erro);
                }
            }],
            'horarios.*.aberto' => ['nullable', 'boolean'],
            'horarios.*.p1_abre' => ['required_with:horarios.*.aberto', 'date_format:H:i'],
            'horarios.*.p1_fecha' => ['required_with:horarios.*.aberto', 'date_format:H:i'],
            'horarios.*.p2_abre' => ['nullable', 'date_format:H:i'],
            'horarios.*.p2_fecha' => ['nullable', 'date_format:H:i'],
            'pagamentos' => ['required', 'array', 'min:1'],
            'pagamentos.*' => ['integer', 'exists:payment_methods,id'],
            'quadras' => ['required', 'array', 'min:1', function ($attribute, $value, $fail) {
                if (ArenaController::temNomesDeQuadraDuplicados($value)) {
                    $fail('Há quadras com nomes equivalentes (ignorando espaços e maiúsculas).');
                }
            }],
            'quadras.*.nome' => ['required', 'string', 'max:80'],
            'quadras.*.descricao' => ['nullable', 'string'],
            'quadras.*.valor_hora' => ['required', 'numeric', 'min:0'],
            'quadras.*.ativa' => ['nullable', 'boolean'],
            'quadras.*.esportes' => ['required', 'array', 'min:1'],
            'quadras.*.esportes.*' => [Rule::in(array_keys(Court::SPORTS))],
        ]), [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'owner_phone.required' => 'Informe seu telefone.',
            'password.required' => 'Crie uma senha.',
            'password.min' => 'A senha deve ter ao menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'terms.required' => 'É preciso aceitar os termos de uso.',
            'terms.accepted' => 'É preciso aceitar os termos de uso.',
            'company_name.required' => 'Informe o nome da empresa.',
            'name_arena.required' => 'Informe o nome da arena.',
            'email_arena.required' => 'Informe o e-mail da arena.',
            'phone.required' => 'Informe o telefone de contato da arena.',
            'tax_id.regex' => 'Informe um CPF (11 dígitos) ou CNPJ (14 dígitos) válido.',
            'horarios.required' => 'Marque ao menos um dia de funcionamento.',
            'horarios.*.p1_abre.required_with' => 'Informe o horário de abertura do dia marcado.',
            'horarios.*.p1_fecha.required_with' => 'Informe o horário de fechamento do dia marcado.',
            'pagamentos.required' => 'Selecione ao menos uma forma de pagamento.',
            'pagamentos.min' => 'Selecione ao menos uma forma de pagamento.',
            'quadras.required' => 'Cadastre ao menos uma quadra.',
            'quadras.min' => 'Cadastre ao menos uma quadra.',
            'quadras.*.nome.required' => 'Informe o nome da quadra.',
            'quadras.*.valor_hora.required' => 'Informe o valor por hora da quadra.',
            'quadras.*.esportes.required' => 'Selecione ao menos um esporte por quadra.',
            'quadras.*.esportes.min' => 'Selecione ao menos um esporte por quadra.',
        ]);

        $horarios = $request->input('horarios', []);
        $pagamentos = $request->input('pagamentos', []);
        $quadras = $request->input('quadras', []);
        $dadosTaxa = ArenaController::dadosTaxaCancelamento($request);

        // Transação: ou faz TUDO, ou não faz nada.
        $user = DB::transaction(function () use ($validated, $horarios, $pagamentos, $quadras, $dadosTaxa, $usarContaAtual) {
            if ($usarContaAtual) {
                // Cliente logado vira proprietário com a MESMA conta. Encerra o
                // papel de cliente (soft delete) — o histórico de reservas
                // permanece (Booking::client() usa withTrashed). E-mail, senha,
                // nome e telefone continuam os mesmos.
                $user = auth()->user();
                $user->client()->delete();
                $user->update(['type' => 'owner']);
            } else {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['owner_phone'],
                    'password_hash' => Hash::make($validated['password']),
                    'terms_accepted_at' => now(),
                    'type' => 'owner',
                ]);
            }

            $owner = Owner::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'tax_id' => $validated['tax_id'],
            ]);

            $arena = Arena::create([
                'owner_id' => $owner->id,
                'name' => $validated['name_arena'],
                'description' => $validated['description'],
                'address_rua' => $validated['address_rua'],
                'address_bairro' => $validated['address_bairro'],
                'address_numero' => $validated['address_numero'],
                'phone' => $validated['phone'],
                'contact_email' => $validated['email_arena'],
                ...$dadosTaxa,
            ]);

            ArenaController::salvarHorarios($arena, $horarios);

            ArenaController::salvarQuadras($arena, $quadras);

            $arena->paymentMethods()->sync($pagamentos);

            return $user;
        });

        // Conta NOVA: dispara Registered (envia o e-mail de verificação, se a
        // verificação estiver ligada). No "usar conta atual" a conta já existe e
        // mantém o estado de verificação que já tinha. É no-op quando a
        // verificação está desligada (hasVerifiedEmail() retorna true).
        if (! $usarContaAtual) {
            event(new Registered($user));
        }

        // Já loga o usuário recém-criado e leva ao painel.
        Auth::login($user);

        return redirect()->route('owners.dashboard');
    }
}
