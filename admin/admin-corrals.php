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
            'insert' => array( $this, 'actionInsert' ),
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
     * Create a new corral.
     *
	 * @return array
	 */
	function actionInsert() {
		if ( !empty( $_POST['ww-new-corral'] ) ) {
			WidgetWranglerCorrals::add( $_POST['ww-new-corral'] );

			return $this->result(sprintf(__('New corral "%s" created.'), $_POST['ww-new-corral']));
		}

		return $this->error();
	}

	/**
	 * Delete an existing corral.
	 *
	 * @return array
	 */
	function actionDelete() {
		if ( !empty( $_POST['ww-delete-slug'] ) ) {
			WidgetWranglerCorrals::remove( $_POST['ww-delete-slug'] );

			return $this->result(sprintf(__('Corral "%s" deleted.'), $_POST['ww-delete-slug']));
		}

		return $this->error();
	}

	/**
	 * Update an existing corral.
	 *
	 * @return array
	 */
	function actionUpdate() {
		if ( isset( $_POST['ww-update-old-slug'], $_POST['ww-update-corral'], $_POST['ww-update-slug'] ) ) {
			WidgetWranglerCorrals::update( $_POST['ww-update-old-slug'],  $_POST['ww-update-corral'], $_POST['ww-update-slug']);

			return $this->result(sprintf(__('New corral "%s" created.'), $_POST['ww-update-corral']));
		}

		return $this->error();
	}

	/**
	 * Reorder the corrals.
	 *
	 * @return array
	 */
	function actionReorder() {
		if ( isset( $_POST['weight'] ) && is_array( $_POST['weight'] ) ) {
			WidgetWranglerCorrals::reorder($_POST['weight']);

			return $this->result(__('Corrals have been reordered.'));
		}

		return $this->error();
	}

	/**
	 * @return string
	 */
	function corralCreateForm() {
	    ob_start();
	    ?>
        <form action='edit.php?post_type=widget&page=corrals&ww_action=insert&noheader=true' method='post'>
            <input class=' ww-pull-right button button-primary button-large' type='submit' value='<?php _e("Create Corral", 'widgetwrangler'); ?>' />
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="ww-new-corral"><?php _e("Corral Name", 'widgetwrangler'); ?></label></th>
                    <td><input class="regular-text" name='ww-new-corral' type='text' value=''></td>
                </tr>
            </table>
        </form>
        <?php
        return ob_get_clean();
    }

	/**
	 * Sortable corrals form.
     *
	 * @param $corrals
	 *
	 * @return string
	 */
	function corralSortableForm($corrals) {
	    ob_start();
		?>
        <form action='edit.php?post_type=widget&page=corrals&ww_action=sort&noheader=true' method='post'>
            <input class='ww-pull-right button button-primary button-large' type='submit' name='ww-sidebars-save' value='<?php _e("Save Order", 'widgetwrangler'); ?>' />
            <ul id='ww-corrals-sort-list'>
                <?php
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
                ?>
            </ul>
        </form>
		<?php
        return ob_get_clean();
    }

	/**
     * Corral edit form.
     *
	 * @param $name
	 * @param $slug
     *
     * @return string
	 */
    function corralEditForm($name, $slug) {
        ob_start();
	    $form = new WidgetWranglerForm(array(
		    'action' => 'edit.php?post_type=widget&page=corrals&ww_action=update&noheader=true',
	    ));

	    print $form->open();
	    print $form->render_field(array(
		    'type' => 'submit',
		    'name' => 'ww-update-submit',
		    'value' => __('Update'),
		    'class' => array('ww-pull-right button button-primary button-large')
	    ));
	    print $form->render_field(array(
		    'type' => 'hidden',
		    'name' => 'ww-update-old-slug',
		    'value' => $slug,
		    'class' => array('ww-pull-right button button-primary button-large')
	    ));

	    $form->form_args['form_style'] = 'table';
	    print $form->form_open_table();

	    print $form->render_field(array(
		    'type' => 'text',
		    'title' => __('Name'),
		    'name' => 'ww-update-corral',
		    'value' => $name,
		    'class' => array('regular-text')
	    ));
	    print $form->render_field(array(
		    'type' => 'text',
		    'title' => __('Slug'),
		    'name' => 'ww-update-slug',
		    'value' => $slug,
		    'class' => array('regular-text disabled')
	    ));
	    print $form->close();

	    // Delete form
	    $form = new WidgetWranglerForm(array(
		    'action' => 'edit.php?post_type=widget&page=corrals&ww_action=delete&noheader=true'
	    ));
	    print $form->open();
	    print $form->render_field(array(
		    'type' => 'hidden',
		    'name' => 'ww-delete-slug',
		    'value' => $slug,
	    ));
	    print $form->render_field(array(
		    'type' => 'submit',
		    'name' => 'ww-delete-submit',
		    'value' => __('Delete'),
		    'class' => array('disabled button button-small'),
	    ));
	    print $form->close();

	    return ob_get_clean();
    }

	/**
	 * Build the various forms
	 *
	 * @see \WidgetWranglerAdminPage::page()
	 */
	function page()
	{
		$corrals = WidgetWranglerCorrals::all();
		?>
        <div class="ww-columns">
            <div class="ww-column">
                <div class="ww-box">
                    <h3><?php _e("Create New Corral", 'widgetwrangler'); ?></h3>
                    <div>
                        <?php print $this->corralCreateForm(); ?>
                    </div>
                </div>

                <?php if ( !empty( $corrals ) ) : ?>
                <div class="ww-box">
                    <h3><?php _e("Sort Corrals", 'widgetwrangler'); ?></h3>
                    <div>
                        <?php print $this->corralSortableForm($corrals); ?>
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
                                        print $this->corralEditForm($name, $slug);
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