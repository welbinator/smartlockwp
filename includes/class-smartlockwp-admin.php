<?php

use Seam\SeamClient;

class SmartLockWP_Admin {

    private $client;

    public function __construct() {
        $this->initialize_seam_client();
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        error_log('SmartLockWP_Admin initialized.');

        // Handle the generate access code action
        if (isset($_POST['smartlockwp_generate_code'])) {
            $this->generate_random_access_code();
        }
    }

    private function initialize_seam_client() {
        $apiKey = get_option('smartlockwp_seam_api_key'); // Get the API key from the stored settings
        if ($apiKey) {
            // Initialize the Seam client
            $this->client = new SeamClient($apiKey);
        }
    }

    public function add_plugin_admin_menu() {
        error_log('add_plugin_admin_menu called.');
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
        error_log('display_plugin_admin_page called.');
        require_once plugin_dir_path(__FILE__) . 'partials/smartlockwp-admin-display.php';
    }

    public function register_settings() {
        error_log('register_settings called.');
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

        add_settings_field(
            'smartlockwp_smart_lock',
            'Choose Smart Lock',
            array($this, 'smart_lock_callback'),
            'smartlockwp',
            'smartlockwp_settings_section'
        );

        add_action('updated_option', array($this, 'validate_api_key'), 10, 3);
    }

    public function validate_api_key($old_value, $new_value, $option) {
        if ($option === 'smartlockwp_seam_api_key') {
            error_log('validate_api_key called.');
            error_log('New API Key: ' . $new_value);

            try {
                $this->client = new SeamClient($new_value);
                error_log('SeamClient initialized.');
                $this->client->locks->list();
                error_log('API Key is valid.');
                add_settings_error('smartlockwp_seam_api_key', 'valid_key', 'API Key Valid', 'updated');
            } catch (Exception $e) {
                error_log('API Key validation failed: ' . $e->getMessage());
                add_settings_error('smartlockwp_seam_api_key', 'invalid_key', 'Invalid API Key: ' . $e->getMessage(), 'error');
            }
        }
    }

    public function seam_api_key_callback() {
        $api_key = get_option('smartlockwp_seam_api_key');
        error_log('seam_api_key_callback called. Retrieved API Key: ' . $api_key);
        ?>
        <input type="text" name="smartlockwp_seam_api_key" value="<?php echo esc_attr($api_key); ?>" size="40">
        <p class="description">Enter your Seam.co API key here.</p>
        <?php
    }

    public function smart_lock_callback() {
        $smart_lock = get_option('smartlockwp_smart_lock');
        error_log('smart_lock_callback called. Retrieved Smart Lock: ' . $smart_lock);
        ?>
        <select name="smartlockwp_smart_lock">
            <option value="august" <?php selected($smart_lock, 'august'); ?>>August</option>
            <!-- Additional options can be added here -->
        </select>
        <?php
    }

    public function generate_random_access_code() {
        $lock_name = 'FRONT DOOR'; // The name of the lock you want to generate a code for
    
        if (!$this->client) {
            $this->initialize_seam_client();
        }
    
        try {
            $locks = $this->client->locks->list();
            foreach ($locks as $lock) {
                if ($lock->display_name === $lock_name) {
                    $access_code = $this->client->access_codes->create(
                        $lock->device_id,
                        allow_external_modification: false, // Specify as boolean
                        code: (string)rand(1000, 9999), // Generate a random 4-digit code
                        name: 'Test Access Code'
                    );
                    error_log('Access code generated: ' . print_r($access_code, true));
                    echo '<div class="notice notice-success"><p>Access code generated successfully: ' . $access_code->code . '</p></div>';
                    return;
                }
            }
            error_log('Lock not found.');
            echo '<div class="notice notice-error"><p>Lock not found.</p></div>';
        } catch (Exception $e) {
            error_log('Failed to generate access code: ' . $e->getMessage());
            echo '<div class="notice notice-error"><p>Failed to generate access code: ' . $e->getMessage() . '</p></div>';
        }
    }
    
}
