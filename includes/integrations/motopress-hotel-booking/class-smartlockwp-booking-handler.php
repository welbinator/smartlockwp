<?php

class SmartLockWP_Booking_Handler {

    private $seam_client;

    public function __construct() {
        $this->seam_client = new SmartLockWP_Seam_Client();
        add_action('mphb_booking_confirmed', [$this, 'handle_booking_confirmation'], 10, 2);
        add_action('mphb_booking_cancelled', [$this, 'handle_booking_cancellation'], 10, 1);
    }

    public function handle_booking_confirmation($booking) {
        $booking_id = $booking->getId();
        $reserved_rooms = $booking->getReservedRooms();
        if (empty($reserved_rooms)) {
            return;
        }

        $start_date = $booking->getCheckInDate();
        $end_date = $booking->getCheckOutDate();
        $check_in_time = MPHB()->settings()->dateTime()->getCheckInTime();
        $check_out_time = MPHB()->settings()->dateTime()->getCheckOutTime();

        $start_time = new DateTime($start_date->format('Y-m-d') . ' ' . $check_in_time, new DateTimeZone('America/Chicago'));
        $end_time = new DateTime($end_date->format('Y-m-d') . ' ' . $check_out_time, new DateTimeZone('America/Chicago'));
        error_log("Check-in Time: " . $check_in_time);
        error_log("Check-out Time: " . $check_out_time);
        error_log("Start Time: " . $start_time->format('Y-m-d H:i:s T'));
error_log("End Time: " . $end_time->format('Y-m-d H:i:s T'));


        foreach ($reserved_rooms as $reserved_room) {
            $room_id = $reserved_room->getRoomId();
            $selected_locks = get_post_meta($room_id, '_smartlockwp_selected_locks', true);

            if (empty($selected_locks)) {
                continue;
            }

            $customer = $booking->getCustomer();
            $email = $customer->getEmail();
            $first_name = $customer->getFirstName();
            $last_name = $customer->getLastName();
            $label = trim($first_name . ' ' . $last_name);

            if (!$email) {
                continue;
            }

            foreach ($selected_locks as $lock_id) {
                $lock = $this->seam_client->get_client()->devices->get($lock_id);
                $lock_name = $lock->display_name;
            
                $access_code = $this->generate_access_code($lock_id, $label, $start_time, $end_time);
                if ($access_code) {
                    update_post_meta($booking_id, '_smartlockwp_access_code_' . $lock_id, $access_code);
                    $this->send_access_code_email($email, $access_code, $lock_name);
                }
            }
        }
    }

    public function handle_booking_cancellation($booking) {
        $this->delete_access_codes($booking);
    }

    private function delete_access_codes($booking) {
        $booking_id = $booking->getId();
        $reserved_rooms = $booking->getReservedRooms();
        
        foreach ($reserved_rooms as $reserved_room) {
            $room_id = $reserved_room->getRoomId();
            $selected_locks = get_post_meta($room_id, '_smartlockwp_selected_locks', true);
            
            if (empty($selected_locks)) {
                continue;
            }

            foreach ($selected_locks as $lock_id) {
                $access_code_id = get_post_meta($booking_id, '_smartlockwp_access_code_' . $lock_id, true);
                if ($access_code_id) {
                    try {
                        $this->seam_client->get_client()->access_codes->delete($access_code_id);
                    } catch (Exception $e) {
                        // Handle the exception if needed
                    }
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
            
            return $response->code;
        } catch (Exception $e) {
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
    }
}
