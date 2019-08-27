<div>
    <strong><?php esc_html_e( 'Name' ); ?>: </strong><br/>
	<?php esc_html_e( $request['first_name'] ); ?> <?php esc_html_e( $request['last_name'] ); ?>
</div>
<div>
    <strong><?php esc_html_e( 'Mobile' ); ?>: </strong><br/>
	<?php esc_html_e( $request['mobile'] ); ?>
</div>
<div>
    <strong><?php esc_html_e( 'ID Number' ); ?>: </strong><br/>
	<?php esc_html_e( $request['id_number'] ); ?>
</div>

<input type="hidden" name="auth_key" value="<?php esc_html_e( $_GET['auth_key'] ); ?>">
<input type="hidden" name="verify" value="<?php esc_html_e( $_GET['verify'] ); ?>">

<div class="clear"></div>