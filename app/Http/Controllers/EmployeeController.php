<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Support\ArenaAtual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $employees = $arena->employees()->with('user')->get();

        return view('employees.index', compact('arena', 'employees'));
    }

    /**
     * Ativa/desativa um funcionário (só muda o flag, preserva o histórico).
     */
    public function toggleActive(Employee $employee)
    {
        $this->guardEmployee($employee);

        $employee->update(['active' => ! $employee->active]);

        return back()->with('msg', $employee->active
            ? 'Funcionário ativado.'
            : 'Funcionário desativado.');
    }

    /**
     * Formulário de novo funcionário (na arena que o dono está gerenciando).
     */
    public function create()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        if (! $arena->active) {
            return redirect()->route('owners.dashboard')
                ->with('aviso', 'A arena "' . $arena->name . '" está inativa. Reative-a para cadastrar funcionários.');
        }

        return view('employees.create', compact('arena'));
    }

    /**
     * Cria o usuário (tipo employee) e o vínculo de funcionário com a arena.
     */
    public function store(Request $request)
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        if (! $arena->active) {
            return redirect()->route('owners.dashboard')
                ->with('aviso', 'A arena "' . $arena->name . '" está inativa. Reative-a para cadastrar funcionários.');
        }

        $request->merge([
            'name' => ArenaController::normalizarTexto($request->input('name')),
            'position' => ArenaController::normalizarTexto($request->input('position')),
            'email' => ArenaController::normalizarEmail($request->input('email')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'position' => ['required', 'string', 'max:80'],
            // O gerente só cadastra atendente (basic); nunca outro gerente.
            'access_level' => ['required', Rule::in($this->niveisPermitidos())],
        ], [
            'email.unique' => 'Já existe um usuário com esse e-mail.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'access_level.in' => 'Tipo de funcionário inválido.',
        ]);

        DB::transaction(function () use ($validated, $arena) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password_hash' => Hash::make($validated['password']),
                'type' => 'employee',
                // Funcionário é criado pelo dono/gerente (área controlada): já
                // nasce com e-mail verificado, para não ser barrado pelo
                // middleware `verified` caso a verificação de e-mail seja ligada.
                'email_verified_at' => now(),
            ]);

            Employee::create([
                'user_id' => $user->id,
                'arena_id' => $arena->id,
                'created_by' => auth()->id(),
                'position' => $validated['position'],
                'access_level' => $validated['access_level'],
                'active' => true,
            ]);
        });

        return redirect()->route('owners.dashboard')
            ->with('msg', 'Funcionário cadastrado com sucesso!');
    }

    public function show(string $id)
    {
        //
    }

    /**
     * Formulário de edição do funcionário (dono da arena dele).
     */
    public function edit(Employee $employee)
    {
        $this->guardEmployee($employee);

        $employee->load('user');

        return view('employees.edit', [
            'employee' => $employee,
            'arena' => $employee->arena,
        ]);
    }

    /**
     * Salva a edição do funcionário: dados de acesso (nome, e-mail, telefone,
     * senha opcional) + cargo e tipo. A arena e o status não mudam aqui.
     */
    public function update(Request $request, Employee $employee)
    {
        $this->guardEmployee($employee);

        $request->merge([
            'name' => ArenaController::normalizarTexto($request->input('name')),
            'position' => ArenaController::normalizarTexto($request->input('position')),
            'email' => ArenaController::normalizarEmail($request->input('email')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
            'position' => ['required', 'string', 'max:80'],
            // O gerente não promove ninguém a gerente.
            'access_level' => ['required', Rule::in($this->niveisPermitidos())],
        ], [
            'email.unique' => 'Já existe um usuário com esse e-mail.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'access_level.in' => 'Tipo de funcionário inválido.',
        ]);

        DB::transaction(function () use ($validated, $employee) {
            $dadosUser = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ];

            // Só troca a senha se foi informada.
            if (! empty($validated['password'])) {
                $dadosUser['password_hash'] = Hash::make($validated['password']);
            }

            $employee->user->update($dadosUser);

            $employee->update([
                'position' => $validated['position'],
                'access_level' => $validated['access_level'],
            ]);
        });

        return redirect()->route('employees.index')
            ->with('msg', 'Funcionário atualizado com sucesso!');
    }

    /**
     * Exclui (soft delete) o perfil do funcionário, preservando o histórico de
     * agendamentos. Mesmo método da exclusão de arena: o registro não é apagado,
     * só marcado como excluído. O login também é desativado.
     */
    public function destroy(Employee $employee)
    {
        $this->guardEmployee($employee);

        DB::transaction(function () use ($employee) {
            $employee->user?->delete(); // soft delete do usuário (login)
            $employee->delete();        // soft delete do vínculo de funcionário
        });

        return redirect()->route('employees.index')
            ->with('msg', 'Funcionário excluído. O histórico de agendamentos foi preservado.');
    }

    /**
     * Níveis de acesso que o usuário atual pode atribuir a um funcionário.
     * O gerente só cadastra/edita atendente (basic); o dono, ambos.
     */
    private function niveisPermitidos(): array
    {
        return ArenaAtual::ehGerente() ? ['basic'] : ['basic', 'managerial'];
    }

    /**
     * Garante que o funcionário é da arena que o usuário gerencia agora e que
     * ele tem permissão para mexer nesse funcionário. O gerente só administra
     * atendentes — quem gerencia gerentes é o dono.
     */
    private function guardEmployee(Employee $employee): void
    {
        $arena = ArenaAtual::obter();

        abort_unless($employee->arena_id === $arena->id, 403);

        if (ArenaAtual::ehGerente() && $employee->access_level === 'managerial') {
            abort(403, 'Gerentes só podem administrar atendentes.');
        }
    }
}
