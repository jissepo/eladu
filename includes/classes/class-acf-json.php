<?php
namespace JJ;
/**
 * Acf_Controller class
 */
class Acf_Controller {
	/**
	 * Acf_Controller constructor.
	 */
	public function __construct() {
		$this->init_local_json();
	}
	/**
	 * Initialize filters for the ACF local JSON functionality.
	 */
	private function init_local_json() {
		add_filter( 'acf/settings/save_json', [ $this, 'local_acf_json_save_point' ] );
		add_filter( 'acf/settings/load_json', [ $this, 'local_acf_json_load_point' ] );
	}
	/** Returns a custom path where to save the ACF Local JSON files.
	 *
	 * @return string
	 */
	public function local_acf_json_save_point() {
		$path = get_stylesheet_directory() . '/includes/acf-fields-json/';

		return $path;
	}

	/** Returns the custom paths where to look for the local JSON files for ACF.
	 *
	 * @param array $paths Default local JSON files path.
	 *
	 * @return array
	 */
	public function local_acf_json_load_point( $paths ) {
		$paths[] = get_stylesheet_directory() . '/includes/acf-fields-json/';

		return $paths;
	}
}
