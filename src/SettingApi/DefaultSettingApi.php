<?php

namespace Stackonet\WP\Framework\SettingApi;

use Stackonet\WP\Framework\Interfaces\FormBuilderInterface;

defined( 'ABSPATH' ) || exit;

class DefaultSettingApi extends SettingApi {

	/**
	 * Setting page form action attribute value
	 *
	 * @var string
	 */
	protected $action = 'options.php';

	/**
	 * @var FormBuilder
	 */
	protected $form_builder;

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
	 * @param array|mixed $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public function sanitize_callback( $input ): array {
		return $this->sanitize_options( is_array( $input ) ? $input : [] );
	}

	/**
	 * Create admin menu
	 */
	public function add_menu_page() {
		$page_title  = $this->menu_fields['page_title'];
		$menu_title  = $this->menu_fields['menu_title'];
		$menu_slug   = $this->menu_fields['menu_slug'];
		$capability  = $this->menu_fields['capability'] ?? 'manage_options';
		$parent_slug = $this->menu_fields['parent_slug'] ?? null;

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
		$options     = $this->get_options();
		$option_name = $this->get_option_name();

		$has_sections = false;
		$panel        = '';
		$sections     = [];
		if ( $this->has_panels() ) {
			$panels_ids   = wp_list_pluck( $this->get_panels(), 'id' );
			$panel        = isset ( $_GET['tab'] ) && in_array( $_GET['tab'], $panels_ids ) ? $_GET['tab'] : $panels_ids[0];
			$sections     = $this->get_sections_by_panel( $panel );
			$has_sections = count( $sections ) > 0;
		}
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
				if ( $has_sections ) {
					echo $this->get_fields_html_by_section( $sections, $panel );
				} else {
					echo $this->get_form_builder()->get_fields_html( $this->filter_fields_by_tab(), $option_name, $options );
				}
				submit_button();
				?>
			</form>
		</div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Get fields HTML by section
	 *
	 * @param array $sections Array of section
	 * @param string|null $panel Panel id
	 *
	 * @return string
	 */
	public function get_fields_html_by_section( array $sections = [], ?string $panel = null ): string {
		$options     = $this->get_options();
		$option_name = $this->get_option_name();

		$table = '';
		foreach ( $sections as $section ) {
			if ( ! empty( $section['title'] ) ) {
				$table .= '<h2 class="title">' . esc_html( $section['title'] ) . '</h2>';
			}
			if ( ! empty( $section['description'] ) ) {
				$table .= '<p class="description">' . esc_js( $section['description'] ) . '</p>';
			}

			$fieldsArray = $this->get_fields_by( $section['id'], $panel );
			$table       .= $this->get_form_builder()->get_fields_html( $fieldsArray, $option_name, $options );
		}

		return $table;
	}

	/**
	 * Generate Option Page Tabs
	 * @return string
	 */
	private function option_page_tabs(): string {
		$panels = $this->get_panels();
		if ( count( $panels ) < 1 ) {
			return '';
		}

		$current_tab = $_GET['tab'] ?? $panels[0]['id'];
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
	 * @param string|null $current_tab
	 *
	 * @return array
	 */
	public function filter_fields_by_tab( ?string $current_tab = null ): array {
		if ( ! $this->has_panels() ) {
			return $this->get_fields();
		}

		if ( empty( $current_tab ) ) {
			$panels      = $this->get_panels();
			$current_tab = $_GET['tab'] ?? $panels[0]['id'];
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
	 * Check if it has panels
	 *
	 * @return bool
	 */
	public function has_panels(): bool {
		return count( $this->panels ) > 0;
	}

	/**
	 * Check if it has sections
	 *
	 * @return bool
	 */
	public function has_sections(): bool {
		return count( $this->sections ) > 0;
	}

	/**
	 * Get sections for current panel
	 *
	 * @param string $panel
	 *
	 * @return array
	 */
	public function get_sections_by_panel( string $panel = '' ): array {
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
	 * @param string|null $section
	 * @param string|null $panel
	 *
	 * @return array
	 */
	public function get_fields_by( ?string $section = '', ?string $panel = '' ): array {
		if ( ( empty( $section ) || ! $this->has_sections() ) && empty( $panel ) ) {
			return $this->get_fields();
		}

		$fields = [];
		foreach ( $this->get_fields() as $field ) {
			if (
				( isset( $field['section'] ) && $field['section'] == $section ) ||
				( ! empty( $panel ) && isset( $field['panel'] ) && $panel == $field['panel'] )
			) {
				$fields[ $field['id'] ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Filter settings fields by page tab
	 *
	 * @param string|null $panel
	 *
	 * @return array
	 */
	public function get_fields_by_panel( ?string $panel = '' ): array {
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

	/**
	 * @return FormBuilderInterface
	 */
	public function get_form_builder(): FormBuilderInterface {
		if ( ! $this->form_builder instanceof FormBuilderInterface ) {
			$this->set_form_builder( new FormBuilder );
		}

		return $this->form_builder;
	}

	/**
	 * @param FormBuilderInterface $form_builder
	 */
	public function set_form_builder( FormBuilderInterface $form_builder ): void {
		$this->form_builder = $form_builder;
	}
}
