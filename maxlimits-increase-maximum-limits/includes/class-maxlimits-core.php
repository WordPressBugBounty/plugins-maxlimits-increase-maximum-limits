<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MaxLimits_Core {

	private static $instance = null;
	
	public $limit_option_name = 'maxlimits_iml_settings';
	public $advanced_option_name = 'maxlimits_iml_advanced';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// Apply limits on init
		add_action( 'init', [ $this, 'apply_custom_limits' ], 1 );
	}

	/**
	 * Reads options and applies them using ini_set if not using .htaccess mode
	 */
	public function apply_custom_limits() {
		$advanced = get_option( $this->advanced_option_name, [] );
		$limits   = get_option( $this->limit_option_name, [] );

		// We attempt ini_set REGARDLESS of file writing mode for best results.


		$set_ini = function ( $key, $value, $suffix = 'M' ) {
			if ( ! empty( $value ) ) {
				// phpcs:ignore WordPress.PHP.IniSet.Risky
				@ini_set( $key, $value . $suffix );
			}
		};
		
		$set_ini( 'upload_max_filesize', $limits['upload_max_filesize'] ?? null, 'M' );
		$set_ini( 'post_max_size', $limits['post_max_size'] ?? null, 'M' );
		$set_ini( 'memory_limit', $limits['memory_limit'] ?? null, 'M' );
		$set_ini( 'max_execution_time', $limits['max_execution_time'] ?? null, '' );
		$set_ini( 'max_input_time', $limits['max_input_time'] ?? null, '' );
		$set_ini( 'max_input_vars', $limits['max_input_vars'] ?? null, '' );
	}

	/**
	 * Returns the array of available limit options and their values
	 */
	public function get_limit_options() {
		return [
			'upload_max_filesize' => [ 'label'  => 'MB', 'values' => [ 32, 64, 128, 256, 512 ] ],
			'post_max_size'       => [ 'label'  => 'MB', 'values' => [ 32, 64, 128, 256, 512 ] ],
			'memory_limit'        => [ 'label'  => 'MB', 'values' => [ 128, 256, 512 ] ],
			'max_execution_time'  => [ 'label'  => 'Seconds', 'values' => [ 300, 600 ] ],
			'max_input_time'      => [ 'label'  => 'Seconds', 'values' => [ 300, 600 ] ],
			'max_input_vars'      => [ 'label'  => 'Vars', 'values' => [ 1000, 3000 ] ],
		];
	}

	/**
	 * Generates code snippets for .htaccess and .user.ini
	 */
	public function get_ini_code_snippets( $options ) {
		$htaccess_code = "# BEGIN MaxLimits" . PHP_EOL;
		$user_ini_code = "; BEGIN MaxLimits" . PHP_EOL;

		$map = [
			'upload_max_filesize' => [ 'htaccess' => 'php_value upload_max_filesize', 'ini' => 'upload_max_filesize =', 'suffix' => 'M' ],
			'post_max_size'       => [ 'htaccess' => 'php_value post_max_size', 'ini' => 'post_max_size =', 'suffix' => 'M' ],
			'memory_limit'        => [ 'htaccess' => 'php_value memory_limit', 'ini' => 'memory_limit =', 'suffix' => 'M' ],
			'max_execution_time'  => [ 'htaccess' => 'php_value max_execution_time', 'ini' => 'max_execution_time =', 'suffix' => '' ],
			'max_input_time'      => [ 'htaccess' => 'php_value max_input_time', 'ini' => 'max_input_time =', 'suffix' => '' ],
			'max_input_vars'      => [ 'htaccess' => 'php_value max_input_vars', 'ini' => 'max_input_vars =', 'suffix' => '' ],
		];

		foreach ( $map as $key => $data ) {
			if ( ! empty( $options[ $key ] ) ) {
				$value           = $options[ $key ] . $data['suffix'];
				$htaccess_code  .= $data['htaccess'] . ' ' . $value . PHP_EOL;
				$user_ini_code  .= $data['ini'] . ' ' . $value . PHP_EOL;
			}
		}

		$htaccess_code .= "# END MaxLimits";
		$user_ini_code .= "; END MaxLimits";

		return [
			'htaccess' => $htaccess_code,
			'user_ini' => $user_ini_code,
		];
	}
}
