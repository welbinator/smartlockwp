<div class="wrap">
    <h1>SmartLockWP Settings</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('smartlockwp_options');  // Ensure the correct settings fields are used
        do_settings_sections('smartlockwp');    // Display the settings sections and fields
        submit_button();                         // Adds a submit button
        ?>
    </form>
</div>
