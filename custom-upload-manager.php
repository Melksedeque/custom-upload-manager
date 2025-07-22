<?php
/*
Plugin Name: Custom Upload Manager
Description: Plugin para gerenciamento de uploads de arquivos por usuários
Version: 1.0
Author: Melksedeque Silva
Author email: freelancer@melksedeque.com.br
*/

if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('CUM_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/associados/');
define('CUM_UPLOAD_URL', content_url('/uploads/associados/'));

// Inicializa o plugin
class Custom_Upload_Manager {
    
    public function __construct() {
        // Hooks e shortcodes
        add_shortcode('custom_upload_form', array($this, 'upload_form_shortcode'));
        add_shortcode('custom_file_list', array($this, 'file_list_shortcode'));
        
        // Actions
        add_action('init', array($this, 'handle_file_upload'));
        add_action('init', array($this, 'handle_file_delete'));

        // Adição de script para estilo:
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Cria o diretório de uploads se não existir
        $this->create_upload_directory();
    }
    
    // Cria o diretório de uploads
    private function create_upload_directory() {
        if (!file_exists(CUM_UPLOAD_DIR)) {
            wp_mkdir_p(CUM_UPLOAD_DIR);
        }
    }
    
    // Shortcode do formulário de upload
    public function upload_form_shortcode() {
        if (!is_user_logged_in()) {
            return '<div style="background-color: #CFE2FF; border: 1px solid #9EC5FE; border-radius: .5rem; color: #052C65; margin: 10px auto 0; padding: 1rem">
                <p style="margin: 0; text-align:center;">Você está tentando acessar uma Área Restrita. Por favor, faça <a href="https://centrorochas.org.br/area-do-assinante/" style="color: #052C65;font-weight: bold; text-decoration: underline;">login</a> para acessar esta área.</p>
            </div>';
        }
        
        $current_user = wp_get_current_user();
        $username = $current_user->user_login;
        
        ob_start();
        ?>
        <div class="custom-upload-form">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="cum_action" value="upload_file">
                <input type="hidden" name="cum_user" value="<?php echo esc_attr($username); ?>">
                
                <div class="form-group">
                    <label for="cum_file">Selecione o arquivo:</label>
                    <input type="file" name="cum_file" id="cum_file" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button">Enviar Arquivo</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // Processa o upload do arquivo
    public function handle_file_upload() {
        if (!isset($_POST['cum_action']) || $_POST['cum_action'] !== 'upload_file') {
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_die('Acesso não autorizado.');
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        
        // Verifica se o arquivo foi enviado
        if (!isset($_FILES['cum_file']) || $_FILES['cum_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die('Erro no upload do arquivo.');
        }
        
        // Cria o diretório do usuário se não existir
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        if (!file_exists($user_dir)) {
            wp_mkdir_p($user_dir);
        }
        
        // Valida o arquivo
        $file = $_FILES['cum_file'];
        $file_name = sanitize_file_name($file['name']);
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        
        // Extensões permitidas (você pode modificar)
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_extensions)) {
            wp_die('Tipo de arquivo não permitido.');
        }
        
        // Limita o tamanho do arquivo (5MB)
        if ($file_size > 5 * 1024 * 1024) {
            wp_die('O arquivo é muito grande. Tamanho máximo: 5MB.');
        }
        
        // Gera um nome único para o arquivo
        $unique_name = wp_unique_filename($user_dir, $file_name);
        $destination = $user_dir . $unique_name;
        
        // Move o arquivo
        if (move_uploaded_file($file_tmp, $destination)) {
            // Redireciona para evitar reenvio
            wp_redirect(add_query_arg('upload', 'success', wp_get_referer()));
            exit;
        } else {
            wp_die('Erro ao mover o arquivo.');
        }
    }

    // Shortcode da listagem de arquivos
    public function file_list_shortcode() {
        if (!is_user_logged_in()) {
            return '<div style="background-color: #CFE2FF; border: 1px solid #9EC5FE; border-radius: .5rem; color: #052C65; margin: 10px auto 0; padding: 1rem">
                <p style="margin: 0; text-align:center;">Você está tentando acessar uma Área Restrita. Por favor, faça <a href="https://centrorochas.org.br/area-do-assinante/" style="color: #052C65;font-weight: bold; text-decoration: underline;">login</a> para acessar esta área.</p>
            </div>';
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        $msg_sem_arquivo = '<div style="background-color: #CFE2FF; border: 1px solid #9EC5FE; border-radius: .5rem; color: #052C65; margin: 10px auto 0; padding: 1rem">
                <p style="margin: 0; text-align:center;">Você ainda não enviou nenhum arquivo.</p>
            </div>';
        
        // Verifica se o diretório existe
        if (!file_exists($user_dir)) {
            return $msg_sem_arquivo;
        }
        
        // Obtém a lista de arquivos
        $files = scandir($user_dir);
        $files = array_diff($files, array('.', '..'));
        
        if (empty($files)) {
            return $msg_sem_arquivo;
        }
        
        ob_start();
        ?>
        <div class="custom-file-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Tipo</th>
                        <th>Enviado em</th>
                        <th>Opções</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): 
                        $file_path = $user_dir . $file;
                        $file_url = CUM_UPLOAD_URL . $username . '/' . $file;
                        $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $file_date = date('d/m/Y H:i', filemtime($file_path));
                    ?>
                    <tr>
                        <td><a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file); ?></a></td>
                        <td><?php echo esc_html(strtoupper($file_ext)); ?></td>
                        <td><?php echo esc_html($file_date); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="cum_action" value="delete_file">
                                <input type="hidden" name="cum_file" value="<?php echo esc_attr($file); ?>">
                                <button type="submit" class="button-link" onclick="return confirm('Tem certeza que deseja excluir este arquivo?')">
                                    <span class="dashicons dashicons-trash" style="color:#a00;"></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    // Processa a exclusão de arquivos
    public function handle_file_delete() {
        if (!isset($_POST['cum_action']) || $_POST['cum_action'] !== 'delete_file') {
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_die('Acesso não autorizado.');
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        
        $file_name = sanitize_file_name($_POST['cum_file']);
        $file_path = $user_dir . $file_name;
        
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                // Redireciona para evitar reenvio
                wp_redirect(add_query_arg('delete', 'success', wp_get_referer()));
                exit;
            }
        }
        
        wp_die('Erro ao excluir o arquivo.');
    }

    // Método para adicionar estilos
    public function enqueue_styles() {
        wp_enqueue_style(
            'custom-upload-manager',
            plugins_url('css/style.css', __FILE__),
            array(),
            '1.0'
        );
    }
}

new Custom_Upload_Manager();