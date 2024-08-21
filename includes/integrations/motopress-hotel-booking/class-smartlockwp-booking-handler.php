<?php

class SmartLockWP_Booking_Handler {

    private $seam_client;

    public function __construct() {
        $this->seam_client = new SmartLockWP_Seam_Client();
        add_action('mphb_booking_confirmed', [$this, 'handle_booking_confirmation'], 10, 2);
    }

    public function handle_booking_confirmation($booking) {
        error_log('handle_booking_confirmation called.');

        $booking_id = $booking->getId();
        error_log('Booking ID: ' . $booking_id);
    
        $reserved_rooms = $booking->getReservedRooms();
        if (empty($reserved_rooms)) {
            error_log('No Room IDs found.');
            return;
        }

        // Get the booking start and end dates from the booking object
        $start_date = $booking->getCheckInDate();
        $end_date = $booking->getCheckOutDate();

        // Get the check-in and check-out times from the MotoPress settings
        $check_in_time = MPHB()->settings()->dateTime()->getCheckInTime();
        $check_out_time = MPHB()->settings()->dateTime()->getCheckOutTime();
        error_log('checkin time: ' . $check_in_time);
        error_log('checkout time: ' . $check_out_time);

        // Combine the dates with the correct times
        $start_time = new DateTime($start_date->format('Y-m-d') . ' ' . $check_in_time, new DateTimeZone('America/New_York'));
        $end_time = new DateTime($end_date->format('Y-m-d') . ' ' . $check_out_time, new DateTimeZone('America/New_York'));

        foreach ($reserved_rooms as $reserved_room) {
            $room_id = $reserved_room->getRoomId();
            error_log('Room ID: ' . $room_id);
    
            $selected_locks = get_post_meta($room_id, '_smartlockwp_selected_locks', true);
            error_log('Raw Selected Locks Data: ' . print_r($selected_locks, true));
    
            if (empty($selected_locks)) {
                error_log('No locks selected for Room ID: ' . $room_id);
                continue;
            }
    
            $customer = $booking->getCustomer();
            $email = $customer->getEmail();
            $first_name = $customer->getFirstName();
            $last_name = $customer->getLastName();
            $label = trim($first_name . ' ' . $last_name);
    
            error_log('Customer Email: ' . $email);
    
            if (!$email) {
                error_log('No email found for Booking ID: ' . $booking_id);
                continue;
            }
    
            foreach ($selected_locks as $lock_id) {
                $lock = $this->seam_client->get_client()->devices->get($lock_id);
                $lock_name = $lock->display_name;
            
                error_log('Lock Name Retrieved: ' . $lock_name);
            
                $access_code = $this->generate_access_code($lock_id, $label, $start_time, $end_time);
                $this->send_access_code_email($email, $access_code, $lock_name);
            }
        }
    }

    public function generate_access_code($lock_id, $label, DateTime $start_time, DateTime $end_time) {
        error_log('Attempting to generate access code for Lock ID: ' . $lock_id);
    
        // Convert to UTC before sending to the Seam API
        $start_time->setTimezone(new DateTimeZone('UTC'));
        $end_time->setTimezone(new DateTimeZone('UTC'));
    
        try {
            $response = $this->seam_client->get_client()->access_codes->create(
                $lock_id, 
                false, 
                null, 
                $this->generate_random_code(), 
                null, 
                $end_time->format('Y-m-d\TH:i:s\Z'), 
                null, 
                null, 
                null, 
                null, 
                $label, 
                null, 
                $start_time->format('Y-m-d\TH:i:s\Z')
            );
    
            error_log('Generated access code: ' . $response->code . ' for Lock ID: ' . $lock_id);
            return $response->code;
        } catch (Exception $e) {
            error_log('Failed to generate access code: ' . $e->getMessage());
            return null;
        }
    }

    private function generate_random_code() {
        return str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function send_access_code_email($email, $access_code, $lock_name) {
        $subject = 'Your Access Code';
        $message = "Your access code for lock $lock_name is: $access_code";
        wp_mail($email, $subject, $message);
        error_log("Access code email sent to: $email");
    }
}
