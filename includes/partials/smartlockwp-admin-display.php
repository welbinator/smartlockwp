<div class="wrap">
    <h1>SmartLockWP Settings</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('smartlockwp_options');
        do_settings_sections('smartlockwp');
        submit_button();
        ?>
    </form>

    <form method="post" action="">
        <h2>Generate Random Access Code</h2>
        <p>Click the button below to generate a random access code for the FRONT DOOR lock:</p>
        <input type="hidden" name="smartlockwp_generate_code" value="1">
        <?php submit_button('Generate Access Code'); ?>
    </form>
</div>
