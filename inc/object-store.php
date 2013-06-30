<?php

class Cloud_Vps_Objects_Store {
	private $token;

	public function __construct( $token ) {
		$this->token = $token;
	}


	public function create_container( $name, $type = 'private' ) {
		if( ! $this->token->success )
			return false;

		$url = $this->token->public_url . '/' . $name;
		$args = array(
			'method' => 'PUT',
			'headers' => array(
				'X-Auth-Token' => $this->token->id
			),
			'timeout' => 60
		);

		if( 'public' == $type )
			$args['headers']['X-Container-Read: .r:*'];

		$response      = wp_remote_request( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if( ! is_wp_error( $response ) && ( 201 == $response_code || 202 == $response_code ) )
			return true;

		return false;
	}

	public function upload_files( $container, $files ) {
		if( ! $this->token->success )
			return false;

		if( is_array( $files ) ) {
			$results = array();

			foreach( $files as $file ) {
				if( is_array( $file ) && isset( $file['path'], $file['urlpath'] ) )
					$results[ $file['path'] ] = $this->upload( $container, $file['path'], $file['urlpath'] );
				else if( ! is_array( $file ) )
					$results[ $file ] = $this->upload( $container, $filepath );				
			}
		}

		return false;
	}

	public function upload( $container, $filepath, $filename = '' ) {
		if( ! $this->token->success )
			return false;

		if( ! is_file( $filepath ) )
			return false;

		if( empty( $filename ) ) {
			$pathinfo = pathinfo( $filepath );
			$filename = $pathinfo['basename'];
		}

		$filename  = ltrim( $filename, '/' );
		$url       = $this->token->public_url . '/' . $container . '/' . $filename;
		$body      = file_get_contents( $filepath );
		$mime_type = '';

		if ( extension_loaded( 'fileinfo' ) ) {
			$finfo = new finfo;
			$mime_type = $finfo->file( $filepath, FILEINFO_MIME );
		}
		elseif ( function_exists('mime_content_type') ) {
			$mime_type = mime_content_type( $filepath );
		}

		$args = array(
			'method'  => 'PUT',
			'headers' => array(
				'Content-Type'           => $mime_type,
				'X-Auth-Token'           => $this->token->id,
				'X-HTTP-Method-Override' => 'PUT'
			),
			'timeout' => 20,
			'body'    => $body,
		);

		$response      = wp_remote_request( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if( ! is_wp_error( $response ) && ( 201 == $response_code || 202 == $response_code ) )
			return true;

		return false;
	}

	public function delete_files( $container, $files ) {
		if( ! $this->token->success )
			return false;

		if( is_array( $files ) ) {
			$results = array();

			foreach( $files as $filepath ) {
				$results[ $filepath ] = $this->delete( $container, $filepath );
			}
		}

		return false;
	}

	public function delete( $container, $filepath ) {
		if( ! $this->token->success )
			return false;

		$filepath = ltrim( $filepath, '/' );
		$url  = $this->token->public_url . '/' . $container . '/' . $filepath;
		$args = array(
			'method' => 'DELETE',
			'headers' => array(
				'X-Auth-Token' => $this->token->id
			)
		);

		$response      = wp_remote_request( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );

		if( ! is_wp_error( $response ) && 204 == $response_code )
			return true;

		return false;
	}

}