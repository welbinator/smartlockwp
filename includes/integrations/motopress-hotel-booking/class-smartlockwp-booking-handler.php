<?php

class SmartLockWP_Booking_Handler {

    private $seam_client;

    public function __construct() {
        $this->seam_client = new SmartLockWP_Seam_Client();
        add_action('mphb_booking_confirmed', [$this, 'handle_booking_confirmation'], 10, 2);
        add_action('mphb_booking_cancelled', [$this, 'handle_booking_cancellation'], 10, 1);
    }

    public function handle_booking_confirmation($booking) {
        error_log("SmartLockWP: Booking confirmed. Booking ID: " . $booking->getId());

        $booking_id = $booking->getId();
        $reserved_rooms = $booking->getReservedRooms();
    
        error_log("SmartLockWP: Booking confirmed. Booking ID: $booking_id, Processing Room ID(s): " . print_r(array_map(fn($room) => $room->getRoomId(), $reserved_rooms), true));

        foreach ($reserved_rooms as $reserved_room) {
            $room_id = $reserved_room->getRoomId();
            error_log("SmartLockWP: Checking selected locks for Room ID $room_id in Booking ID $booking_id.");

            // $selected_locks = get_post_meta($room_id, '_smartlockwp_selected_locks', true);
            $selected_locks = get_post_meta($room_id, '_smartlockwp_selected_locks', false);
            error_log("SmartLockWP: Retrieved selected locks for Room ID $room_id (as array): " . print_r($selected_locks, true));

            error_log("SmartLockWP: Retrieved selected locks for Room ID $room_id: " . print_r($selected_locks, true));
        
        }

        if (empty($reserved_rooms)) {
            error_log("SmartLockWP: No reserved rooms found for Booking ID: $booking_id.");
            return;
        }

        $start_date = $booking->getCheckInDate();
        $end_date = $booking->getCheckOutDate();
        $check_in_time = MPHB()->settings()->dateTime()->getCheckInTime();
        $check_out_time = MPHB()->settings()->dateTime()->getCheckOutTime();

        $start_time = new DateTime($start_date->format('Y-m-d') . ' ' . $check_in_time, new DateTimeZone('America/New_York'));
        $end_time = new DateTime($end_date->format('Y-m-d') . ' ' . $check_out_time, new DateTimeZone('America/New_York'));

        foreach ($reserved_rooms as $reserved_room) {
            $room_id = $reserved_room->getRoomId();
            error_log("SmartLockWP: Processing room ID $room_id for Booking ID $booking_id.");

            // $selected_locks = get_post_meta($room_id, '_smartlockwp_selected_locks', true);
            $selected_locks = get_post_meta($room_id, '_smartlockwp_selected_locks', false);
error_log("SmartLockWP: Retrieved selected locks for Room ID $room_id (as array): " . print_r($selected_locks, true));


            if (empty($selected_locks)) {
                error_log("SmartLockWP: No locks selected for Room ID $room_id.");
                continue;
            }

            $customer = $booking->getCustomer();
            $email = $customer->getEmail();
            $first_name = $customer->getFirstName();
            $last_name = $customer->getLastName();
            $label = trim($first_name . ' ' . $last_name);

            if (!$email) {
                error_log("SmartLockWP: No customer email found for Booking ID $booking_id.");
                continue;
            }

            foreach ($selected_locks as $lock_id_array) {
                $lock_id = is_array($lock_id_array) ? $lock_id_array[0] : $lock_id_array;
                error_log("SmartLockWP: Attempting to generate access code for Lock ID $lock_id with label $label.");
                $access_code = $this->generate_access_code($lock_id, $label, $start_time, $end_time);
                
                if ($access_code) {
                    error_log("SmartLockWP: Access code generated for Lock ID $lock_id: $access_code.");
                    update_post_meta($booking_id, '_smartlockwp_access_code_' . $lock_id, $access_code);
                    $this->send_access_code_email($email, $access_code, $lock_name);
                } else {
                    error_log("SmartLockWP: Failed to generate access code for Lock ID $lock_id.");
                }
            }
            
        }
    }

    public function generate_access_code($lock_id, $label, DateTime $start_time, DateTime $end_time) {
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
                null,  // Preferred code length as null
                null,  // This was previously missing, for prefer_native_scheduling
                $start_time->format('Y-m-d\TH:i:s\Z')
            );
            
    
            return $response->code ?? null;
        } catch (Exception $e) {
            error_log("SmartLockWP: Exception while generating access code for Lock ID $lock_id. Error: " . $e->getMessage());
            return null;
        }
    }
    
    
    

    private function generate_random_code() {
        return (float)str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    
    
}
