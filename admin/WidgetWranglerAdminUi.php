<?php

class WidgetWranglerAdminUi {

	/**
	 * Add css to admin interface.
	 */
	public static function css(){
		print '<link rel="stylesheet" type="text/css" href="'.WW_PLUGIN_URL.'/admin/css/admin.css" />';
	}

	/**
	 * Reusable admin page template
	 *
	 * @param array $page
	 *
	 * @return string
	 */
	public static function page($page = array()) {

		$default_page = array(
			'title' => 'Page Title',
			'description' => '',
			'content' => '',
		);
		$page = array_replace($default_page, $page);
		ob_start();
		?>
		<div class="wrap">
            <h2><?php printf( __('%s', 'widgetwrangler'), $page['title']); ?></h2>
            <?php
                print self::messages();
                print self::description($page['description']);
	        ?>
			<div>
				<?php print $page['content']; ?>
			</div>
			<div class="ww-clear-gone">&nbsp;</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Reusable admin form output
	 *
	 * @param array $form
	 *
	 * @return string
	 */
	public static function form($form = array()) {
		$default_form = array(
			'title' => 'Form Title',
			'description' => 'Form Description',
			'content' => '',
			'submit_button' => array(
				'attributes' => array(
					'value' => 'Save Settings',
					'class' => 'button button-primary button-large',
				),
				'location' => 'top',
			),
			'attributes' => array(
				'class' => 'ww-form',
				'action' => '',
				'method' => 'post'
			),
		);

		$form = array_replace_recursive($default_form, $form);
		$form_attributes = '';
		$submit_button_attributes = '';

		// make form attributes attributes
		foreach ($form['attributes'] as $name => $value){
			$form_attributes.= " {$name}='{$value}'";
		}

		// make submit button element attributes
		foreach ($form['submit_button']['attributes'] as $name => $value){
			$submit_button_attributes.= " {$name}='{$value}'";
		}

		$form['attributes']['output'] = $form_attributes;
		$form['submit_button']['attributes']['output'] = $submit_button_attributes;

		ob_start();
		?>
		<a id="widget-wrangler"></a><br />
		<div class="wrap">
			<form <?php print $form['attributes']['output']; ?>>
				<div class="ww-admin-top">

					<?php if ($form['submit_button']['location'] == "top") { ?>
						<p class="ww-top-right-save">
							<input type="submit" <?php print $form['submit_button']['attributes']['output']; ?> />
						</p>
					<?php } ?>

					<h2 class="ww-admin-title"><?php print $form['title']; ?></h2>
					<div class="ww-clear-gone">&nbsp;</div>
					<?php
                        print self::messages();
                        print self::description($form['description']);
					?>
				</div>
				<div>
					<?php print $form['content']; ?>
				</div>
				<div class="ww-clear-gone">&nbsp;</div>

				<?php if ($form['submit_button']['location'] == "bottom") { ?>
					<p class="ww-top-right-save">
						<input type="submit" <?php print $form['submit_button']['attributes']['output']; ?> />
					</p>
				<?php } ?>

			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
     * Template messages.
     *
	 * @return string
	 */
	public static function messages() {
		$messages = WidgetWranglerAdminMessages::instance()->get();

	    ob_start();
		if ( !empty( $messages ) ) {
			foreach ( $messages as $message ) {
				?>
                <p class="message <?php print $message['type']; ?>">
					<?php print $message['message']; ?>
                </p>
				<?php
			}
		}

        return ob_get_clean();
    }

	/**
     * Template descriptions.
     *
	 * @param $descriptions array
	 *
	 * @return string
	 */
	public static function description( $descriptions ) {
	    ob_start();
		if ( !empty( $descriptions ) ) {
		    if ( !is_array($descriptions) ){
		        $descriptions = array($descriptions);
            }
            
			foreach ( $descriptions as $description ) {
				?>
                <p class="description">
					<?php print $description; ?>
                </p>
				<?php
			}
		}

        return ob_get_clean();
    }
}