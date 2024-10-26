<?php

$autoload_path = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoload_path)) {
    die('Autoload file not found.');
}

require $autoload_path;

use Seam\SeamClient;

class SmartLockWP_Seam_Client {

    private $client;

    public function __construct() {
        $api_key = get_option('smartlockwp_seam_api_key');
        
        // Only initialize if the API key is available
        if (!empty($api_key)) {
            $this->client = new SeamClient($api_key);
        }
    }

    public function get_client() {
        if (!$this->client) {
            throw new \Exception('Seam API client is not initialized. Ensure the API key is set in the plugin settings.');
        }
        return $this->client;
    }
}

