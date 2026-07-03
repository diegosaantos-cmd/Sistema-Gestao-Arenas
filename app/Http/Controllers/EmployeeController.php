<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem ver os funcionários.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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
        $owner = Owner::where('user_id', auth()->id())->first();

        // Garante que o funcionário é de uma arena deste proprietário.
        if (! $owner || ! $owner->arenas()->whereKey($employee->arena_id)->exists()) {
            abort(403);
        }

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
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem cadastrar funcionários.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem cadastrar funcionários.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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
            'access_level' => ['required', Rule::in(['basic', 'managerial'])],
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

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
