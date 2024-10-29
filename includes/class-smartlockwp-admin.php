<?php

use Seam\SeamClient;

class SmartLockWP_Admin {

    private $client;

    public function __construct() {
        $this->initialize_seam_client();
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    private function initialize_seam_client() {
        $apiKey = get_option('smartlockwp_seam_api_key');
        if ($apiKey) {
            $this->client = new SeamClient($apiKey);
        }
    }
    
    public function is_api_key_valid() {
        return isset($this->client);
    }
    

    public function add_plugin_admin_menu() {
        
        add_menu_page(
            'SmartLockWP Settings',
            'SmartLockWP',
            'manage_options',
            'smartlockwp',
            array($this, 'display_plugin_admin_page'),
            'dashicons-lock',
            20
        );
    }

    public function display_plugin_admin_page() {
       
        require_once plugin_dir_path(__FILE__) . 'partials/smartlockwp-admin-display.php';
    }

    public function register_settings() {
        
        register_setting('smartlockwp_options', 'smartlockwp_seam_api_key');
        register_setting('smartlockwp_options', 'smartlockwp_smart_lock');

        add_settings_section(
            'smartlockwp_settings_section',
            'SmartLock Settings',
            null,
            'smartlockwp'
        );

        add_settings_field(
            'smartlockwp_seam_api_key',
            'Seam.co API Key',
            array($this, 'seam_api_key_callback'),
            'smartlockwp',
            'smartlockwp_settings_section'
        );

       

        add_action('updated_option', array($this, 'validate_api_key'), 10, 3);
    }

    public function validate_api_key($old_value, $new_value, $option) {
        if ($option === 'smartlockwp_seam_api_key') {
            

            try {
                $this->client = new SeamClient($new_value);
                
                $this->client->locks->list();
                
                add_settings_error('smartlockwp_seam_api_key', 'valid_key', 'API Key Valid', 'updated');
            } catch (Exception $e) {
                
                add_settings_error('smartlockwp_seam_api_key', 'invalid_key', 'Invalid API Key: ' . $e->getMessage(), 'error');
            }
        }
    }

    public function seam_api_key_callback() {
        $api_key = get_option('smartlockwp_seam_api_key');
        
        ?>
        <input type="text" name="smartlockwp_seam_api_key" value="<?php echo esc_attr($api_key); ?>" size="40">
        <p class="description">Enter your Seam.co API key here.</p>
        <?php
    }

    public function smart_lock_callback() {
        $smart_lock = get_option('smartlockwp_smart_lock');
        
        ?>
        <select name="smartlockwp_smart_lock">
            <option value="august" <?php selected($smart_lock, 'august'); ?>>August</option>
            <!-- Additional options can be added here -->
        </select>
        <?php
    }
    
}
