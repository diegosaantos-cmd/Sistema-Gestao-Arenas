<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    /**
     * Tela onde o usuário logado envia uma sugestão ou reporta um bug.
     */
    public function create()
    {
        $meus = Feedback::where('user_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();

        return view('feedback.create', compact('meus'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'tipo'     => ['required', Rule::in(['sugestao', 'bug'])],
            'assunto'  => ['required', 'string', 'max:120'],
            'mensagem' => ['required', 'string', 'max:2000'],
        ], [
            'tipo.required'     => 'Escolha se é uma sugestão ou um bug.',
            'assunto.required'  => 'Informe o assunto.',
            'mensagem.required' => 'Escreva a mensagem.',
        ]);

        Feedback::create([
            'user_id'  => auth()->id(),
            'tipo'     => $dados['tipo'],
            'assunto'  => $dados['assunto'],
            'mensagem' => $dados['mensagem'],
        ]);

        return redirect()->route('feedback.create')
            ->with('status', 'Enviado! Obrigado pelo seu retorno.');
    }

    /**
     * Lista (admin) de todas as sugestões e bugs recebidos.
     */
    public function index(Request $request)
    {
        $tipo = $request->query('tipo');
        $status = $request->query('status');

        $feedbacks = Feedback::with('user')
            ->when(in_array($tipo, ['sugestao', 'bug']), fn ($q) => $q->where('tipo', $tipo))
            ->when(in_array($status, ['aberto', 'em_andamento', 'resolvido']), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.feedbacks.index', compact('feedbacks', 'tipo', 'status'));
    }

    /**
     * Admin altera o status de um feedback (aberto / em andamento / resolvido).
     */
    public function updateStatus(Request $request, Feedback $feedback)
    {
        $dados = $request->validate([
            'status' => ['required', Rule::in(['aberto', 'em_andamento', 'resolvido'])],
        ]);

        $feedback->update(['status' => $dados['status']]);

        return back()->with('status', 'Status atualizado.');
    }
}
