<?php

namespace Stackonet\WP\Framework\SettingApi;

defined( 'ABSPATH' ) || exit;

class DefaultSettingApi extends SettingApi {

	/**
	 * Setting page form action attribute value
	 *
	 * @var string
	 */
	protected $action = 'options.php';

	/**
	 * Class constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'register_setting' ) );
			add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		}
	}

	/**
	 * Register setting and its sanitize callback.
	 */
	public function register_setting() {
		register_setting( $this->get_option_name(), $this->get_option_name(), [ $this, 'sanitize_callback' ] );
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function sanitize_callback( $input ) {
		return $this->sanitize_options( is_array( $input ) ? $input : [] );
	}

	/**
	 * Create admin menu
	 */
	public function add_menu_page() {
		$page_title  = $this->menu_fields['page_title'];
		$menu_title  = $this->menu_fields['menu_title'];
		$menu_slug   = $this->menu_fields['menu_slug'];
		$capability  = isset( $this->menu_fields['capability'] ) ? $this->menu_fields['capability'] : 'manage_options';
		$parent_slug = isset( $this->menu_fields['parent_slug'] ) ? $this->menu_fields['parent_slug'] : null;

		if ( $parent_slug ) {
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug,
				[ $this, 'page_content' ] );
		} else {
			add_menu_page( $page_title, $menu_title, $capability, $menu_slug, [ $this, 'page_content' ] );
		}
	}

	/**
	 * Load page content
	 */
	public function page_content() {
		$formBuilder = new FormBuilder;
		$options     = $this->get_options();
		$option_name = $this->get_option_name();
		ob_start(); ?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->menu_fields['page_title'] ); ?></h1>
			<hr class="wp-header-end">
			<?php if ( ! empty( $this->menu_fields['about_text'] ) ) { ?>
				<div class="about-text"><?php echo esc_html( $this->menu_fields['about_text'] ); ?></div>
			<?php } ?>
			<?php
			if ( $this->has_panels() ) {
				echo $this->option_page_tabs();
			}
			?>
			<form autocomplete="off" method="POST" action="<?php echo esc_attr( $this->action ); ?>">
				<?php
				settings_fields( $option_name );
				echo $formBuilder->get_fields_html( $this->filter_fields_by_tab(), $option_name, $options );
				submit_button();
				?>
			</form>
		</div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Generate Option Page Tabs
	 * @return string
	 */
	private function option_page_tabs() {
		$panels = $this->get_panels();
		if ( count( $panels ) < 1 ) {
			return '';
		}

		$current_tab = isset ( $_GET['tab'] ) ? $_GET['tab'] : $panels[0]['id'];
		$page        = $this->menu_fields['menu_slug'];

		$html = '<h2 class="nav-tab-wrapper wp-clearfix">';
		foreach ( $panels as $tab ) {
			$class    = ( $tab['id'] === $current_tab ) ? ' nav-tab-active' : '';
			$page_url = esc_url( add_query_arg( [
				'page' => $page,
				'tab'  => $tab['id']
			], admin_url( $this->menu_fields['parent_slug'] ) ) );
			$html     .= '<a class="nav-tab' . $class . '" href="' . $page_url . '">' . $tab['title'] . '</a>';
		}
		$html .= '</h2>';

		return $html;
	}

	/**
	 * Filter settings fields by page tab
	 *
	 * @param string $current_tab
	 *
	 * @return array
	 */
	public function filter_fields_by_tab( $current_tab = null ) {
		if ( ! $this->has_panels() ) {
			return $this->get_fields();
		}

		if ( empty( $current_tab ) ) {
			$panels      = $this->get_panels();
			$current_tab = isset ( $_GET['tab'] ) ? $_GET['tab'] : $panels[0]['id'];
		}

		return $this->get_fields_by_panel( $current_tab );
	}

	/**
	 * Add new field
	 *
	 * @param array $field
	 */
	public function add_field( array $field ) {
		if ( empty( $field['title'] ) && ! empty( $field['name'] ) ) {
			$field['title'] = $field['name'];
			unset( $field['name'] );
		}
		if ( empty( $field['description'] ) && ! empty( $field['desc'] ) ) {
			$field['description'] = $field['desc'];
			unset( $field['desc'] );
		}
		if ( empty( $field['default'] ) && ! empty( $field['std'] ) ) {
			$field['default'] = $field['std'];
			unset( $field['std'] );
		}
		$this->set_field( $field );
	}

	/**
	 * Check if has panels
	 *
	 * @return bool
	 */
	public function has_panels() {
		return count( $this->panels ) > 0;
	}

	/**
	 * Check if has sections
	 *
	 * @return bool
	 */
	public function has_sections() {
		return count( $this->sections ) > 0;
	}

	/**
	 * Get sections for current panel
	 *
	 * @param string $panel
	 *
	 * @return array
	 */
	public function get_sections_by_panel( $panel = '' ) {
		if ( empty( $panel ) || ! $this->has_panels() ) {
			return $this->get_sections();
		}

		$panels = [];
		foreach ( $this->get_sections() as $section ) {
			if ( $section['panel'] == $panel ) {
				$panels[] = $section;
			}
		}

		return $panels;
	}

	/**
	 * Get field for current section
	 *
	 * @param string $section
	 * @param string $panel
	 *
	 * @return mixed
	 */
	public function get_fields_by( $section = '', $panel = '' ) {
		if ( ( empty( $section ) || ! $this->has_sections() ) && empty( $panel ) ) {
			return $this->get_fields();
		}

		$fields = [];
		foreach ( $this->get_fields() as $field ) {
			if ( $field['section'] == $section || ( ! empty( $panel ) && $panel == $field['panel'] ) ) {
				$fields[ $field['id'] ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Filter settings fields by page tab
	 *
	 * @param string $panel
	 *
	 * @return array
	 */
	public function get_fields_by_panel( $panel = '' ) {
		$sections = $this->get_sections_by_panel( $panel );

		if ( count( $sections ) < 1 ) {
			return $this->get_fields_by( null, $panel );
		}

		$fields = [];
		foreach ( $sections as $section ) {
			$_section = $this->get_fields_by( $section['id'], $panel );
			$fields   = array_merge( $fields, $_section );
		}

		return $fields;
	}
}
