<div class="wrap">
    <h1>TradeSafe Settings</h1>

    <h2>Callback URL's</h2>
    <p>The following URL's can be used to register your application with TradeSafe.</p>

    <table class="form-table">
        <tbody>
        <tr>
            <th scope="row">Callback URL</th>
            <td><?php esc_attr_e($urls['callback']); ?></td>
        </tr>
        <tr>
            <th scope="row">Auth Callback URL</th>
            <td><?php esc_attr_e($urls['auth_callback']); ?></td>
        </tr>
        </tbody>
    </table>

    <form method="POST" action="options.php">
		<?php
		settings_fields( 'tradesafe' );
		do_settings_sections( 'tradesafe' );
		submit_button();
		?>
    </form>
</div>