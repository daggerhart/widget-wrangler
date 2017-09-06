<?php
// hook this addon in
add_filter( 'Widget_Wrangler_Admin_Addons', 'ww_corrals_admin_addon', 10, 2   );

//
function ww_corrals_admin_addon($addons, $settings){
  $addons['Corrals'] = WW_Corrals_Admin::register($settings);
  return $addons;
}

/**
 * Class WW_Corrals_Admin
 */
class WW_Corrals_Admin extends WidgetWranglerAdminPage {

	/**
	 * @see WidgetWranglerAdminPage::title()
	 */
    function title() {
        return __('Widget Corrals');
    }

	/**
	 * @see WidgetWranglerAdminPage::menuTitle()
	 */
    function menuTitle() {
	    return __('Corrals');
    }

	/**
	 * @see WidgetWranglerAdminPage::slug()
	 */
    function slug() {
        return 'corrals';
    }

	/**
	 * @see WidgetWranglerAdminPage::description()
	 */
	function description() {
		return array(
            __("A Corral is an arbitrary group of widgets. WordPress and previous Widget Wrangler versions call them 'sidebars', but they are ultimately not limited by that terminology.", 'widgetwrangler'),
            __("In general, you probably want at least one corral per theme sidebar.", 'widgetwrangler'),
		);
	}

	/**
	 * @see WidgetWranglerAdminPage::actions()
	 */
	function actions() {
        return array(
            'create' => array( $this, 'actionCreate' ),
            'delete' => array( $this, 'actionDelete' ),
            'update' => array( $this, 'actionUpdate' ),
            'sort' => array( $this, 'actionReorder' ),
        );
    }

	/**
	 * @see WidgetWranglerAdminPage::enqueue()
	 */
	function enqueue() {
		if ( $this->onPage() ){
		    wp_enqueue_style('ww-admin');
			wp_enqueue_script('ww-corrals');
			wp_enqueue_script('ww-box-toggle');
		}
	}

	/**
     * Create corral form.
     *
	 * @return string
	 */
	function formCreate() {
	    $form = new WidgetWranglerForm(array(
            'form_style' => 'table',
            'action' => 'edit.php?post_type=widget&page=corrals&ww_action=create&noheader=true',
            'fields' => array(
                'submit' => array(
                    'type' => 'submit',
                    'value' => __('Create Corral'),
                    'class' => array('ww-pull-right button button-primary button-large'),
                ),
                'ww-new-corral' => array(
                    'type' => 'text',
                    'title' => __('Corral Name'),
                    'class' => array('regular-text'),
                )
            )
        ));

	    return $form->render();
    }

	/**
	 * Create a new corral.
	 *
	 * @return array
	 */
	function actionCreate() {
		if ( empty( $_POST['ww-new-corral'] ) ) {
			return $this->error( __('Error: No corral name given.') );
        }

        WidgetWranglerCorrals::add( $_POST['ww-new-corral'] );

        return $this->result(sprintf(__('New corral "%s" created.'), $_POST['ww-new-corral']));
	}

	/**
	 * Sortable corrals form.
     *
	 * @param $corrals
	 *
	 * @return string
	 */
	function formReorder($corrals) {
	    ob_start();
	        ?><ul id='ww-corrals-sort-list'><?php
            $weight = 1;
            foreach( $corrals as $slug => $name) {
                ?>
                <li class='ww-box ww-item'>
                    <h4><?php print "$name ($slug)"; ?></h4>
                    <input type='hidden' name='weight[<?php print $weight; ?>]' value='<?php print $slug?>' />
                </li>
                <?php
                $weight += 1;
            }
            ?></ul><?php
	    $items = ob_get_clean();

        $form = new WidgetWranglerForm(array(
            'action' => 'edit.php?post_type=widget&page=corrals&ww_action=sort&noheader=true',
            'fields' => array(
                'submit' => array(
                    'type' => 'submit',
                    'value' => __('Save order'),
                    'class' => array('ww-pull-right button button-primary button-large'),
                ),
                'items' => array(
                    'type' => 'markup',
                    'value' => $items,
                )
            )
        ));

        return $form->render();
    }

	/**
	 * Reorder the corrals.
	 *
	 * @return array
	 */
	function actionReorder() {
	    if ( empty($_POST['weight']) || !is_array($_POST['weight']) ) {
		    return $this->error();
        }

        WidgetWranglerCorrals::reorder($_POST['weight']);

        return $this->result(__('Corrals have been reordered.'));
	}

	/**
     * Corral edit form.
     *
	 * @param $name
	 * @param $slug
     *
     * @return string
	 */
    function formUpdate($name, $slug) {
	    $form = new WidgetWranglerForm(array(
            'form_style' => 'table',
		    'action' => 'edit.php?post_type=widget&page=corrals&ww_action=update&noheader=true',
            'fields' => array(
                'ww-update-submit' => array(
	                'type' => 'submit',
	                'value' => __('Update'),
	                'class' => array('ww-pull-right button button-primary button-large')
                ),
                'ww-update-old-slug' => array(
	                'type' => 'hidden',
	                'value' => $slug,
	                'class' => array('ww-pull-right button button-primary button-large')
                ),
                'ww-update-corral' => array(
	                'type' => 'text',
	                'title' => __('Name'),
	                'value' => $name,
	                'class' => array('regular-text')
                ),
                'ww-update-slug' => array(
	                'type' => 'text',
	                'title' => __('Slug'),
	                'value' => $slug,
	                'class' => array('regular-text disabled')
                )
            ),
	    ));

	    return $form->render();
    }

	/**
	 * Update an existing corral.
	 *
	 * @return array
	 */
	function actionUpdate() {
	    if ( empty( $_POST['ww-update-old-slug'] ) ) {
		    return $this->error( __('Previous corral name not found.') );
        }

	    if ( empty( $_POST['ww-update-corral'] ) ) {
		    return $this->error( __('No new corral name found.') );
        }

	    if ( empty( $_POST['ww-update-slug'] ) ) {
		    return $this->error( __('No corral slug found.') );
        }

        WidgetWranglerCorrals::update( $_POST['ww-update-old-slug'],  $_POST['ww-update-corral'], $_POST['ww-update-slug']);

        return $this->result(sprintf(__('New corral "%s" created.'), $_POST['ww-update-corral']));
	}

	/**
	 * @param $slug
	 *
	 * @return string
	 */
    function formDelete($slug) {
	    $form = new WidgetWranglerForm(array(
		    'action' => 'edit.php?post_type=widget&page=corrals&ww_action=delete&noheader=true',
            'fields' => array(
                'ww-delete-slug' => array(
	                'type' => 'hidden',
	                'value' => $slug,
                ),
                'ww-delete-submit' => array(
	                'type' => 'submit',
	                'value' => __('Delete'),
	                'class' => array('disabled button button-small'),
                )
            ),
	    ));

	    return $form->render();
    }

	/**
	 * Delete an existing corral.
	 *
	 * @return array
	 */
	function actionDelete() {
		if ( empty( $_POST['ww-delete-slug'] ) ) {
			return $this->error( __('Corral data missing.') );
		}

        WidgetWranglerCorrals::remove( $_POST['ww-delete-slug'] );

        return $this->result(sprintf(__('Corral "%s" deleted.'), $_POST['ww-delete-slug']));
	}

	/**
	 * Build the various forms
	 *
	 * @see \WidgetWranglerAdminPage::page()
	 */
	function page() {
		$corrals = WidgetWranglerCorrals::all();
		?>
        <div class="ww-columns">
            <div class="ww-column">
                <div class="ww-box">
                    <h3><?php _e("Create New Corral", 'widgetwrangler'); ?></h3>
                    <div>
                        <?php print $this->formCreate(); ?>
                    </div>
                </div>

                <?php if ( !empty( $corrals ) ) : ?>
                <div class="ww-box">
                    <h3><?php _e("Sort Corrals", 'widgetwrangler'); ?></h3>
                    <div>
                        <?php print $this->formReorder($corrals); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="ww-column">
                <?php if ( !empty( $corrals ) ) : ?>
                <div class="ww-box">
                    <h3><?php _e("Edit existing Corrals", 'widgetwrangler'); ?></h3>
                    <div class='description ww-danger'>
                        <?php _e("Warning! If you change a corral's slug, widgets currently assigned to that corral will need to be reassigned.", 'widgetwrangler'); ?>
                    </div>
                    <ul id='ww-corrals-list'>
                        <?php
                            foreach ($corrals as $slug => $name) {
                                ?>
                                <li class='ww-box ww-box-toggle'>
                                <h3><?php print $name; ?> (<?php print $slug; ?>)</h3>
                                <div class="ww-box-toggle-content">
                                    <?php
                                        print $this->formUpdate($name, $slug);
                                        print $this->formDelete($slug);
                                    ?>
                                </div>
                                </li>
                                <?php
                            }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
		<?php
	}

}
