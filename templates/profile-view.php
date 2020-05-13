<?php
$sections = [
	'user'    => esc_html__( 'Personal Details', 'woocommerce-tradesafe-gateway' ),
	'company' => esc_html__( 'Company Details', 'woocommerce-tradesafe-gateway' ),
	'bank'    => esc_html__( 'Banking Details', 'woocommerce-tradesafe-gateway' ),
];
?>

<div style="border: 1px solid #FFD700; background-color: #fffbe5; padding: 10px;"><strong>Please Note:</strong> This
	following information is not stored on <strong><?php esc_html_e( get_bloginfo( 'name' ) ); ?></strong> and is
	provided for confirmation purposes only. If you would like update your information please <a
		href="https://<?php echo $tradesafe->domain; ?>/login" target="_blank">login to your account</a> on the TradeSafe Website.
</div>

<br/>

<?php
foreach ( $profile as $section_name => $rows ) {
	printf( '<h3>%s</h3>', $sections[ $section_name ] );
	foreach ( $rows as $field => $row ) {
		printf( '<div class="tradesafe-%s-%s"><strong>%s :</strong> %s</div>', $section_name, $field, esc_attr( $row['title'] ), esc_attr( $row['value'] ) );
	}
	print '<br />';
}

?>

<p><a href="<?php print get_site_url(); ?>/tradesafe/unlink/"
	  class="button"><?php esc_html_e( 'Unlink Account', 'woocommerce-tradesafe-gateway' ); ?></a></p>
