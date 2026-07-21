<?php

namespace App\Support;

/**
 * O vocabulário da anonimização: o que entra no lugar do dado pessoal quando
 * uma conta ou uma arena é excluída.
 *
 * Anonimizar é SUBSTITUIR, não esvaziar. Campo nulo é ambíguo — não se
 * distingue "nunca foi preenchido" de "foi removido na exclusão" —, então quem
 * lê depois ou mostra um branco sem explicação, ou tenta seguir um vínculo que
 * não existe mais e quebra. O marcador diz que ali havia um dado e ele saiu,
 * que é a informação de que as telas precisam.
 *
 * Para a LGPD tanto faz: o dado pessoal desaparece igual nos dois casos. O
 * marcador ainda ajuda, por registrar que a anonimização foi aplicada em vez
 * de deixar parecer que nunca se coletou nada.
 *
 * EXCEÇÃO: coluna que não é texto não comporta marcador. A data de nascimento
 * fica nula mesmo (nenhuma tela identifica alguém por ela, então não há rastro
 * a preservar), e a identidade de quem reservou vive em `guest_name`.
 */
final class Anonimizacao
{
    /** Contato que existia e foi retirado — telefone, e-mail, observação. */
    public const REMOVIDO = 'Removido';

    /** No lugar do nome de quem reservou, quando a conta foi encerrada. */
    public const CLIENTE_EXCLUIDO = 'Cliente excluído';
}
