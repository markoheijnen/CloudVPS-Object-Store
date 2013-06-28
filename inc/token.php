<?php

class Cloud_Vps_Objects_Token {
	public $success = false;
	public $id;
	public $expires;

	public $public_url;
	public $internal_url;
	public $endpoints;

	public function __construct( $username, $password, $tenant_id ) {
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
			$this->public_url   = $data->access->serviceCatalog[0]->endpoints[0]->publicURL;
			$this->internal_url = $data->access->serviceCatalog[0]->endpoints[0]->internalURL;
		}
	}

}