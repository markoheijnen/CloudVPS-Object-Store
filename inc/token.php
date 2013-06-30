<?php

if ( ! defined('ABSPATH') )
	die();

class Cloud_Vps_Objects_Token {
	private $username = '';
	private $password = '';

	public $success = false;
	public $id;
	public $expires;

	public $admin_url;
	public $public_url;
	public $internal_url;
	public $endpoints;

	public function __construct( $username, $password, $tenant_id ) {
		$this->username = $username;
		$this->password = $password;

		$api_url = 'https://identity.stack.cloudvps.com/v2.0/tokens';
		$headers = array( 'Content-Type' => 'application/json' );
		$body    = array(
			'auth' => array(
				'passwordCredentials' => array(
					'username' => $username,
					'password' => $password
				),
				'tenantId' => $tenant_id
			),
		);

		$cache_key = md5( serialize( $body ) );
		$data      = get_transient( $cache_key );

		if ( false === $data ) {
			$body = json_encode( $body );

			$response = wp_remote_post( $api_url , array( 'body' => $body, 'headers' => $headers ) );

			if( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
				$data = json_decode( wp_remote_retrieve_body( $response ) );
				// Remove 30 seconds of a day to be sure
				set_transient( $cache_key, $data, DAY_IN_SECONDS - 30 );
			}
		}

		if( $data ) {
			$this->success      = true;
			$this->id           = $data->access->token->id;
			$this->expires      = $data->access->token->expires;

			$this->endpoints    = $data->access->serviceCatalog[0];
			$this->admin_url    = $data->access->serviceCatalog[0]->endpoints[0]->adminURL;
			$this->public_url   = $data->access->serviceCatalog[0]->endpoints[0]->publicURL;
			$this->internal_url = $data->access->serviceCatalog[0]->endpoints[0]->internalURL;
		}
	}

	public function containers() {
		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password )
		);
		$url = add_query_arg( 'format', 'json', $this->admin_url );

		$cache_key  = md5( $url );
		$containers = get_transient( $cache_key );

		if ( false === $containers ) {
			$response = wp_remote_get( $url, array( 'headers' => $headers ) );

			if( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
				$containers = json_decode( wp_remote_retrieve_body( $response ) );

				foreach( $containers as $container ) {
					$response2 = wp_remote_head( $this->admin_url . '/' . $container->name, array( 'headers' => $headers ) );

					if( ! is_wp_error( $response2 ) && 204 == wp_remote_retrieve_response_code( $response2 ) ) {
						$header = wp_remote_retrieve_header( $response2, 'x-container-read' );

						if( '.r:*,.rlistings' == $header )
							$container->status = __( 'Public', 'cloudvps-object-store' );
						else
							$container->status = __( 'Private', 'cloudvps-object-store' );
					}
					else {
						$container->status = __( 'Unknown', 'cloudvps-object-store' );
					}
				}

				set_transient( $cache_key, $containers, DAY_IN_SECONDS - 30 );

				return $containers;
			}

			return array();
		}

		return $containers;
	}

}