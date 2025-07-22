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
            '<div class="cum-notice %s" data-auto-dismiss="5000">
                <p>%s</p>
                <button type="button" class="cum-notice-dismiss" aria-label="Fechar">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>',
            $classes[$type] ?? 'notice-info',
            $message
        );
    }

    private function remove_existing_notices() {
        echo '<script>jQuery(".cum-notice").remove();</script>';
    }
    
    public function get_login_message() {
        return '<div class="cum-alert">
            <p>Você está tentando acessar uma Área Restrita. Por favor, faça <a href="' . esc_url(wp_login_url()) . '">login</a> para acessar esta área.</p>
        </div>';
    }
    
    public function get_no_files_message() {
        return '<div class="cum-alert">
            <p>Você ainda não enviou nenhum arquivo.</p>
        </div>';
    }
    
    public function handle_query_notices() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Verifica se há uma mensagem de exclusão (tem prioridade)
        if (isset($_GET['delete']) && $_GET['delete'] === 'success') {
            add_action('wp_footer', function() {
                echo $this->show_notice('success', 'Arquivo excluído com sucesso!', 'delete-success');
            });
            return;
        }
        
        // Verifica se há mensagem de upload
        if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
            add_action('wp_footer', function() {
                echo $this->show_notice('success', 'Arquivo(s) enviado(s) com sucesso!', 'upload-success');
            });
            return;
        }
        
        // Verifica se há erro de upload
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