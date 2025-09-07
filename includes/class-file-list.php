<?php
class CUM_File_List {
    
    private $notifications;
    private $folder_manager;
    
    public function __construct($notifications, $folder_manager) {
        $this->notifications = $notifications;
        $this->folder_manager = $folder_manager;
    }
    
    public function file_list_shortcode() {
        if (!is_user_logged_in()) {
            return $this->notifications->get_login_message();
        }
        
        $this->notifications->handle_query_notices();
        
        $current_user = wp_get_current_user();
        $username = sanitize_file_name($current_user->user_login);
        
        // Verifica se está navegando em uma pasta específica
        $current_folder = isset($_GET['folder']) ? sanitize_text_field($_GET['folder']) : '';
        
        if (!empty($current_folder) && !$this->folder_manager->folder_exists($current_folder)) {
            $current_folder = '';
        }
        
        $user_dir = $this->folder_manager->get_folder_path($current_folder);
        
        // Verifica se o diretório existe
        if (!$user_dir || !file_exists($user_dir)) {
            return $this->notifications->get_no_files_message();
        }
        
        // Obtém pastas e arquivos
        $items = scandir($user_dir);
        $items = array_diff($items, array('.', '..'));
        
        $folders = array();
        $files = array();
        
        foreach ($items as $item) {
            $item_path = $user_dir . $item;
            if (is_dir($item_path)) {
                $folders[] = $item;
            } else {
                $files[] = $item;
            }
        }
        
        if (empty($folders) && empty($files)) {
            return $this->notifications->get_no_files_message($current_folder);
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
            <!-- Navegação de pastas -->
            <div class="cum-folder-navigation">
                <?php if (!empty($current_folder)): ?>
                    <a href="<?php echo remove_query_arg('folder'); ?>" class="cum-back-button">
                        <span class="dashicons dashicons-arrow-left-alt2"></span> Voltar para pasta raiz
                    </a>
                    <h3>Pasta: <?php echo esc_html($current_folder); ?></h3>
                <?php else: ?>
                    <h3>Meus Arquivos</h3>
                <?php endif; ?>
            </div>
            
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
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Data</th>
                        <th>Opções</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Exibe pastas primeiro (apenas se estivermos na pasta raiz)
                    if (empty($current_folder)):
                        foreach ($folders as $folder): 
                            $folder_path = $user_dir . $folder;
                            $folder_date = date('d/m/Y H:i', filemtime($folder_path));
                            $folder_url = add_query_arg('folder', $folder);
                    ?>
                    <tr class="cum-folder-row">
                        <td>
                            <span class="dashicons dashicons-category"></span>
                            <a href="<?php echo esc_url($folder_url); ?>"><?php echo esc_html($folder); ?></a>
                        </td>
                        <td>Pasta</td>
                        <td><?php echo esc_html($folder_date); ?></td>
                        <td>
                            <a href="<?php echo esc_url($folder_url); ?>" class="button-link" title="Abrir pasta">
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    endif;
                    
                    // Exibe arquivos
                    foreach ($files as $file): 
                        $file_path = $user_dir . $file;
                        $file_url = $this->folder_manager->get_folder_url($current_folder) . rawurlencode($file);
                        $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $file_date = date('d/m/Y H:i', filemtime($file_path));
                    ?>
                    <tr class="cum-file-row">
                        <td>
                            <span class="dashicons dashicons-media-default"></span>
                            <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file); ?></a>
                        </td>
                        <td><?php echo esc_html(strtoupper($file_ext)); ?></td>
                        <td><?php echo esc_html($file_date); ?></td>
                        <td>
                            <form method="post" class="cum-delete-form" style="display: inline;">
                                <input type="hidden" name="cum_action" value="delete_file">
                                <input type="hidden" name="cum_file" value="<?php echo esc_attr($file); ?>">
                                <input type="hidden" name="cum_folder" value="<?php echo esc_attr($current_folder); ?>">
                                <button type="submit" class="button-link" onclick="return confirm('Tem certeza que deseja excluir este arquivo?')" title="Excluir arquivo">
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
        
        // Verifica se o arquivo está em uma pasta específica
        $current_folder = isset($_POST['cum_folder']) ? sanitize_text_field($_POST['cum_folder']) : '';
        
        if (!empty($current_folder) && $this->folder_manager->folder_exists($current_folder)) {
            $user_dir = $this->folder_manager->get_folder_path($current_folder);
        } else {
            $user_dir = $this->folder_manager->get_folder_path();
        }
        
        $file_name = sanitize_file_name($_POST['cum_file']);
        $file_path = $user_dir . $file_name;
        
        if (file_exists($file_path)) {
            if (unlink($file_path)) {
                // Limpa qualquer parâmetro de upload existente na URL e mantém a pasta atual
                $redirect_url = remove_query_arg(['upload', 'upload_error'], wp_get_referer());
                if (!empty($current_folder)) {
                    $redirect_url = add_query_arg('folder', $current_folder, $redirect_url);
                }
                wp_redirect(add_query_arg('delete', 'success', $redirect_url));
                exit;
            }
        }
        
        wp_redirect(add_query_arg('upload_error', 'generic', wp_get_referer()));
        exit;
    }
}