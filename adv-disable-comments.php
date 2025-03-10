<?php
/**
 * Plugin Name: Advanced Disable Comments
 * Plugin URI: https://d-solutions.vn/disable-comments
 * Description: Disable comments on posts, pages, WooCommerce products, and via API.
 * Version: 1.0.0
 * Author: Bui Thuc Dong
 * Author URI: https://buithucdong.com
 * Text Domain: adv-disable-comments
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADC_VERSION', '1.0.0');
define('ADC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ADC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Advanced_Disable_Comments {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Load plugin options
        $this->options = get_option('adv_disable_comments_options', array(
            'everywhere' => 'off',
            'post' => 'off',
            'page' => 'off',
            'product' => 'off',
            'xml_rpc' => 'off',
            'rest_api' => 'off'
        ));
        
        // Initialize admin
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
        }
        
        // Initialize frontend hooks
        add_action('init', array($this, 'init_hooks'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('adv-disable-comments', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('Disable Comments', 'adv-disable-comments'),
            __('Disable Comments', 'adv-disable-comments'),
            'manage_options',
            'disable-comments',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'adv_disable_comments',
            'adv_disable_comments_options',
            array($this, 'sanitize_options')
        );
        
        add_settings_section(
            'adv_disable_comments_section',
            __('Disable Comments Settings', 'adv-disable-comments'),
            array($this, 'settings_section_callback'),
            'disable-comments'
        );
        
        add_settings_field(
            'everywhere',
            __('Everywhere', 'adv-disable-comments'),
            array($this, 'checkbox_callback'),
            'disable-comments',
            'adv_disable_comments_section',
            array('id' => 'everywhere', 'description' => __('Disable comments everywhere (enables all options below)', 'adv-disable-comments'))
        );
        
        add_settings_field(
            'post',
            __('Posts', 'adv-disable-comments'),
            array($this, 'checkbox_callback'),
            'disable-comments',
            'adv_disable_comments_section',
            array('id' => 'post', 'description' => __('Disable comments on posts', 'adv-disable-comments'))
        );
        
        add_settings_field(
            'page',
            __('Pages', 'adv-disable-comments'),
            array($this, 'checkbox_callback'),
            'disable-comments',
            'adv_disable_comments_section',
            array('id' => 'page', 'description' => __('Disable comments on pages', 'adv-disable-comments'))
        );
        
        add_settings_field(
            'product',
            __('WooCommerce Products', 'adv-disable-comments'),
            array($this, 'checkbox_callback'),
            'disable-comments',
            'adv_disable_comments_section',
            array('id' => 'product', 'description' => __('Disable comments on WooCommerce products', 'adv-disable-comments'))
        );
        
        add_settings_field(
            'api_section',
            __('API Settings', 'adv-disable-comments'),
            array($this, 'api_section_callback'),
            'disable-comments',
            'adv_disable_comments_section'
        );
        
        add_settings_field(
            'xml_rpc',
            __('XML-RPC', 'adv-disable-comments'),
            array($this, 'checkbox_callback'),
            'disable-comments',
            'adv_disable_comments_section',
            array('id' => 'xml_rpc', 'description' => __('Disable comments via XML-RPC', 'adv-disable-comments'))
        );
        
        add_settings_field(
            'rest_api',
            __('REST API', 'adv-disable-comments'),
            array($this, 'checkbox_callback'),
            'disable-comments',
            'adv_disable_comments_section',
            array('id' => 'rest_api', 'description' => __('Disable comments via REST API', 'adv-disable-comments'))
        );
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $output = array();
        
        // First handle the 'everywhere' option
        $output['everywhere'] = isset($input['everywhere']) ? 'on' : 'off';
        
        // If 'everywhere' is checked, set all other options to 'on'
        if ($output['everywhere'] === 'on') {
            $output['post'] = 'on';
            $output['page'] = 'on';
            $output['product'] = 'on';
            $output['xml_rpc'] = 'on';
            $output['rest_api'] = 'on';
        } else {
            // Otherwise, sanitize each option individually
            $output['post'] = isset($input['post']) ? 'on' : 'off';
            $output['page'] = isset($input['page']) ? 'on' : 'off';
            $output['product'] = isset($input['product']) ? 'on' : 'off';
            $output['xml_rpc'] = isset($input['xml_rpc']) ? 'on' : 'off';
            $output['rest_api'] = isset($input['rest_api']) ? 'on' : 'off';
        }
        
        return $output;
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Select where you want to disable comments:', 'adv-disable-comments') . '</p>';
    }
    
    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<h3>' . __('API Settings', 'adv-disable-comments') . '</h3>';
    }
    
    /**
     * Checkbox field callback
     */
    public function checkbox_callback($args) {
        $id = $args['id'];
        $description = $args['description'];
        
        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="adv_disable_comments_options[' . esc_attr($id) . ']" ' . checked($this->options[$id], 'on', false) . ' />';
        echo '<label for="' . esc_attr($id) . '">' . esc_html($description) . '</label>';
        
        // Add JavaScript to handle the "Everywhere" checkbox
        if ($id === 'everywhere') {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    const everywhereCheckbox = $('#everywhere');
                    const otherCheckboxes = $('#post, #page, #product, #xml_rpc, #rest_api');
                    
                    // When "Everywhere" is checked/unchecked
                    everywhereCheckbox.on('change', function() {
                        if (this.checked) {
                            otherCheckboxes.prop('checked', true).prop('disabled', true);
                        } else {
                            otherCheckboxes.prop('disabled', false);
                        }
                    });
                    
                    // Initialize state
                    if (everywhereCheckbox.is(':checked')) {
                        otherCheckboxes.prop('checked', true).prop('disabled', true);
                    }
                });
            </script>
            <?php
        }
    }
    
    /**
     * Render the settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('adv_disable_comments');
                do_settings_sections('disable-comments');
                submit_button(__('Save Settings', 'adv-disable-comments'));
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Initialize hooks based on settings
     */
    public function init_hooks() {
        // Disable comments on specific post types
        if ($this->is_disabled_for('post') || $this->is_disabled_for('page') || $this->is_disabled_for('product')) {
            add_action('admin_init', array($this, 'disable_admin_comment_ui'));
            add_filter('comments_open', array($this, 'filter_comment_status'), 20, 2);
            add_filter('pings_open', array($this, 'filter_comment_status'), 20, 2);
            add_filter('comments_array', array($this, 'filter_comments_array'), 10, 2);
            add_action('template_redirect', array($this, 'filter_query'), 9);
            add_action('admin_init', array($this, 'remove_comment_support'));
        }
        
        // Disable comments via XML-RPC
        if ($this->is_disabled_for('xml_rpc')) {
            add_filter('xmlrpc_methods', array($this, 'disable_xmlrpc_comments'));
        }
        
        // Disable comments via REST API
        if ($this->is_disabled_for('rest_api')) {
            add_filter('rest_endpoints', array($this, 'disable_rest_endpoints'));
        }
    }
    
    /**
     * Check if comments are disabled for a specific type
     */
    private function is_disabled_for($type) {
        return $this->options[$type] === 'on';
    }
    
    /**
     * Get post types for which comments are disabled
     */
    private function get_disabled_post_types() {
        $types = array();
        
        if ($this->is_disabled_for('post')) {
            $types[] = 'post';
        }
        
        if ($this->is_disabled_for('page')) {
            $types[] = 'page';
        }
        
        if ($this->is_disabled_for('product') && post_type_exists('product')) {
            $types[] = 'product';
        }
        
        return $types;
    }
    
    /**
     * Remove comment support from post types
     */
    public function remove_comment_support() {
        $post_types = $this->get_disabled_post_types();
        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }
    
    /**
     * Disable admin comment UI
     */
    public function disable_admin_comment_ui() {
        $post_types = $this->get_disabled_post_types();
        
        // Remove admin menu items
        if (count($post_types) > 0) {
            remove_menu_page('edit-comments.php');
            
            // Remove Recent Comments dashboard widget
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
            
            // Filter dashboard items
            add_filter('wp_count_comments', array($this, 'filter_wp_count_comments'), 10, 2);
            
            // Disable commenting settings - Discussion Page
            add_filter('pre_option_default_comment_status', function() { return 'closed'; });
            add_filter('pre_option_default_ping_status', function() { return 'closed'; });
        }
    }
    
    /**
     * Filter comment status for disabled post types
     */
    public function filter_comment_status($open, $post_id) {
        $post = get_post($post_id);
        if ($post && in_array($post->post_type, $this->get_disabled_post_types())) {
            return false;
        }
        return $open;
    }
    
    /**
     * Filter comment count
     */
    public function filter_wp_count_comments($stats, $post_id) {
        global $wpdb;
        
        $post_types = $this->get_disabled_post_types();
        
        if (!empty($post_types)) {
            $post_type_placeholders = implode(', ', array_fill(0, count($post_types), '%s'));
            
            $sql = "SELECT comment_approved, COUNT(*) AS num_comments 
                    FROM $wpdb->comments 
                    WHERE comment_post_ID IN (
                        SELECT ID FROM $wpdb->posts 
                        WHERE post_type NOT IN ($post_type_placeholders)
                    ) 
                    GROUP BY comment_approved";
                    
            $args = $post_types;
            $counts = $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A);
            
            $stats = array(
                'approved' => 0,
                'moderated' => 0,
                'spam' => 0,
                'trash' => 0,
                'total_comments' => 0
            );
            
            foreach ($counts as $row) {
                switch ($row['comment_approved']) {
                    case '1':
                        $stats['approved'] = $row['num_comments'];
                        $stats['total_comments'] += $row['num_comments'];
                        break;
                    case '0':
                        $stats['moderated'] = $row['num_comments'];
                        $stats['total_comments'] += $row['num_comments'];
                        break;
                    case 'spam':
                        $stats['spam'] = $row['num_comments'];
                        $stats['total_comments'] += $row['num_comments'];
                        break;
                    case 'trash':
                        $stats['trash'] = $row['num_comments'];
                        $stats['total_comments'] += $row['num_comments'];
                        break;
                }
            }
            
            return (object) $stats;
        }
        
        return $stats;
    }
    
    /**
     * Filter comments array
     */
    public function filter_comments_array($comments, $post_id) {
        $post = get_post($post_id);
        if ($post && in_array($post->post_type, $this->get_disabled_post_types())) {
            return array();
        }
        return $comments;
    }
    
    /**
     * Filter comment query for disabled post types
     */
    public function filter_query() {
        if (is_singular() && in_array(get_post_type(), $this->get_disabled_post_types())) {
            wp_redirect(get_permalink(), 301);
            exit;
        }
    }
    
    /**
     * Disable XMLRPC comment methods
     */
    public function disable_xmlrpc_comments($methods) {
        unset($methods['wp.newComment']);
        unset($methods['wp.getCommentCount']);
        unset($methods['wp.getComment']);
        unset($methods['wp.getComments']);
        unset($methods['wp.deleteComment']);
        unset($methods['wp.editComment']);
        unset($methods['wp.newComment']);
        unset($methods['wp.getCommentStatusList']);
        
        return $methods;
    }
    
    /**
     * Disable REST API comment endpoints
     */
    public function disable_rest_endpoints($endpoints) {
        if (isset($endpoints['/wp/v2/comments'])) {
            unset($endpoints['/wp/v2/comments']);
        }
        if (isset($endpoints['/wp/v2/comments/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
        }
        
        return $endpoints;
    }
}

// Initialize the plugin
function advanced_disable_comments_init() {
    return Advanced_Disable_Comments::get_instance();
}

// Start the plugin
advanced_disable_comments_init();