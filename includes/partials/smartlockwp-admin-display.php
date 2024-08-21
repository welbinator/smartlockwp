<div class="wrap">
    <h1>SmartLockWP Settings</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('smartlockwp_options');
        do_settings_sections('smartlockwp');
        submit_button();
        ?>
    </form>

    
</div>
