jQuery(document).ready(function($) {
    // Remove notificações existentes ao carregar a página
    $('.cum-notice').remove();

    // Fechar notificação manualmente
    $(document).on('click', '.cum-notice-dismiss', function() {
        dismissNotice($(this).closest('.cum-notice'));
    });

    // Fechar notificação automaticamente
    $('.cum-notice[data-auto-dismiss]').each(function() {
        const notice = $(this);
        const timeout = parseInt(notice.attr('data-auto-dismiss'));
        
        setTimeout(() => {
            if (notice.is(':visible')) {
                dismissNotice(notice);
            }
        }, timeout);
    });

    function dismissNotice(notice) {
        notice.addClass('fade-out');
        setTimeout(() => notice.remove(), 500);
    }

    // Preview dos arquivos selecionados
    $('#cum_files').on('change', function() {
        const files = this.files;
        const preview = $('#file-selection-preview');
        preview.empty();
        
        if (files.length > 0) {
            preview.append('<p>Arquivos selecionados:</p>');
            
            for (let i = 0; i < files.length; i++) {
                preview.append(`
                    <div class="file-preview-item">
                        <span>${files[i].name}</span>
                        <span>${(files[i].size / 1024).toFixed(2)} KB</span>
                    </div>
                `);
            }
            
            preview.append(`<p>Total: ${files.length} arquivo(s)</p>`);
        }
    });
    
    // Confirmação antes de enviar muitos arquivos
    $('#cum-upload-form').on('submit', function() {
        const files = $('#cum_files')[0].files;
        if (files.length > 10) {
            return confirm('Você está tentando enviar muitos arquivos de uma vez. Deseja continuar?');
        }
        return true;
    });
});