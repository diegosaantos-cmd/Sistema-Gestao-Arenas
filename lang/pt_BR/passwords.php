<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Linhas de tradução da recuperação de senha
    |--------------------------------------------------------------------------
    */

    'reset' => 'Sua senha foi redefinida com sucesso!',
    // Mensagem NEUTRA (anti-enumeração): vale exista ou não a conta. Ver o bind
    // em FortifyServiceProvider que iguala a resposta de falha à de sucesso.
    'sent' => 'Se houver uma conta com esse e-mail, você receberá um link de recuperação.',
    'throttled' => 'Aguarde um momento antes de tentar novamente.',
    'token' => 'O link de recuperação de senha é inválido ou expirou.',
    'user' => 'Não encontramos nenhuma conta com esse e-mail.',

];
