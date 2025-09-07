<?php
class CUM_Notifications {
    
    public function show_notice($type, $message) {
        $classes = array(
            'success' => 'notice-success',
            'error' => 'notice-error',
            'info' => 'notice-info'
        );

        $this->remove_existing_notices();
        
        return sprintf(
            '<div class="cum-notice %s" data-auto-dismiss="5000" data-notice-id="%s">
                <p>%s</p>
                <button type="button" class="cum-notice-dismiss" aria-label="Fechar">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>',
            $classes[$type] ?? 'notice-info',
            esc_attr($unique_id),
            $message
        );
    }

    private function remove_existing_notices() {
        echo '<script>jQuery(".cum-notice").remove();</script>';
    }
    
    public function get_login_message() {
        return '<div class="cum-alert">
            <p>Você está tentando acessar uma Área Restrita. Por favor, faça <a href="https://centrorochas.org.br/area-do-associado/">login</a> para acessar esta área.</p>
        </div>';
    }
    
    public function get_no_files_message() {
        return '<div style="display: flex; align-items: center; justify-content: space-between;">
        <a href="/area-do-associado/meus-documentos/" class="button cum-new-file-button">
                <span class="dashicons dashicons-list-view"></span> Meus Arquivos
            </a>
            <a href="/area-do-associado/formulario-de-envio/" class="button cum-new-file-button">
                <span class="dashicons dashicons-plus-alt2"></span> Novo Arquivo
            </a>
        </div>
        <div class="cum-alert">
            <p>Você ainda não enviou nenhum arquivo.</p>
        </div>';
    }
    
    public function handle_query_notices() {
        if (isset($_GET['delete'])) {
            if ($_GET['delete'] === 'success') {
                add_action('wp_footer', function() {
                    echo $this->show_notice('success', 'Arquivo excluído com sucesso!', 'delete-success');
                });
            }
            return;
        }
        
        if (isset($_GET['upload'])) {
            if ($_GET['upload'] === 'success') {
                add_action('wp_footer', function() {
                    echo $this->show_notice('success', 'Arquivo(s) enviado(s) com sucesso!', 'upload-success');
                });
            }
            return;
        }
        
        if (isset($_GET['upload_error'])) {
            $error_messages = array(
                'type' => 'Tipo de arquivo não permitido.',
                'size' => 'O arquivo é muito grande. Tamanho máximo: 5MB.',
                'generic' => 'Ocorreu um erro ao enviar o arquivo.'
            );
            
            $message = $error_messages[$_GET['upload_error']] ?? $error_messages['generic'];
            add_action('wp_footer', function() use ($message) {
                echo $this->show_notice('error', $message, 'upload-error');
            });
        }
    }
}