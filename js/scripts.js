jQuery(document).ready(function($) {
    // Remove parâmetros da URL após mostrar as notificações
    if (window.location.search.includes('upload=') || 
        window.location.search.includes('delete=') || 
        window.location.search.includes('upload_error=') ||
        window.location.search.includes('folder_created=') ||
        window.location.search.includes('folder_error=')) {
        
        // Espera a animação terminar antes de limpar a URL
        setTimeout(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 1000);
    }

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
    
    // Gerenciamento de pastas
    
    // Validação do nome da pasta
    $('#cum_folder_name').on('input', function() {
        const folderName = $(this).val();
        const button = $(this).siblings('button');
        
        // Remove caracteres não permitidos em tempo real
        const sanitized = folderName.replace(/[^a-zA-Z0-9\-_\s]/g, '');
        if (sanitized !== folderName) {
            $(this).val(sanitized);
        }
        
        // Habilita/desabilita o botão baseado na validação
        if (sanitized.trim().length > 0 && sanitized.trim().length <= 50) {
            button.prop('disabled', false);
        } else {
            button.prop('disabled', true);
        }
    });
    
    // Submissão do formulário de criação de pasta
    $('#cum-folder-form').on('submit', function(e) {
        const folderName = $('#cum_folder_name').val().trim();
        
        if (folderName.length === 0) {
            e.preventDefault();
            alert('Por favor, digite um nome para a pasta.');
            return false;
        }
        
        if (folderName.length > 50) {
            e.preventDefault();
            alert('O nome da pasta deve ter no máximo 50 caracteres.');
            return false;
        }
        
        // Confirma a criação da pasta
        if (!confirm(`Deseja criar a pasta "${folderName}"?`)) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Atualiza a lista de pastas no select após criação bem-sucedida
    if (window.location.search.includes('folder_created=success')) {
        // Recarrega a página para atualizar a lista de pastas
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }
    
    // Placeholder dinâmico para o campo de nome da pasta
    const folderPlaceholders = [
        'Ex: NF, Contratos, Documentos',
        'Ex: Relatórios, Planilhas',
        'Ex: Fotos, Imagens',
        'Ex: Backup, Arquivos'
    ];
    
    let placeholderIndex = 0;
    setInterval(() => {
        const input = $('#cum_folder_name');
        if (input.length && !input.is(':focus') && input.val() === '') {
            input.attr('placeholder', folderPlaceholders[placeholderIndex]);
            placeholderIndex = (placeholderIndex + 1) % folderPlaceholders.length;
        }
    }, 3000);
    
    // Destaque visual para a pasta selecionada no upload
    $('#cum_folder_select').on('change', function() {
        const selectedFolder = $(this).val();
        const formGroup = $(this).closest('.form-group');
        
        if (selectedFolder) {
            formGroup.addClass('folder-selected');
        } else {
            formGroup.removeClass('folder-selected');
        }
    });
    
    // Confirmação antes de navegar para uma pasta
    $('.cum-folder-row a').on('click', function(e) {
        const folderName = $(this).text();
        if (!confirm(`Deseja abrir a pasta "${folderName}"?`)) {
            e.preventDefault();
            return false;
        }
    });
});