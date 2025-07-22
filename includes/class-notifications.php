<?php
class CUM_Notifications {
    
    public function show_notice($type, $message) {
        $classes = array(
            'success' => 'notice-success',
            'error' => 'notice-error',
            'info' => 'notice-info'
        );
        
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
        if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
            echo $this->show_notice('success', 'Arquivo(s) enviado(s) com sucesso!');
        }
        
        if (isset($_GET['upload_error'])) {
            $error_messages = array(
                'type' => 'Tipo de arquivo não permitido.',
                'size' => 'O arquivo é muito grande. Tamanho máximo: 5MB.',
                'generic' => 'Ocorreu um erro ao enviar o arquivo.'
            );
            
            $message = $error_messages[$_GET['upload_error']] ?? $error_messages['generic'];
            echo $this->show_notice('error', $message);
        }
        
        if (isset($_GET['delete']) && $_GET['delete'] === 'success') {
            echo $this->show_notice('success', 'Arquivo excluído com sucesso!');
        }
    }
}