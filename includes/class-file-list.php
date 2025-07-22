<?php
class CUM_File_List {
    
    private $notifications;
    
    public function __construct($notifications) {
        $this->notifications = $notifications;
    }
    
    public function file_list_shortcode() {
        if (!is_user_logged_in()) {
            return $this->notifications->get_login_message();
        }
        
        $this->notifications->handle_query_notices();
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        $user_dir = CUM_UPLOAD_DIR . $username . '/';
        
        // Verifica se o diretório existe
        if (!file_exists($user_dir)) {
            return $this->notifications->get_no_files_message();
        }
        
        // Obtém a lista de arquivos
        $files = scandir($user_dir);
        $files = array_diff($files, array('.', '..'));
        
        if (empty($files)) {
            return $this->notifications->get_no_files_message();
        }
        
        // Processa ordenação
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'asc' : 'desc';
        
        $files = $this->sort_files($files, $user_dir, $orderby, $order);
        
        // Paginação
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $total_files = count($files);
        $total_pages = ceil($total_files / CUM_FILES_PER_PAGE);
        $offset = ($current_page - 1) * CUM_FILES_PER_PAGE;
        $files = array_slice($files, $offset, CUM_FILES_PER_PAGE);
        
        ob_start();
        ?>
        <div class="custom-file-list">
            <div class="cum-sorting-options">
                <span>Ordenar por:</span>
                <a href="<?php echo add_query_arg(array('orderby' => 'name', 'order' => $orderby === 'name' && $order === 'asc' ? 'desc' : 'asc')); ?>" class="<?php echo $orderby === 'name' ? 'active' : ''; ?>">
                    Nome <?php echo $orderby === 'name' ? ($order === 'asc' ? '↑' : '↓') : ''; ?>
                </a>
                <a href="<?php echo add_query_arg(array('orderby' => 'date', 'order' => $orderby === 'date' && $order === 'asc' ? 'desc' : 'asc')); ?>" class="<?php echo $orderby === 'date' ? 'active' : ''; ?>">
                    Data <?php echo $orderby === 'date' ? ($order === 'asc' ? '↑' : '↓') : ''; ?>
                </a>
            </div>
            
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
                        $file_url = CUM_UPLOAD_URL . $username . '/' . rawurlencode($file);
                        $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $file_date = date('d/m/Y H:i', filemtime($file_path));
                    ?>
                    <tr>
                        <td><a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file); ?></a></td>
                        <td><?php echo esc_html(strtoupper($file_ext)); ?></td>
                        <td><?php echo esc_html($file_date); ?></td>
                        <td>
                            <form method="post" class="cum-delete-form">
                                <input type="hidden" name="cum_action" value="delete_file">
                                <input type="hidden" name="cum_file" value="<?php echo esc_attr($file); ?>">
                                <button type="submit" class="button-link" onclick="return confirm('Tem certeza que deseja excluir este arquivo?')">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="cum-pagination">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page
                ));
                ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function sort_files($files, $dir, $orderby = 'date', $order = 'desc') {
        $sorted_files = array();
        
        foreach ($files as $file) {
            $sorted_files[$file] = array(
                'name' => $file,
                'date' => filemtime($dir . $file)
            );
        }
        
        if ($orderby === 'name') {
            uasort($sorted_files, function($a, $b) use ($order) {
                return $order === 'asc' ? 
                    strcasecmp($a['name'], $b['name']) : 
                    strcasecmp($b['name'], $a['name']);
            });
        } else {
            uasort($sorted_files, function($a, $b) use ($order) {
                return $order === 'asc' ? 
                    $a['date'] - $b['date'] : 
                    $b['date'] - $a['date'];
            });
        }
        
        return array_keys($sorted_files);
    }
    
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
                wp_redirect(add_query_arg('delete', 'success', wp_get_referer()));
                exit;
            }
        }
        
        wp_redirect(add_query_arg('upload_error', 'error', wp_get_referer()));
        exit;
    }
}