<?php

if ( ! defined('ABSPATH') )
	die();

class Cloud_Vps_Objects_Admin {

	public function __construct() {
		add_action( 'admin_print_styles', array( $this, 'print_stylesheets' ) );

		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	public function print_stylesheets() {
		$screen = get_current_screen();

		if( 'toplevel_page_cloudvps-object-store' == $screen->base ) {
			wp_register_style( 'cloudvpsobject-style', plugins_url( 'css/settings.css', dirname( __FILE__ ) ) );
			wp_enqueue_style( 'cloudvpsobject-style' );
		}
	}

	public function register_page() {
		add_menu_page(
			__( 'CloudVPS Object Store', 'cloudvps-object-store' ),
			__( 'Object Store', 'cloudvps-object-store' ),
			'manage_options',
			'cloudvps-object-store',
			array( $this, 'page_settings' ),
			plugins_url( 'images/cloudobjects16.png', dirname( __FILE__ ) )
		);
	}

	public function page_settings() {
		?>

		<div class="wrap">
			<?php screen_icon('cloudvps-objects'); ?>
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<?php
			$option = get_option( 'cloudvps-object-settings', false );

			if( ! $option ) {
				$option = $this->page_settings_configure();
			}

			if( $option ) {
				$this->page_settings_buckets();
			}
			?>

		</div>

		<?php
	}

	public function page_settings_configure() {
		$username = $password = $projectid = '';

		if ( isset( $_POST['cloudvps_object_nonce'] ) && wp_verify_nonce( $_POST['cloudvps_object_nonce'], 'cloudvps_object_nonce' ) ) {
				$username  = sanitize_text_field( $_POST['cloudobject-username'] );
				$password  = sanitize_text_field( $_POST['cloudobject-password'] );
				$projectid = sanitize_text_field( $_POST['cloudobject-projectid'] );	

			$token = new Cloud_Vps_Objects_Token(
				$username,
				$password,
				$projectid
			);

			if( $token->success ) {
				$option = array(
					'username'  => $username,
					'password'  => $password,
					'projectid' => $projectid
				);

				$result = update_option( 'cloudvps-object-settings', $option );

				if( $result ) {
					echo '<div class="updated"><p><strong>' . __( 'Settings are saved', 'cloudvps-object-store' ) . '</strong></p></div>';

					return $option;
				}
			}

			echo '<div class="error"><p><strong>' . __( "Couldn't connect to the object store. Check the values and try again.", 'cloudvps-object-store' ) . '</strong></p></div>';
		}

		?>
			<h3><?php _e('Access Settings'); ?></h3>

			<p><?php _e( "Start configuring your keypair here so you'll be able to use all the features.", 'cloudvps-object-store' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'cloudvps_object_nonce', 'cloudvps_object_nonce' ); ?>

				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e( 'Username', 'cloudvps-object-store' ); ?></th>
							<td><input type="text" name="cloudobject-username" value="<?php echo $username; ?>" class="regular-text"></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Password', 'cloudvps-object-store' ); ?></th>
							<td><input type="text" name="cloudobject-password" value="<?php echo $password; ?>" class="regular-text"></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Project ID', 'cloudvps-object-store' ); ?></th>
							<td><input type="text" name="cloudobject-projectid" value="<?php echo $projectid; ?>" class="regular-text"></td>
						</tr>
					</tbody>
				</table>

				<?php submit_button(); ?>

			</form>
		<?php
	}


	public function page_settings_buckets() {
		?>
			<h3><?php _e( 'Buckets', 'cloudvps-object-store' ); ?></h3>

			<ul>
			<?php
			$token      = Cloud_Vps_Objects::get_token();
			$containers = $token->containers();

			foreach( $containers as $container ) {
				// bytes
				echo '<li>' . $container->name . ' ( ' . $container->status . ' ) (' . $container->count . ')</li>';
			}
			?>
			</ul>

		<?php
	}
}