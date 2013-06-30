<?php

if ( ! defined('ABSPATH') )
	die();


class Cloud_Vps_Objects_Cdn {
	private $container = false;

	public function __construct() {
		$this->container = get_option( 'cloudvps-object-cdn-container', false );

		if( $this->container ) {
			add_filter( 'update_attached_file', array( $this, 'update_attached_file' ) );
			add_filter( 'wp_update_attachment_metadata', array( $this, 'update_attachment_metadata' ) );

			add_action( 'delete_attachment', array( $this, 'delete_attachment' ) );
		}
	}

	/**
	 * Update attachment file
	 *
	 *
	 * @param string $attached_file
	 * @return string
	 */
	public function update_attached_file( $attached_file ) {
		$store = Cloud_Vps_Objects::get_store();

		$file_info = $this->locale_path_for_upload( $this->normalize_attachment_file( $attached_file ) );

		$store->upload( $this->container, $attached_file, $file_info['urlpath'] );

		return $attached_file;
	}

	/**
	 * Update attachment metadata filter
	 *
	 * @param array $metadata
	 * @return array
	 */
	function update_attachment_metadata( $metadata ) {
		$store = Cloud_Vps_Objects::get_store();
		$files = array();

		if ( isset( $metadata['file'] ) && isset( $metadata['sizes'] ) )
			$files = array_merge( $files, $this->get_sizes_files( $metadata['file'], $metadata['sizes'] ) );

		$store->upload_files( $this->container, $files );

		return $metadata;
	}

	/**
	 * On attachment delete action
	 *
	 * @param integer $attachment_id
	 */
	public function delete_attachment( $attachment_id ) {
		$store = Cloud_Vps_Objects::get_store();
		$files = $this->get_attachment_files( $attachment_id );
		$files = wp_list_pluck( $files, 'urlpath' );

		$store->delete_files( $this->container, $files );
	}

























	private function normalize_attachment_file( $filepath ) {
		$upload_info = $this->upload_info();

		$filepath = ltrim( str_replace( $upload_info['basedir'], '', $filepath ), '/\\' );

		if ( preg_match( '~(\d{4}/\d{2}/)?[^/]+$~', $filepath, $matches ) )
			$filepath = $matches[0];

		return $filepath;
	}

	private function locale_path_for_upload( $file ) {
		$upload_info = $this->upload_info();

		$filepath = $upload_info['basedir'] . '/' . $file;
		$urlpath  = $upload_info['baseurlpath'] . $file;

		return array( 'path' => $filepath, 'urlpath' => $urlpath );
	}

	private static function upload_info() {
		static $upload_info = null;

		if ( null === $upload_info ) {
			$upload_info = wp_upload_dir();

			if ( empty( $upload_info['error'] ) ) {
				$parse_url = parse_url( $upload_info['baseurl'] );

				if ($parse_url)
					$baseurlpath = ( ! empty( $parse_url['path'] ) ? trim( $parse_url['path'], '/' ) : '' );
				else
					$baseurlpath = 'wp-content/uploads';

				$upload_info['baseurlpath'] = '/' . $baseurlpath . '/';
			}
			else {
				$upload_info = false;
			}
		}

		return $upload_info;
	}


	/**
	 * Returns attachment files by attachment ID
	 *
	 * @param integer $attachment_id
	 * @return array
	 */
	private function get_attachment_files( $attachment_id ) {
		$files = array();

		/**
		 * Get attached file
		 */
		$attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );

		if ( '' != $attached_file ) {
			$files[] = $this->locale_path_for_upload( $attached_file );

			/**
			 * Get backup sizes files
			 */
			$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

			if ( is_array( $backup_sizes ) )
				$files = array_merge( $files, $this->get_sizes_files( $attached_file, $backup_sizes ) );
		}

		/**
		 * Get files from metadata
		 */
		$metadata = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );

		if ( is_array( $metadata ) && isset( $metadata['file'] ) && isset( $metadata['sizes'] ) )
			$files = array_merge( $files, $this->get_sizes_files( $metadata['file'], $metadata['sizes'] ) );

		return $files;
	}

	/**
	 * Returns array of files from sizes array
	 *
	 * @param string $attached_file
	 * @param array $sizes
	 * @return array
	 */
	private function get_sizes_files( $attached_file, $sizes ) {
		$files = array();
		$base_dir = dirname( $attached_file );

		foreach ( (array) $sizes as $size ) {
			if ( isset( $size['file'] ) ) {
				if ( $base_dir )
					$file = $base_dir . '/' . $size['file'];
				else
					$file = $size['file'];

				$files[] = $this->locale_path_for_upload( $file );
			}
		}

		return $files;
	}


}