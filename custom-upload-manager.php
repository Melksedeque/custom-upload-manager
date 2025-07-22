<?php
/*
Plugin Name: Custom Upload Manager
Description: Plugin para gerenciamento de uploads de arquivos por usuários
Version: 1.2
Author: Melksedeque Silva
Author email: freelancer@melksedeque.com.br
Author URI: https://github.com/Melksedeque/
*/

if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('CUM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CUM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CUM_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/associados/');
define('CUM_UPLOAD_URL', content_url('/uploads/associados/'));
define('CUM_FILES_PER_PAGE', 20);

// Inclui os arquivos necessários
require_once CUM_PLUGIN_PATH . 'includes/class-upload-handler.php';
require_once CUM_PLUGIN_PATH . 'includes/class-file-list.php';
require_once CUM_PLUGIN_PATH . 'includes/class-notifications.php';

// Inicializa o plugin
class Custom_Upload_Manager {
    
    private $upload_handler;
    private $file_list;
    private $notifications;
    
    public function __construct() {
        $this->notifications = new CUM_Notifications();
        $this->upload_handler = new CUM_Upload_Handler($this->notifications);
        $this->file_list = new CUM_File_List($this->notifications);
        
        // Hooks e shortcodes
        add_shortcode('custom_upload_form', array($this->upload_handler, 'upload_form_shortcode'));
        add_shortcode('custom_file_list', array($this->file_list, 'file_list_shortcode'));
        
        // Actions
        add_action('init', array($this->upload_handler, 'handle_file_upload'));
        add_action('init', array($this->file_list, 'handle_file_delete'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Redirecionar no logout
        add_action('wp_logout', array($this, 'redirect_after_logout'));
        
        // Cria o diretório de uploads se não existir
        $this->create_upload_directory();
    }
    
    // Cria o diretório de uploads
    private function create_upload_directory() {
        if (!file_exists(CUM_UPLOAD_DIR)) {
            wp_mkdir_p(CUM_UPLOAD_DIR);
        }
    }
    
    // Carrega scripts e estilos
    public function enqueue_assets() {
        wp_enqueue_style(
            'custom-upload-manager',
            CUM_PLUGIN_URL . 'css/style.css',
            array(),
            '1.1'
        );

        wp_enqueue_style('dashicons');

        wp_enqueue_script(
            'custom-upload-manager',
            CUM_PLUGIN_URL . 'js/scripts.js',
            array('jquery'),
            '1.1',
            true
        );
    }

    // Método de redirecionamento no logout
    public function redirect_after_logout() {
        wp_redirect(home_url());
        exit();
    }
}

new Custom_Upload_Manager();