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
        return get_option('smartlockwp_api_key_status') === 'valid';
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
    
        // Directly validate API key on save
        if (isset($_POST['smartlockwp_seam_api_key'])) {
            $new_value = sanitize_text_field($_POST['smartlockwp_seam_api_key']);
            $old_value = get_option('smartlockwp_seam_api_key');
            $this->validate_api_key($old_value, $new_value, 'smartlockwp_seam_api_key');
        }
    }
    
    
    public function validate_api_key($old_value, $new_value, $option) {
       
        if ($option === 'smartlockwp_seam_api_key') {
           
            if (empty($new_value)) {
                // Log and set status to 'none' if no API key is provided
              
                update_option('smartlockwp_api_key_status', 'none');
                return;
            }
    
            try {
                $this->client = new SeamClient($new_value);
                $this->client->locks->list(); // Validate API key
                
                update_option('smartlockwp_api_key_status', 'valid');
            } catch (Exception $e) {
                
                update_option('smartlockwp_api_key_status', 'invalid');
            }
        }
    }
    
    

    public function seam_api_key_callback() {
       
        $api_key = get_option('smartlockwp_seam_api_key');
       
        $status = get_option('smartlockwp_api_key_status', 'none'); // Default to 'none' if missing
       
    
        ?>
        <input type="text" name="smartlockwp_seam_api_key" value="<?php echo esc_attr($api_key); ?>" size="40">
        <p class="description" style="color: <?php echo ($status === 'valid') ? 'green' : 'red'; ?>">
            <?php echo ($status === 'valid') ? 'API Key Valid' : (($status === 'invalid') ? 'API Key Invalid' : 'Enter your Seam.co API key here'); ?>
        </p>
        <?php
    }
    
    

    public function smart_lock_callback() {
        $smart_lock = get_option('smartlockwp_smart_lock');

        echo '<select name="smartlockwp_smart_lock">
                <option value="august" ' . selected($smart_lock, 'august', false) . '>August</option>
                <!-- Additional options can be added here -->
              </select>';
    }
}
