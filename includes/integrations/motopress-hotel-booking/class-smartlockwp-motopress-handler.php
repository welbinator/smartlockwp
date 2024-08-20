<?php

class SmartLockWP_Motopress_Handler {

    public function __construct() {
        // Hook into the booking completion action (assuming this action exists in MotoPress)
        add_action('mphb_booking_confirmed', array($this, 'handle_booking_confirmation'));
    }

    /**
     * Handle booking confirmation.
     *
     * @param int $booking_id The ID of the confirmed booking.
     */
    public function handle_booking_confirmation($booking_id) {
        // Fetch booking details
        $booking = get_post($booking_id);

        if (!$booking || $booking->post_type !== 'mphb_booking') {
            return; // Exit if this is not a valid booking
        }

        // Here, you would call the method to create an unlock code via the Seam API
        $this->generate_smart_lock_code($booking);
    }

    /**
     * Generate a smart lock code using the Seam API.
     *
     * @param WP_Post $booking The booking post object.
     */
    private function generate_smart_lock_code($booking) {
        // Placeholder function - Here you would interact with the Seam API
        // and create an unlock code based on booking details.

        $room_id = get_post_meta($booking->ID, 'mphb_room_id', true); // Example meta field
        $check_in = get_post_meta($booking->ID, 'mphb_check_in_date', true);
        $check_out = get_post_meta($booking->ID, 'mphb_check_out_date', true);

        // Example: Call the Seam API to create an unlock code
        // You would replace this with actual API interaction code
        $unlock_code = "UNLOCK123"; // Placeholder for the actual unlock code

        // Save the generated unlock code to the booking meta (for reference)
        update_post_meta($booking->ID, 'smartlockwp_unlock_code', $unlock_code);

        // Log or handle errors as needed
    }
}

new SmartLockWP_Motopress_Handler();
