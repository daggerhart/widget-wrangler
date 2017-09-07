<?php

namespace WidgetWrangler;

/**
 * Class AdminPageSidebars
 * @package WidgetWrangler
 */
class AdminPageSidebars extends AdminPage {

	/**
	 * @see AdminPage::title()
	 */
	function title() {
		return __('Theme Sidebars');
	}

	/**
	 * @see AdminPage::menuTitle()
	 */
	function menuTitle() {
		return __('Sidebars');
	}

	/**
	 * @see AdminPage::slug()
	 */
	function slug() {
		return 'sidebars';
	}

	/**
	 * @see AdminPage::description()
	 */
	function description() {
		return array(
            __('Modify the sidebar output registered by the current theme.'),
		);
	}

	/**
	 * @see AdminPage::actions()
	 */
	function actions() {
		return array(
			'update' => array( $this, 'actionUpdate' ),
		);
	}

	/**
	 * @see AdminPage::enqueue()
	 */
	function enqueue() {
		if ( $this->onPage() ){
			wp_enqueue_style('ww-admin');
			wp_enqueue_script('ww-sidebars');
			wp_enqueue_script('ww-box-toggle');
		}
	}

	/**
	 * @see AdminPage::menu()
	 */
	function menu() {
		parent::menu();

		if ( !$this->settings['theme_compat']) {
			remove_submenu_page($this->parent(), $this->slug());
		}
	}

	/**
     * Save the sidebars
     *
	 * @return array
	 */
	function actionUpdate() {
		if ( isset($_POST['ww-data']['sidebars'] ) ) {
			// clean up a little
			$sidebars = array();
			foreach ($_POST['ww-data']['sidebars'] as $slug => $sidebar){
				$sidebars[$slug] = array();
				foreach ($sidebar as $k => $v){
					$sidebars[$slug][$k] = str_replace("\\", '', $v);
				}
			}
			update_option('ww_alter_sidebars', $sidebars);
			return $this->result(__('Sidebars modified.'));
		}

		return $this->error(__('Something went wrong...'));
	}

	/**
	 * @see AdminPage::page()
	 */
	function page() {
		global $wp_registered_sidebars;
		$altered_sidebars = Utils::alteredSidebars(true);

		$form = new Form(array(
			'style' => 'table',
			'field_prefix' => 'ww-data[sidebars]',
		));

		ob_start();
		foreach ($altered_sidebars as $slug => $sidebar)
		{
			$original = (isset($wp_registered_sidebars[$slug])) ? $wp_registered_sidebars[$slug] : FALSE;
			?>
            <div class="ww-box">
                <h3><?php print $sidebar['name']; ?> (<code><?php print $slug; ?></code>)</h3>
                <div>
                    <p class="description"><?php print $sidebar['description']; ?></p>
                    <?php
                    print $form->render_field(array(
                        'type' => 'hidden',
                        'name' => 'slug',
                        'name_prefix' => "[{$slug}]",
                        'value' => $slug,
                    ));

                    print $form->form_open_table();

                    print $form->render_field(array(
                        'type' => 'checkbox',
                        'title' => __('Alter Sidebar'),
                        'name' => 'ww_alter',
                        'name_prefix' => "[{$slug}]",
                        'value' => !empty($sidebar['ww_alter']),
                    ));
                    print $form->render_field(array(
                        'type' => 'text',
                        'title' => __('Before Widget'),
                        'name' => 'before_widget',
                        'name_prefix' => "[{$slug}]",
                        'value' => htmlentities( $sidebar['before_widget'] ),
                        'class' => 'regular-text code',
                    ));
                    print $form->render_field(array(
                        'type' => 'text',
                        'title' => __('Before Title'),
                        'name' => 'before_title',
                        'name_prefix' => "[{$slug}]",
                        'value' => htmlentities( $sidebar['before_title'] ),
                        'class' => 'regular-text code',
                    ));
                    print $form->render_field(array(
                        'type' => 'text',
                        'title' => __('After Title'),
                        'name' => 'after_title',
                        'name_prefix' => "[{$slug}]",
                        'value' => htmlentities( $sidebar['after_title'] ),
                        'class' => 'regular-text code',
                    ));
                    print $form->render_field(array(
                        'type' => 'text',
                        'title' => __('After Widget'),
                        'name' => 'after_widget',
                        'name_prefix' => "[{$slug}]",
                        'value' => htmlentities( $sidebar['after_widget'] ),
                        'class' => 'regular-text code',
                    ));

                    print $form->form_close_table();
                    ?>
                    <hr>
                    <a class="toggle-next-content"><?php _e('View Original', 'widgetwrangler'); ?></a>
                    <div class="togglable-content">
                        <pre class="code"><?php print htmlentities(print_r($original,1)); ?></pre>
                    </div>
                </div>
            </div>
			<?php
		}

		$content = ob_get_clean();

		$page = array(
			'title' => '',
			'description' => '',
			'content' => $content,
			'attributes' => array(
				'action' => 'edit.php?post_type=widget&page=sidebars&ww_action=update&noheader=true',
			),
			'submit_button' => array(
				'attributes' => array(
					'value' => __('Update Sidebars', 'widgetwrangler'),
				),
			),
		);
		print AdminUi::form($page);
	}

}