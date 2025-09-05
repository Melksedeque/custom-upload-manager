<?php
class CUM_Upload_Handler {
    
    private $notifications;
    private $folder_manager;
    
    public function __construct($notifications, $folder_manager) {
        $this->notifications = $notifications;
        $this->folder_manager = $folder_manager;
    }
    
    public function upload_form_shortcode() {
        if (!is_user_logged_in()) {
            return $this->notifications->get_login_message();
        }
        
        $current_user = wp_get_current_user();
        $username = $current_user->user_login;
        
        $this->notifications->handle_query_notices();
        
        ob_start();
        ?>
        <div class="custom-upload-form">
            <!-- Formulário para criar nova pasta -->
            <div class="cum-folder-creation">
                <h4>Criar Nova Pasta</h4>
                <form method="post" id="cum-folder-form">
                    <input type="hidden" name="cum_action" value="create_folder">
                    <div class="form-group">
                        <label for="cum_folder_name">Nome da pasta:</label>
                        <input type="text" name="cum_folder_name" id="cum_folder_name" placeholder="Ex: NF, Contratos, Documentos" maxlength="50">
                        <button type="submit" class="button button-secondary">Criar Pasta</button>
                    </div>
                </form>
            </div>
            
            <!-- Formulário de upload -->
            <form method="post" enctype="multipart/form-data" id="cum-upload-form">
                <input type="hidden" name="cum_action" value="upload_file">
                <input type="hidden" name="cum_user" value="<?php echo esc_attr($username); ?>">
                
                <div class="form-group">
                    <label for="cum_folder_select">Selecionar pasta:</label>
                    <select name="cum_folder_select" id="cum_folder_select">
                        <option value="">Pasta raiz (sem pasta)</option>
                        <?php 
                        $folders = $this->folder_manager->get_user_folders();
                        foreach ($folders as $folder): 
                        ?>
                        <option value="<?php echo esc_attr($folder); ?>"><?php echo esc_html($folder); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cum_files">Selecione os arquivos:</label>
                    <input type="file" name="cum_files[]" id="cum_files" multiple required>
                    <div id="file-selection-preview" class="file-preview"></div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button">Enviar Arquivos</button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_file_upload() {
        if (!isset($_POST['cum_action']) || $_POST['cum_action'] !== 'upload_file') {
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_die('Acesso não autorizado.');
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        
        // Verifica se foi selecionada uma pasta específica
        $selected_folder = isset($_POST['cum_folder_select']) ? sanitize_text_field($_POST['cum_folder_select']) : '';
        
        if (!empty($selected_folder) && $this->folder_manager->folder_exists($selected_folder)) {
            $user_dir = $this->folder_manager->get_folder_path($selected_folder);
        } else {
            $user_dir = $this->folder_manager->get_folder_path();
        }
        
        // Cria o diretório se não existir
        if (!file_exists($user_dir)) {
            wp_mkdir_p($user_dir);
        }
        
        // Verifica se há arquivos
        if (empty($_FILES['cum_files']['name'][0])) {
            wp_redirect(add_query_arg('upload_error', 'generic', wp_get_referer()));
            exit;
        }
        
        $upload_errors = array();
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx');
        
        foreach ($_FILES['cum_files']['name'] as $index => $name) {
            if ($_FILES['cum_files']['error'][$index] !== UPLOAD_ERR_OK) {
                $upload_errors[] = 'Erro no upload do arquivo: ' . $name;
                continue;
            }
            
            $file_name = sanitize_file_name($name);
            $file_tmp = $_FILES['cum_files']['tmp_name'][$index];
            $file_size = $_FILES['cum_files']['size'][$index];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Valida extensão
            if (!in_array($file_ext, $allowed_extensions)) {
                $upload_errors[] = $name;
                continue;
            }
            
            // Valida tamanho
            if ($file_size > 5 * 1024 * 1024) {
                $upload_errors[] = $name;
                continue;
            }
            
            // Gera nome único e move o arquivo
            $unique_name = wp_unique_filename($user_dir, $file_name);
            $destination = $user_dir . $unique_name;
            
            if (!move_uploaded_file($file_tmp, $destination)) {
                $upload_errors[] = $name;
            }
        }
        
        // Redireciona com feedback
        if (!empty($upload_errors)) {
            $error_type = (strpos($upload_errors[0], 'Erro no upload') !== false) ? 'generic' : 
                        ($_FILES['cum_files']['size'][0] > 5 * 1024 * 1024 ? 'size' : 'type');
            $redirect_url = remove_query_arg(['upload', 'delete'], '/area-do-assinante/meus-documentos/');
            wp_redirect(add_query_arg('upload_error', $error_type, $redirect_url));
        } else {
            $redirect_url = remove_query_arg(['upload_error', 'delete'], '/area-do-assinante/meus-documentos/');
            wp_redirect(add_query_arg('upload', 'success', $redirect_url));
        }
        exit;
    }
}