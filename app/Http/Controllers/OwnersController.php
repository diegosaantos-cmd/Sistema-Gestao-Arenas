<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('owners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'tax_id' => ['required', 'string', 'max:20', 'unique:owners,tax_id'],
        ]);

        Owner::create([
            'user_id' => auth()->id(),
            'company_name' => $validated['company_name'],
            'tax_id' => $validated['tax_id'],
        ]);

        auth()->user()->update([
            'type' => 'owner'
        ]);

        return redirect()->route('owners.dashboard');
    }

    /**
     * Display the specified resource.
     */
    public function show(Owner $owner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Owner $owner)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Owner $owner)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Owner $owner)
    {
        //
    }

    public function deactivateCompany()
    {
        $owner = Owner::where('user_id', auth()->id())->firstOrFail();

        DB::transaction(function () use ($owner) {
            $owner->update([
                'active' => false,
                'deactivated_by' => auth()->id(),
                'deactivation_source' => 'self',
                'deactivated_at' => now(),
            ]);
            $owner->arenas()->update(['active' => false]);
            Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))
                ->update(['active' => false]);
        });

        return back()->with('msg', 'Sua empresa foi desativada. Você pode reativá-la quando desejar.');
    }

    public function activateCompany()
    {
        $owner = Owner::where('user_id', auth()->id())->firstOrFail();

        if ($owner->deactivation_source !== 'self') {
            return back()->withErrors([
                'empresa' => 'A empresa foi desativada pelo administrador do sistema e somente ele pode reativá-la.',
            ]);
        }

        DB::transaction(function () use ($owner) {
            $owner->update([
                'active' => true,
                'deactivated_by' => null,
                'deactivation_source' => null,
                'deactivated_at' => null,
            ]);
            $owner->arenas()->update(['active' => true]);
            Court::whereIn('arena_id', $owner->arenas()->select('arenas.id'))
                ->update(['active' => true]);
        });

        return back()->with('msg', 'Sua empresa foi ativada com sucesso.');
    }
}
