<?php
/**
 * Classe responsável pelo gerenciamento de pastas dos usuários
 * 
 * Esta classe implementa a funcionalidade de criação, listagem e seleção
 * de pastas para organização de arquivos dos usuários.
 * 
 * @since 1.3
 * @author Melksedeque Silva
 */
class CUM_Folder_Manager {
    
    private $notifications;
    
    public function __construct($notifications) {
        $this->notifications = $notifications;
    }
    
    /**
     * Obtém todas as pastas do usuário atual
     * 
     * @return array Lista de pastas do usuário
     */
    public function get_user_folders() {
        if (!is_user_logged_in()) {
            return array();
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        
        if (!file_exists($user_dir)) {
            return array();
        }
        
        $folders = array();
        $items = scandir($user_dir);
        
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($user_dir . $item)) {
                $folders[] = $item;
            }
        }
        
        // Ordena as pastas alfabeticamente
        sort($folders);
        
        return $folders;
    }
    
    /**
     * Cria uma nova pasta para o usuário
     * 
     * @param string $folder_name Nome da pasta a ser criada
     * @return bool|WP_Error True se criada com sucesso, WP_Error se houver erro
     */
    public function create_folder($folder_name) {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', 'Usuário não está logado.');
        }
        
        // Sanitiza o nome da pasta
        $folder_name = $this->sanitize_folder_name($folder_name);
        
        if (empty($folder_name)) {
            return new WP_Error('invalid_name', 'Nome da pasta inválido.');
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        $folder_path = $user_dir . $folder_name . '/';
        
        // Cria o diretório do usuário se não existir
        if (!file_exists($user_dir)) {
            wp_mkdir_p($user_dir);
        }
        
        // Verifica se a pasta já existe
        if (file_exists($folder_path)) {
            return new WP_Error('folder_exists', 'Uma pasta com este nome já existe.');
        }
        
        // Cria a pasta
        if (wp_mkdir_p($folder_path)) {
            return true;
        } else {
            return new WP_Error('creation_failed', 'Falha ao criar a pasta.');
        }
    }
    
    /**
     * Verifica se uma pasta existe para o usuário atual
     * 
     * @param string $folder_name Nome da pasta
     * @return bool True se a pasta existe
     */
    public function folder_exists($folder_name) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        $folder_path = $user_dir . $folder_name . '/';
        
        return file_exists($folder_path) && is_dir($folder_path);
    }
    
    /**
     * Obtém o caminho completo de uma pasta do usuário
     * 
     * @param string $folder_name Nome da pasta
     * @return string|false Caminho da pasta ou false se não existir
     */
    public function get_folder_path($folder_name = '') {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        
        if (empty($folder_name)) {
            return $user_dir;
        }
        
        $folder_path = $user_dir . $folder_name . '/';
        
        if ($this->folder_exists($folder_name)) {
            return $folder_path;
        }
        
        return false;
    }
    
    /**
     * Obtém a URL de uma pasta do usuário
     * 
     * @param string $folder_name Nome da pasta
     * @return string|false URL da pasta ou false se não existir
     */
    public function get_folder_url($folder_name = '') {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_url = CUM_UPLOAD_URL . $username . '/';
        
        if (empty($folder_name)) {
            return $user_url;
        }
        
        if ($this->folder_exists($folder_name)) {
            return $user_url . $folder_name . '/';
        }
        
        return false;
    }
    
    /**
     * Sanitiza o nome da pasta
     * 
     * @param string $folder_name Nome da pasta
     * @return string Nome sanitizado
     */
    private function sanitize_folder_name($folder_name) {
        // Remove espaços no início e fim
        $folder_name = trim($folder_name);
        
        // Remove caracteres especiais e mantém apenas letras, números, hífens e underscores
        $folder_name = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $folder_name);
        
        // Substitui espaços por hífens
        $folder_name = preg_replace('/\s+/', '-', $folder_name);
        
        // Remove hífens múltiplos
        $folder_name = preg_replace('/-+/', '-', $folder_name);
        
        // Remove hífens no início e fim
        $folder_name = trim($folder_name, '-');
        
        // Limita o tamanho do nome
        $folder_name = substr($folder_name, 0, 50);
        
        return $folder_name;
    }
    
    /**
     * Processa a criação de pasta via POST
     */
    public function handle_folder_creation() {
        if (!isset($_POST['cum_action']) || $_POST['cum_action'] !== 'create_folder') {
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_die('Acesso não autorizado.');
        }
        
        $folder_name = isset($_POST['cum_folder_name']) ? sanitize_text_field($_POST['cum_folder_name']) : '';
        
        if (empty($folder_name)) {
            wp_redirect(add_query_arg('folder_error', 'empty_name', wp_get_referer()));
            exit;
        }
        
        $result = $this->create_folder($folder_name);
        
        if (is_wp_error($result)) {
            $error_code = $result->get_error_code();
            wp_redirect(add_query_arg('folder_error', $error_code, wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('folder_created', 'success', wp_get_referer()));
        }
        
        exit;
    }
    
    /**
     * Obtém arquivos de uma pasta específica
     * 
     * @param string $folder_name Nome da pasta (vazio para pasta raiz)
     * @return array Lista de arquivos
     */
    public function get_folder_files($folder_name = '') {
        if (!is_user_logged_in()) {
            return array();
        }
        
        $folder_path = $this->get_folder_path($folder_name);
        
        if (!$folder_path || !file_exists($folder_path)) {
            return array();
        }
        
        $files = scandir($folder_path);
        $files = array_diff($files, array('.', '..'));
        
        // Remove diretórios da lista, mantém apenas arquivos
        $files = array_filter($files, function($file) use ($folder_path) {
            return is_file($folder_path . $file);
        });
        
        return array_values($files);
    }
}