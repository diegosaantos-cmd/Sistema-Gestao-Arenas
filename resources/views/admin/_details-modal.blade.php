{{--
    Modal reutilizável de detalhes para as telas de listagem do admin.
    Uso:
      - No botão da linha:  data-details-target="#algumId"
      - Um elemento oculto com esse id contendo o conteúdo:
            <div id="algumId" class="d-none"> ...detalhes... </div>
    Inclua @include('admin._details-modal') uma vez na página.
--}}
<div class="modal fade" id="adminDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" data-details-body></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('adminDetailsModal');
        if (!modalEl) return;

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var body = modalEl.querySelector('[data-details-body]');

        // Delegação: funciona também com linhas carregadas dinamicamente.
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-details-target]');
            if (!trigger) return;

            var source = document.querySelector(trigger.getAttribute('data-details-target'));
            if (!source) return;

            body.innerHTML = source.innerHTML;
            modal.show();
        });
    });
</script>
