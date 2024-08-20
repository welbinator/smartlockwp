<?php

class SmartLockWP_Motopress_Metabox {

    private $seam_client;

    public function __construct() {
        $this->seam_client = new SmartLockWP_Seam_Client();
        add_action('add_meta_boxes', array($this, 'add_metabox'));
        add_action('save_post_mphb_room', array($this, 'save_selected_locks'));
    }

    public function add_metabox() {
        add_meta_box(
            'smartlockwp_metabox',               // ID
            'SmartLockWP',                       // Title
            array($this, 'render_metabox'),      // Callback
            'mphb_room',                         // Post type
            'side',                              // Context
            'default'                            // Priority
        );
    }

    public function render_metabox($post) {
        $locks = $this->fetch_seam_locks(); // Fetch the locks from Seam API

        if (empty($locks)) {
            echo '<p>No locks found. Please check your Seam account or API key.</p>';
            return;
        }

        echo '<p>Select the locks associated with this accommodation:</p>';

        // Retrieve selected locks from post meta
        $selected_locks = get_post_meta($post->ID, 'smartlockwp_selected_locks', true);
        if (!is_array($selected_locks)) {
            $selected_locks = array();
        }

        foreach ($locks as $lock) {
           

            $lock_id = esc_attr($lock->device_id);
            $lock_name = esc_html($lock->display_name); // Use display_name for the lock name

            $checked = in_array($lock_id, $selected_locks) ? 'checked="checked"' : '';

            echo '<label>';
            echo '<input type="checkbox" name="smartlockwp_locks[]" value="' . $lock_id . '" ' . $checked . '> ' . $lock_name;
            echo '</label><br>';
        }
    }

    public function save_selected_locks($post_id) {
        if (!isset($_POST['smartlockwp_locks'])) {
            return;
        }

        $selected_locks = array_map('sanitize_text_field', $_POST['smartlockwp_locks']);
        update_post_meta($post_id, 'smartlockwp_selected_locks', $selected_locks);
    }

    private function fetch_seam_locks() {
        try {
            $locks = $this->seam_client->get_client()->locks->list();
           
            return $locks ?? array();
        } catch (Exception $e) {
          
            return array(); // Handle exceptions and return an empty array
        }
    }
}
