<?php

namespace WidgetWrangler;

/**
 * Class SortableWidgetsUi
 * @package WidgetWrangler
 */
class SortableWidgetsUi {

	private $all_widgets = array();

	function __construct(){
		add_action( 'widget_wrangler_form_top', array( $this, 'add_new_widget' ) );

		$this->all_widgets = Widgets::all(array('publish', 'draft'));
	}

	/**
	 * Javascript drag and drop for sorting
	 */
	public static function js(){
	    wp_enqueue_style('ww-admin');
		wp_enqueue_script('ww-sortable-widgets');

		$data = array(
            'data' => array(
	            'ajaxURL' => admin_url( 'admin-ajax.php' ),
	            'allWidgets' => Widgets::all(),
            )
        );
		wp_localize_script( 'ww-sortable-widgets', 'WidgetWrangler', array('l10n_print_after' => 'WidgetWrangler = '.json_encode( $data ).';') );
	}

	/**
     * Meta box hook callback.
     *
	 * @param $post
	 */
	public static function postMetaBox( $post ) {
	    self::metaBox();
    }

	/**
	 * Output the sortable wrangler meta box.
	 *
	 * @param null $widgets
	 */
	public static function metaBox( $widgets = null ) {
	    if ( !$widgets ){
		    $widgets = Utils::pageWidgets();
	    }

		$sortable = new self();

		print $sortable->box_wrapper( $widgets );
    }

	/**
	 * Create an admin interface for wrangling widgets
	 *
	 * @param $widgets
	 *
	 * @return string
	 */
	function box_wrapper( $widgets ){
		ob_start();
		// meta_box interior
		?>
		<div id="widget-wrangler-form-meta">
			<?php do_action('widget_wrangler_form_meta'); ?>
			<input value='true' type='hidden' name='widget-wrangler-edit' />
			<?php wp_nonce_field( 'widget-wrangler-sortable-list-box-save' , 'ww-sortable-list-box' ); ?>
		</div>

		<div id="widget-wrangler-form-wrapper">
			<div id='widget-wrangler-form' class='new-admin-panel'>
				<div class='outer'>
					<div id="widget_wrangler_form_top">
						<?php do_action('widget_wrangler_form_top', Utils::pageContext()); ?>
					</div>
                    <div id='ww-edited-message'>
                        <p><em>* <?php _e("Widget changes will not be updated until you save.", 'widgetwrangler'); ?></em></p>
                    </div>
					<?php
						print $this->theme_sortable_corrals( $widgets );
					?>
					<div id="widget_wrangler_form_bottom">
						<?php do_action('widget_wrangler_form_bottom'); ?>
					</div>

				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param $widgets
	 *
	 * @return string
	 */
	function theme_sortable_corrals( $widgets ){
		$output = '';

		foreach ( Corrals::all() as $corral_slug => $corral_name ){
			// ensure an array exists
			if ( ! isset( $widgets[ $corral_slug ] ) ){
				$widgets[ $corral_slug ] = array();
			}

			$corral_widgets = $this->preprocess_sortable_corral( $widgets[ $corral_slug ], $corral_slug );

			$output.= $this->sortable_corral( $corral_widgets, $corral_slug  );
		}

		return $output;
	}

	/**
	 * Prepare the important widget details for sortable display
	 *
	 * @param $corral_widgets
	 * @param string $corral_slug
	 *
	 * @return array
	 */
	function preprocess_sortable_corral( $corral_widgets, $corral_slug = 'disabled' ){
		$sorted_widgets = array();

		foreach ( $corral_widgets as $i => $details ){
			if ( isset( $this->all_widgets[ $details['id'] ] ) ){
				$widget = $this->all_widgets[ $details['id'] ];

				$widget_details = array(
					'weight' => $details['weight'],
					'id' => $widget->ID,
					'title' => $widget->post_title,
					'slug' => $widget->post_name,
					'status' => $widget->post_status,
					'display_logic' => $widget->display_logic_enabled,
					'corral' => array(
						'title' => Corrals::all()[ $corral_slug ],
						'slug' => $corral_slug,
					),
					'notes' => array(),
				);

				// additional text indicators of widget's status and display logic
				// indicate drafts
				if ( $widget_details['status'] == 'draft' ) {
					$widget_details['notes'][] = __('draft', 'widgetwrangler');
				}

				// indicate display logic enabled
				if ( $widget_details['display_logic'] ){
					$widget_details['notes'][] = __('display logic', 'widgetwrangler');
				}

				// fix widgets with no title
				if ( empty( $widget_details['title'] ) ){
					$widget_details['title'] = sprintf( __('(no title) - Slug: %1$s - ID: %2$s', 'widgetwrangler'), $widget->post_name, $widget->ID );
				}

				// fix empty or duplicate weights by moving widget to the bottom
				if ( ! isset( $widget_details['weight'] ) || isset( $sorted_widgets[ $widget_details['weight'] ] ) ) {
					$widget_details['weight'] = $i + count( $corral_widgets );
				}

				$sorted_widgets[ $widget_details['weight'] ] = $widget_details;
			}
		}

		ksort($sorted_widgets);

		return $sorted_widgets;
	}

	/**
	 * Sortable corral items
	 *
	 * @param $corral_widgets
	 * @param $corral_slug
	 *
	 * @return string
	 */
	function sortable_corral( $corral_widgets, $corral_slug ){
		$corral_name = Corrals::all()[ $corral_slug ];
		$no_widgets_style = count( $corral_widgets ) ? 'style="display:none"' : '';

		ob_start();
		?>
		<div id="ww-corral-<?php print $corral_slug; ?>-wrapper">
			<h3><?php print $corral_name; ?></h3>
			<ul name='<?php print $corral_slug; ?>' id='ww-corral-<?php print $corral_slug; ?>-items' class='inner ww-sortable' width='100%'>
				<?php
					foreach ( $corral_widgets as $i => $widget ){
						print $this->sortable_corral_item( $widget, $corral_slug.'-'.$i);
					}
				?>
				<li class='ww-no-widgets' <?php print $no_widgets_style; ?>>
                    <p><?php _e("No Widgets in this corral.", 'widgetwrangler'); ?></p>
                </li>
			</ul>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Single sortable corral item
	 *
	 * @param $widget_details
	 * @param null $row_index
	 *
	 * @return string
	 */
	function sortable_corral_item($widget_details, $row_index = null) {
		if ( empty($row_index) ) { $row_index = $widget_details->ID; }
		$corral_slug = $widget_details['corral']['slug'];
		$weight = $widget_details['weight'];

		$notes = '';
		if ( ! empty( $widget_details['notes'] ) ) {
			$notes = ' : <em>(' . implode( '),(', $widget_details['notes'] ) . ')</em>';
		}

		ob_start();
		?>
		<li class='ww-item <?php print $corral_slug; ?> nojs' width='100%'>
			<input  name='ww-data[widgets][<?php print $row_index; ?>][weight]' type='text' class='ww-widget-weight'  size='2' value='<?php print $weight; ?>' />
			<input  name='ww-data[widgets][<?php print $row_index; ?>][id]' type='hidden' class='ww-widget-id' value='<?php print $widget_details['id']; ?>' />
			<select name='ww-data[widgets][<?php print $row_index; ?>][sidebar]'>
				<option value='disabled'><?php _e('Remove', 'widgetwrangler'); ?></option>
				<?php foreach( Corrals::all() as $this_corral_slug => $corral_name ){ ?>
					<option name='<?php print $this_corral_slug; ?>'
					        value='<?php print $this_corral_slug; ?>'
						    <?php selected( $this_corral_slug, $corral_slug ); ?>>
						<?php print $corral_name; ?>
					</option>
				<?php } ?>
			</select>
			<?php print $widget_details['title'] . $notes ?>
		</li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Implements widget_wrangler_form_bottom hook
	 *
	 * Allow user to add any widget to any corral
	 */
	function add_new_widget(){
		?>
		<div class="">
			<h3><?php _e('Add Widget', 'widgetwrangler'); ?></h3>
			<div class="">
				<select id="ww-add-new-widget-widget">
					<option value="0">- <?php _e('Select a Widget', 'widgetwrangler'); ?> -</option>
					<?php foreach ( $this->all_widgets as $widget ){ ?>
						<?php if ( ! $widget->hide_from_wrangler ) : ?>
						<option value="<?php print esc_attr( $widget->ID ); ?>"><?php print $widget->post_title; ?></option>
						<?php endif; ?>
					<?php } ?>
				</select>
				<select id="ww-add-new-widget-corral">
					<option value="0">- <?php _e('Select a Corral', 'widgetwrangler'); ?> -</option>
					<?php foreach(Corrals::all() as $corral_slug => $corral_name) { ?>
						<option value="<?php print esc_attr( $corral_slug ); ?>"><?php print $corral_name; ?></option>
					<?php } ?>
				</select>
				<span id="ww-add-new-widget-button" class="button button-large"><?php _e('Add Widget to Corral', 'widgetwrangler'); ?></span>
                <p class="description"><?php _e('Select a widget you would like to add, and the corral where you would like to add it. Click the button Add Widget to Corral.', 'widgetwrangler'); ?></p>
            </div>

			<script type="text/html" id="tmpl-add-widget">
				<?php
				$tmpl_widget = array(
					'weight' => '__widget-weight__',
					'id' => '__widget-ID__',
					'title' => '__widget-post_title__',
					'corral' => array(
						'slug' => '__widget-corral_slug__',
					),
				);

				print $this->sortable_corral_item( $tmpl_widget, '__ROW-INDEX__' );
				?>
			</script>
		</div>
		<?php
		//
	}
}
