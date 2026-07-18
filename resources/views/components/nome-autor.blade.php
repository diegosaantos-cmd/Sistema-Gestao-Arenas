{{--
    Nome de quem fez uma ação (lançou no caixa, cancelou, cadastrou...).

    Se a conta foi encerrada, o próprio nome já vem como "Gerente removido #12"
    (User::encerrarConta apaga o nome e grava a FUNÇÃO — dado pessoal some por
    LGPD, a auditoria fica). Por isso não se acrescenta mais nenhum sufixo aqui:
    seria redundante. Depende dos relacionamentos de autoria usarem withTrashed.

    Uso:
      <x-nome-autor :user="$entry->createdBy" />
      <x-nome-autor :user="$reserva->cancelledBy" vazio="Sistema" />
      <x-nome-autor :user="$entry->createdBy" com-tipo />   (mostra "Gerente: Fulano")
--}}
@props([
    'user' => null,
    'vazio' => '—',
    'comTipo' => false,
])

@if ($user)
    <span @class(['text-muted fst-italic' => $user->trashed()])>{{ $comTipo ? $user->descricaoComTipo() : $user->name }}</span>
@else
    {{ $vazio }}
@endif
