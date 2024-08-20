<?php

// Use statement to include the Seam namespace's Client class
use Seam\SeamClient;

class SmartLockWP_Admin {

    private $client;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
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
        
        register_setting('smartlockwp_options', 'smartlockwp_seam_api_key', array($this, 'validate_api_key'));
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

        add_settings_field(
            'smartlockwp_smart_lock',
            'Choose Smart Lock',
            array($this, 'smart_lock_callback'),
            'smartlockwp',
            'smartlockwp_settings_section'
        );
    }

    public function validate_api_key($new_value) {
       
        try {
            $this->client = new SeamClient($new_value);
            
            $this->client->locks->list();
            
            add_settings_error('smartlockwp_seam_api_key', 'valid_key', 'API Key Valid', 'updated');
        } catch (Exception $e) {
            error_log('API Key validation failed: ' . $e->getMessage());
            add_settings_error('smartlockwp_seam_api_key', 'invalid_key', 'Invalid API Key: ' . $e->getMessage(), 'error');
        }

        return $new_value; // Return the validated API key
    }

    public function seam_api_key_callback() {
        $api_key = get_option('smartlockwp_seam_api_key');
        $status_message = '';
        $status_class = '';
    
        // Check if there are any settings errors related to the API key
        $errors = get_settings_errors('smartlockwp_seam_api_key');
        if (!empty($errors)) {
            foreach ($errors as $error) {
                if ($error['type'] === 'updated') {
                    $status_message = 'API Key is Valid';
                    $status_class = 'valid';
                } else {
                    $status_message = 'API Key is Invalid';
                    $status_class = 'invalid';
                }
            }
        }
    
        ?>
        <input type="text" name="smartlockwp_seam_api_key" value="<?php echo esc_attr($api_key); ?>" size="40">
        <p class="description <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_message ? $status_message : 'Enter your Seam.co API key here.'); ?></p>
    
        <style>
            .description.valid {
                color: green;
            }
            .description.invalid {
                color: red;
            }
        </style>
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
