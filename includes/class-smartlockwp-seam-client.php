<?php

$autoload_path = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoload_path)) {
    die('Autoload file not found.');
}

require $autoload_path;

// Use the correct namespace for the SeamClient
use Seam\SeamClient;

class SmartLockWP_Seam_Client {

    private $client;

    public function __construct() {
        // Fetch the API key from the WordPress options table
        $api_key = get_option('smartlockwp_seam_api_key');

        // Initialize the Seam API client with the API key
        $this->client = new SeamClient($api_key);
    }

    public function get_client() {
        return $this->client;
    }
}
