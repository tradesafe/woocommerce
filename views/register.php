<?php
function render_fields( $fields, $count ) {
	foreach ( $fields as $field_name => $field_info ) {
		$field_value = ! empty( $_POST[ $field_name ] ) ? $_POST[ $field_name ] : '';
		?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="<?php print $field_name; ?>"><?php esc_html_e( $field_info[0], 'woocommerce-gateway-tradesafe' ) ?>
				<?php if ( 'html' != $field_info[1] ): ?>
                    <span class="required">*</span>
				<?php endif; ?>
				<?php if ( 'select' == $field_info[1] ): ?>
                    <select step="<?php print $count; ?>"
                            id="<?php print $field_name; ?>"
                            name="<?php print $field_name; ?>"
                            class="input">
						<?php
						foreach ( $field_info[2] as $option_value => $option_name ) {
							if ( $option_value == $field_value ) {
								print '<option selected="selected" value="' . $option_value . '">' . $option_name . '</option>';
							} else {
								print '<option value="' . $option_value . '">' . $option_name . '</option>';
							}
						}
						?>
                    </select>
				<?php elseif ( 'html' == $field_info[1] ): ?>
					<?php print $field_info[2]; ?>
				<?php else: ?>
                    <input type="<?php print $field_info[1]; ?>"
                           step="<?php print $count; ?>"
                           id="<?php print $field_name; ?>"
                           name="<?php print $field_name; ?>"
                           value="<?php echo esc_attr( $field_value ); ?>"
                           class="input input-text"
                    />
				<?php endif; ?>
            </label>
        </p>
		<?php
		$count ++;
	}

	return $count;
}
?>

<?php $count = render_fields($user_fields, 1); ?>

<a href='#why-tradesafe' class='show-more' id='why-tradesafe'>Why do we require your bank account details if you are the
    one buying?</a>
<div class='more-text'>
    <p>Your funds are paid into an independent escrow (trust) account managed by our escrow partners TradeSafe Escrow.
        If the goods or services are not what you ordered, then TradeSafe will refund you. This information is also
        required for regulatory reporting purposes (TradeSafe is accountable to both the South African Reserve Bank and
        the Financial Intelligence Centre). Your bank account details are secured and encrypted with industry leading
        technology standards which can be found in most banks.</p>
    <p>Please ensure you enter your banking account details correctly as neither us nor TradeSafe will be held
        responsible should the funds be paid into another account if you provide incorrect bank account details.</p>
    <p><a href='#why-tradesafe' class='show-more'>Hide</a></p>
</div>

<?php render_fields($bank_fields, $count); ?>

<div class="clear"></div>