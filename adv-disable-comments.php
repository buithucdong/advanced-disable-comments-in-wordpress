<?php
/**
 * Plugin Name: Advanced Disable Comments
 * Plugin URI: https://d-solutions.vn/disable-comments
 * Description: Disable comments on posts, pages, WooCommerce products, and via API.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Bui Thuc Dong
 * Author URI: https://buithucdong.com
 * Text Domain: adv-disable-comments
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ADC_VERSION', '1.0.1' );
define( 'ADC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ADC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

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
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Load plugin text domain
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        
        // Load plugin options
        $this->options = get_option( 'adv_disable_comments_options', array(
            'everywhere' => 'off',
            'post'       => 'off',
            'page'       => 'off',
            'product'    => 'off',
            'xml_rpc'    => 'off',
            'rest_api'   => 'off'
        ) );
        
        // Initialize admin
        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        }
        
        // Initialize frontend hooks
        add_action( 'init', array( $this, 'init_hooks' ) );
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 
            'adv-disable-comments', 
            false, 
            dirname( plugin_basename( __FILE__ ) ) . '/languages' 
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        // Chỉ load trên trang settings của plugin
        if ( 'settings_page_disable-comments' !== $hook ) {
            return;
        }
        
        wp_enqueue_script(
            'adv-disable-comments-admin',
            ADC_PLUGIN_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            ADC_VERSION,
            true
        );
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __( 'Disable Comments', 'adv-disable-comments' ),
            __( 'Disable Comments', 'adv-disable-comments' ),
            'manage_options',
            'disable-comments',
            array( $this, 'settings_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'adv_disable_comments',
            'adv_disable_comments_options',
            array( 
                'sanitize_callback' => array( $this, 'sanitize_options' ),
                'default'           => array(
                    'everywhere' => 'off',
                    'post'       => 'off',
                    'page'       => 'off',
                    'product'    => 'off',
                    'xml_rpc'    => 'off',
                    'rest_api'   => 'off'
                )
            )
        );
        
        add_settings_section(
            'adv_disable_comments_section',
            __( 'Disable Comments Settings', 'adv-disable-comments' ),
            array( $this, 'settings_section_callback' ),
            'disable-comments'
        );
        
        add_settings_field(
            'everywhere',
            __( 'Everywhere', 'adv-disable-comments' ),
            array( $this, 'checkbox_callback' ),
            'disable-comments',
            'adv_disable_comments_section',
            array( 
                'id'          => 'everywhere', 
                'description' => __( 'Disable comments everywhere (enables all options below)', 'adv-disable-comments' ) 
            )
        );
        
        add_settings_field(
            'post',
            __( 'Posts', 'adv-disable-comments' ),
            array( $this, 'checkbox_callback' ),
            'disable-comments',
            'adv_disable_comments_section',
            array( 
                'id'          => 'post', 
                'description' => __( 'Disable comments on posts', 'adv-disable-comments' ) 
            )
        );
        
        add_settings_field(
            'page',
            __( 'Pages', 'adv-disable-comments' ),
            array( $this, 'checkbox_callback' ),
            'disable-comments',
            'adv_disable_comments_section',
            array( 
                'id'          => 'page', 
                'description' => __( 'Disable comments on pages', 'adv-disable-comments' ) 
            )
        );
        
        add_settings_field(
            'product',
            __( 'WooCommerce Products', 'adv-disable-comments' ),
            array( $this, 'checkbox_callback' ),
            'disable-comments',
            'adv_disable_comments_section',
            array( 
                'id'          => 'product', 
                'description' => __( 'Disable comments on WooCommerce products', 'adv-disable-comments' ) 
            )
        );
        
        add_settings_field(
            'api_section',
            __( 'API Settings', 'adv-disable-comments' ),
            array( $this, 'api_section_callback' ),
            'disable-comments',
            'adv_disable_comments_section'
        );
        
        add_settings_field(
            'xml_rpc',
            __( 'XML-RPC', 'adv-disable-comments' ),
            array( $this, 'checkbox_callback' ),
            'disable-comments',
            'adv_disable_comments_section',
            array( 
                'id'          => 'xml_rpc', 
                'description' => __( 'Disable comments via XML-RPC', 'adv-disable-comments' ) 
            )
        );
        
        add_settings_field(
            'rest_api',
            __( 'REST API', 'adv-disable-comments' ),
            array( $this, 'checkbox_callback' ),
            'disable-comments',
            'adv_disable_comments_section',
            array( 
                'id'          => 'rest_api', 
                'description' => __( 'Disable comments via REST API', 'adv-disable-comments' ) 
            )
        );
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options( $input ) {
        if ( ! is_array( $input ) ) {
            $input = array();
        }
        
        $output = array();
        
        // Sanitize 'everywhere' option
        $output['everywhere'] = isset( $input['everywhere'] ) ? 'on' : 'off';
        
        // If 'everywhere' is checked, set all other options to 'on'
        if ( $output['everywhere'] === 'on' ) {
            $output['post']     = 'on';
            $output['page']     = 'on';
            $output['product']  = 'on';
            $output['xml_rpc']  = 'on';
            $output['rest_api'] = 'on';
        } else {
            // Otherwise, sanitize each option individually
            $output['post']     = isset( $input['post'] ) ? 'on' : 'off';
            $output['page']     = isset( $input['page'] ) ? 'on' : 'off';
            $output['product']  = isset( $input['product'] ) ? 'on' : 'off';
            $output['xml_rpc']  = isset( $input['xml_rpc'] ) ? 'on' : 'off';
            $output['rest_api'] = isset( $input['rest_api'] ) ? 'on' : 'off';
        }
        
        return $output;
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Select where you want to disable comments:', 'adv-disable-comments' ) . '</p>';
    }
    
    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<h3>' . esc_html__( 'API Settings', 'adv-disable-comments' ) . '</h3>';
    }
    
    /**
     * Checkbox field callback
     */
    public function checkbox_callback( $args ) {
        $id          = isset( $args['id'] ) ? $args['id'] : '';
        $description = isset( $args['description'] ) ? $args['description'] : '';
        
        // Kiểm tra giá trị option
        $checked = isset( $this->options[ $id ] ) && $this->options[ $id ] === 'on';
        
        printf(
            '<input type="checkbox" id="%s" name="adv_disable_comments_options[%s]" %s />',
            esc_attr( $id ),
            esc_attr( $id ),
            checked( $checked, true, false )
        );
        
        printf(
            '<label for="%s">%s</label>',
            esc_attr( $id ),
            esc_html( $description )
        );
    }
    
    /**
     * Render the settings page
     */
    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'adv-disable-comments' ) );
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'adv_disable_comments' );
                do_settings_sections( 'disable-comments' );
                submit_button( __( 'Save Settings', 'adv-disable-comments' ) );
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
        if ( $this->is_disabled_for( 'post' ) || $this->is_disabled_for( 'page' ) || $this->is_disabled_for( 'product' ) ) {
            add_action( 'admin_init', array( $this, 'disable_admin_comment_ui' ) );
            add_filter( 'comments_open', array( $this, 'filter_comment_status' ), 20, 2 );
            add_filter( 'pings_open', array( $this, 'filter_comment_status' ), 20, 2 );
            add_filter( 'comments_array', array( $this, 'filter_comments_array' ), 10, 2 );
            add_action( 'admin_init', array( $this, 'remove_comment_support' ) );
            
            // Ẩn comment form và comment list trên frontend
            add_filter( 'comments_template', array( $this, 'disable_comments_template' ), 20 );
        }
        
        // Disable comments via XML-RPC
        if ( $this->is_disabled_for( 'xml_rpc' ) ) {
            add_filter( 'xmlrpc_methods', array( $this, 'disable_xmlrpc_comments' ) );
        }
        
        // Disable comments via REST API
        if ( $this->is_disabled_for( 'rest_api' ) ) {
            add_filter( 'rest_endpoints', array( $this, 'disable_rest_endpoints' ) );
        }
    }
    
    /**
     * Check if comments are disabled for a specific type
     */
    private function is_disabled_for( $type ) {
        return isset( $this->options[ $type ] ) && $this->options[ $type ] === 'on';
    }
    
    /**
     * Get post types for which comments are disabled
     */
    private function get_disabled_post_types() {
        $types = array();
        
        if ( $this->is_disabled_for( 'post' ) ) {
            $types[] = 'post';
        }
        
        if ( $this->is_disabled_for( 'page' ) ) {
            $types[] = 'page';
        }
        
        if ( $this->is_disabled_for( 'product' ) && post_type_exists( 'product' ) ) {
            $types[] = 'product';
        }
        
        return $types;
    }
    
    /**
     * Remove comment support from post types
     */
    public function remove_comment_support() {
        $post_types = $this->get_disabled_post_types();
        
        foreach ( $post_types as $post_type ) {
            if ( post_type_supports( $post_type, 'comments' ) ) {
                remove_post_type_support( $post_type, 'comments' );
                remove_post_type_support( $post_type, 'trackbacks' );
            }
        }
    }
    
    /**
     * Disable admin comment UI
     */
    public function disable_admin_comment_ui() {
        $post_types = $this->get_disabled_post_types();
        
        // Remove admin menu items
        if ( count( $post_types ) > 0 ) {
            remove_menu_page( 'edit-comments.php' );
            
            // Remove Recent Comments dashboard widget
            remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
            
            // Filter dashboard items
            add_filter( 'wp_count_comments', array( $this, 'filter_wp_count_comments' ), 10, 2 );
            
            // Disable commenting settings
            add_filter( 'pre_option_default_comment_status', '__return_false' );
            add_filter( 'pre_option_default_ping_status', '__return_false' );
        }
    }
    
    /**
     * Filter comment status for disabled post types
     */
    public function filter_comment_status( $open, $post_id ) {
        $post = get_post( $post_id );
        
        if ( $post && in_array( $post->post_type, $this->get_disabled_post_types(), true ) ) {
            return false;
        }
        
        return $open;
    }
    
    /**
     * Filter comment count
     */
    public function filter_wp_count_comments( $stats, $post_id ) {
        global $wpdb;
        
        $post_types = $this->get_disabled_post_types();
        
        if ( empty( $post_types ) ) {
            return $stats;
        }
        
        // Tạo placeholders an toàn cho prepared statement
        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        
        $sql = "SELECT comment_approved, COUNT(*) AS num_comments 
                FROM {$wpdb->comments} 
                WHERE comment_post_ID IN (
                    SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type NOT IN ({$placeholders})
                ) 
                GROUP BY comment_approved";
        
        // Prepare query an toàn
        $prepared_sql = $wpdb->prepare( $sql, $post_types );
        $counts = $wpdb->get_results( $prepared_sql, ARRAY_A );
        
        // Khởi tạo stats
        $new_stats = array(
            'approved'       => 0,
            'moderated'      => 0,
            'spam'           => 0,
            'trash'          => 0,
            'total_comments' => 0,
            'all'            => 0
        );
        
        if ( $counts ) {
            foreach ( $counts as $row ) {
                $num = (int) $row['num_comments'];
                
                switch ( $row['comment_approved'] ) {
                    case '1':
                        $new_stats['approved'] = $num;
                        break;
                    case '0':
                        $new_stats['moderated'] = $num;
                        break;
                    case 'spam':
                        $new_stats['spam'] = $num;
                        break;
                    case 'trash':
                        $new_stats['trash'] = $num;
                        break;
                }
            }
            
            $new_stats['total_comments'] = $new_stats['approved'] + $new_stats['moderated'];
            $new_stats['all'] = $new_stats['total_comments'] + $new_stats['spam'] + $new_stats['trash'];
        }
        
        return (object) $new_stats;
    }
    
    /**
     * Filter comments array
     */
    public function filter_comments_array( $comments, $post_id ) {
        $post = get_post( $post_id );
        
        if ( $post && in_array( $post->post_type, $this->get_disabled_post_types(), true ) ) {
            return array();
        }
        
        return $comments;
    }
    
    /**
     * Disable comments template on frontend
     */
    public function disable_comments_template( $template ) {
        global $post;
        
        if ( $post && in_array( $post->post_type, $this->get_disabled_post_types(), true ) ) {
            // Trả về template rỗng
            return dirname( __FILE__ ) . '/templates/comments-disabled.php';
        }
        
        return $template;
    }
    
    /**
     * Disable XMLRPC comment methods
     */
    public function disable_xmlrpc_comments( $methods ) {
        $comment_methods = array(
            'wp.newComment',
            'wp.getCommentCount',
            'wp.getComment',
            'wp.getComments',
            'wp.deleteComment',
            'wp.editComment',
            'wp.getCommentStatusList'
        );
        
        foreach ( $comment_methods as $method ) {
            unset( $methods[ $method ] );
        }
        
        return $methods;
    }
    
    /**
     * Disable REST API comment endpoints
     */
    public function disable_rest_endpoints( $endpoints ) {
        $comment_endpoints = array(
            '/wp/v2/comments',
            '/wp/v2/comments/(?P<id>[\d]+)'
        );
        
        foreach ( $comment_endpoints as $endpoint ) {
            if ( isset( $endpoints[ $endpoint ] ) ) {
                unset( $endpoints[ $endpoint ] );
            }
        }
        
        return $endpoints;
    }
}

// Initialize the plugin
function advanced_disable_comments_init() {
    return Advanced_Disable_Comments::get_instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'advanced_disable_comments_init', 5 );