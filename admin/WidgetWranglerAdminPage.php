<?php

class WidgetWranglerAdminPage {

	/**
	 * Widget Wrangler settings values array.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Parent relative url for this page.
	 *
	 * @return string
	 */
	function parent() {
		return Widget_Wrangler_Admin::$page_slug;
	}

	/**
	 * Capability required to access this page.
	 *
	 * @return string
	 */
	function capability() {
		return Widget_Wrangler_Admin::$capability;
	}

	/**
	 * This page's title.
	 *
	 * @return string
	 */
	function title() {
		return __('Page Title');
	}

	/**
	 * This page's menu title.
	 *
	 * @return string
	 */
	function menuTitle() {
		return $this->title();
	}

	/**
	 * This page's description.
	 *
	 * @return string
	 */
	function description() {
		return '';
	}

	/**
	 * This page's unique slug.
	 *
	 * @return string
	 */
	function slug() {
		return '';
	}

	/**
	 * Array of action_name => callable pairs
	 *
	 * @return array
	 */
	function actions() {
		return array();
	}

	/**
	 * Whether or not to output this page with a form template.
	 *
	 * @return bool
	 */
	function isForm() {
		return false;
	}

	/**
	 * WidgetWranglerAdminPage constructor.
	 *
	 * @param $settings
	 */
	function __construct($settings) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @param $settings
	 *
	 * @return \WidgetWranglerAdminPage
	 */
	public static function register($settings) {
		$class = get_called_class();
		$plugin = new $class($settings);

		add_action( 'admin_menu', array( $plugin, 'menu' ) );

		return $plugin;
	}

	/**
	 * WordPress hook admin_menu.
	 */
	function menu() {
		if ($this->parent() && $this->slug()) {
			add_submenu_page( $this->parent(), $this->title(), $this->menuTitle(), $this->capability(), $this->slug(), array( $this, 'route' ));
		}
	}

	/**
	 * Route the page navigation
	 */
	function route() {
		$action = !empty($_GET['ww_action']) ? $_GET['ww_action'] : null;
		$actions = $this->actions();

		if ( $action && !empty( $actions[ $action ] ) && is_callable( $actions[ $action ] ) ) {
			$result = call_user_func( $actions[ $action ] );
			$message = null;
			$redirect = $_SERVER['HTTP_REFERER'];

			if ( is_array($result) && !empty( $result['redirect'] ) ) {
				$redirect = $result['redirect'];
			}

			if ( is_array($result) && !empty( $result['message'] ) ) {
				WidgetWranglerAdminMessages::instance()->add( $result['message'], $result['type'] );
			}

			wp_safe_redirect($redirect);
			exit;
		}

		if ( $this->isForm() ){
			$this->showForm();
		}
		else {
			$this->showPage();
		}
	}

	/**
	 * Output the templated page.
	 */
	function showPage() {
		ob_start();
			$this->page();
		$content = ob_get_clean();

		print WidgetWranglerAdminUi::page(array(
			'title' => $this->title(),
			'description' => $this->description(),
			'content' => $content,
		));
	}

	/**
	 * Output the templated form.
	 */
	function showForm() {
		ob_start();
			$this->form();
		$content = ob_get_clean();

		print WidgetWranglerAdminUi::form(array(
			'title' => $this->title(),
			'description' => $this->description(),
			'content' => $content,
		));
	}

	/**
	 * Override in child to produce page output.
	 */
	function page() {}

	/**
	 * Override in child to produce form output.
	 */
	function form() {}

	/**
	 * Standard action result array
	 *
	 * @param string $message
	 * @param null $redirect
	 * @param string $type
	 *
	 * @return array
	 */
	function result( $message, $redirect = null, $type = 'success') {
		$result = array(
			'message' => $message,
			'type' => $type,
		);

		if ( $redirect ) {
			$result['redirect'] = $redirect;
		}

		return $result;
	}

	/**
	 * An error result.
	 *
	 * @param $message
	 * @param null $redirect
	 *
	 * @return array
	 */
	function error( $message = null, $redirect = null ) {
		if (!$message) {
			$message = __('Something went wrong, please refresh the page and try again.');
		}
		return $this->result($message, $redirect, 'error');
	}
}