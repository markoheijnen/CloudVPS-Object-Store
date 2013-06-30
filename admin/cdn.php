<?php

if ( ! defined('ABSPATH') )
	die();


class Cloud_Vps_Objects_Admin_Cdn {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	public function register_page() {
		add_submenu_page(
			'cloudvps-object-store',
			__( 'CDN', 'cloudvps-object-store' ),
			__( 'CDN', 'cloudvps-object-store' ),
			'manage_options',
			'cloudvps-object-store-cdn',
			array( $this, 'page_settings' )
		);
	}

	public function page_settings() {
		?>

		<div class="wrap">
			<?php screen_icon('cloudvps-objects'); ?>
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<?php
			if ( isset( $_POST['cloudvps_object_cdn_set_nonce'] ) && wp_verify_nonce( $_POST['cloudvps_object_cdn_set_nonce'], 'cloudvps_object_cdn_set_nonce' ) ) {

				$bucket = sanitize_text_field( $_POST['cdn_bucket'] );
				$result = update_option( 'cloudvps-object-cdn-container', $bucket );

				echo '<div class="updated"><p><strong>' . __( 'Bucket has been set', 'cloudvps-object-store' ) . '</strong></p></div>';
			}

			$selected_bucket = get_option( 'cloudvps-object-cdn-container', false );
			$token           = Cloud_Vps_Objects::get_token();
			$containers      = $token->containers();
			$containers      = $this->array_search_value( $containers, 'status',  __( 'Public', 'cloudvps-object-store' ) );
			?>

			<form method="post" class="form-table">
				<?php wp_nonce_field( 'cloudvps_object_cdn_set_nonce', 'cloudvps_object_cdn_set_nonce' ); ?>

				<h3><?php _e( 'Choose bucket', 'cloudvps-object-store' ); ?></h3>
				<p><?php _e( 'Choose the bucket the you want to use for the CDN.', 'cloudvps-object-store' ); ?></p>

				<select name="cdn_bucket">
					<option value=""><?php _e( 'Select bucket', 'cloudvps-object-store' ); ?></option>
					<?php
					foreach( $containers as $container ) {
						$selected = selected( $container->name, $selected_bucket, false );

						echo '<option value="' . $container->name . '"' . $selected .'>';
						echo $container->name . ' (' . $container->count . ')';
						echo '</option>';
					}
					?>
				</select>

				<?php submit_button(); ?>

			</form>

		</div>

		<?php
	}

	function array_search_value( $array, $name, $value ) {
		$found = array();

		foreach ( $array as $key => $val ) {
			if ( $val->$name === $value )
				$found[] = $val;
		}

		return $found;
	}
}