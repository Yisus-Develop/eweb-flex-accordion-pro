<?php
/**
 * Post Types Module.
 *
 * Registers the original custom post types and Meta Boxes for ACF-like functionality.
 * Optimized for AI-Vault Elite Standards and WPCS compliance.
 *
 * @package EWEB_Flex_Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom post types and taxonomies.
 *
 * @return void
 */
function spfa_register_post_types() {
	// Register Sections.
	register_post_type(
		'menu_section',
		array(
			'labels'       => array(
				'name'          => 'Menu Sections',
				'singular_name' => 'Menu Section',
			),
			'public'       => true,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-category',
			'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
			'show_in_rest' => true,
		)
	);

	// Register Items.
	register_post_type(
		'menu_item',
		array(
			'labels'       => array(
				'name'          => 'Menu Items',
				'singular_name' => 'Menu Item',
			),
			'public'       => true,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-food',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'show_in_rest' => true,
		)
	);

	// Register Categories Taxonomy.
	register_taxonomy(
		'menu_category',
		'menu_item',
		array(
			'labels'            => array(
				'name'          => 'Menu Categories',
				'singular_name' => 'Menu Category',
			),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		)
	);
}

/**
 * Handle activation.
 *
 * @return void
 */
function spfa_plugin_activate() {
	spfa_register_post_types();
	flush_rewrite_rules();
}

/**
 * CATEGORY ADMIN: Rich Descriptions & Groups.
 *
 * @param WP_Term $term The term object.
 * @return void
 */
function spfa_category_fields_cb( $term ) {
	$desc        = get_term_meta( $term->term_id, 'spfa_cat_description', true );
	$rich_groups = get_term_meta( $term->term_id, 'spfa_rich_groups', true ) ?: array();

	// Detect groups from items in this category.
	$items    = get_posts(
		array(
			'post_type'      => 'menu_item',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'menu_category',
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				),
			),
		)
	);
	$detected = array();
	foreach ( $items as $i ) {
		$g = trim( get_post_meta( $i->ID, 'spfa_item_group', true ) );
		if ( $g && strtolower( $g ) !== 'default' ) {
			$detected[] = $g;
		}
	}
	$detected = array_unique( $detected );
	?>
	<tr class="form-field">
		<th scope="row"><label>Tab Intro Description</label></th>
		<td><?php wp_editor( $desc, 'spfa_cat_description', array( 'textarea_name' => 'spfa_cat_description', 'media_buttons' => false, 'textarea_rows' => 4, 'teeny' => true ) ); ?></td>
	</tr>

	<?php if ( ! empty( $detected ) ) : ?>
		<tr class="form-field">
			<th scope="row"><label>Internal Group Descriptions</label></th>
			<td>
				<div style="background: #f9f9f9; padding: 15px; border: 1px solid #ccc; border-radius: 8px;">
					<p><strong>Detected Groups:</strong> Manage formatting for groups here.</p>
					<?php
					foreach ( $detected as $name ) :
						$val = isset( $rich_groups[ $name ] ) ? $rich_groups[ $name ] : '';
						?>
						<div style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
							<label style="font-weight:bold; color:#A98856;"><?php echo esc_html( $name ); ?></label>
							<input type="hidden" name="spfa_group_names[]" value="<?php echo esc_attr( $name ); ?>">
							<textarea name="spfa_group_descs[]" style="width:100%; height:60px; margin-top:5px;" placeholder="Use <b>bold</b> or <br> for lines..."><?php echo esc_textarea( $val ); ?></textarea>
						</div>
					<?php endforeach; ?>
					<p class="description">Tip: You can use <code>&lt;b&gt;Bold Text&lt;/b&gt;</code> and <code>&lt;br&gt;</code> for new lines.</p>
				</div>
			</td>
		</tr>
	<?php endif; ?>
	<?php
}
add_action( 'menu_category_edit_form_fields', 'spfa_category_fields_cb' );

/**
 * Save taxonomy meta data.
 *
 * @param int $term_id The ID of the term.
 * @return void
 */
function spfa_save_taxonomy_meta( $term_id ) {
	if ( isset( $_POST['spfa_cat_description'] ) ) {
		update_term_meta( $term_id, 'spfa_cat_description', wp_kses_post( wp_unslash( $_POST['spfa_cat_description'] ) ) );
	}

	if ( isset( $_POST['spfa_group_names'] ) && isset( $_POST['spfa_group_descs'] ) ) {
		$data        = array();
		$group_names = (array) $_POST['spfa_group_names'];
		$group_descs = (array) $_POST['spfa_group_descs'];

		foreach ( $group_names as $i => $name ) {
			if ( isset( $group_descs[ $i ] ) ) {
				$data[ trim( $name ) ] = wp_kses_post( wp_unslash( $group_descs[ $i ] ) );
			}
		}
		update_term_meta( $term_id, 'spfa_rich_groups', $data );
	}
}
add_action( 'edited_menu_category', 'spfa_save_taxonomy_meta' );
add_action( 'create_menu_category', 'spfa_save_taxonomy_meta' );

/**
 * ITEM ADMIN: Dish Meta Callback.
 *
 * @param WP_Post $post The post object.
 * @return void
 */
function spfa_item_meta_cb( $post ) {
	wp_nonce_field( 'spfa_save_master', 'spfa_master_nonce' );
	$subtitle = get_post_meta( $post->ID, 'spfa_item_subtitle', true );
	$details  = get_post_meta( $post->ID, 'spfa_item_details', true );
	$group    = get_post_meta( $post->ID, 'spfa_item_group', true );
	?>
	<p><label>Dish Subtitle:</label><br><input type="text" name="spfa_item_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" style="width:100%;"></p>
	<p><label>Internal Group Name:</label><br><input type="text" name="spfa_item_group" value="<?php echo esc_attr( $group ); ?>" style="width:100%;"></p>
	<p><label>Ingredients/Details:</label><br><?php wp_editor( $details, 'spfa_item_details', array( 'textarea_name' => 'spfa_item_details', 'media_buttons' => false, 'textarea_rows' => 4, 'teeny' => true ) ); ?></p>
	<?php
}

/**
 * SECTION CONFIG: Meta Box Callback.
 *
 * @param WP_Post $post The post object.
 * @return void
 */
function spfa_sec_config_cb( $post ) {
	wp_nonce_field( 'spfa_save_master', 'spfa_master_nonce' );
	$color        = get_post_meta( $post->ID, 'spfa_color', true );
	$subtitle     = get_post_meta( $post->ID, 'spfa_subtitle', true );
	$nav_subtitle = get_post_meta( $post->ID, 'spfa_nav_subtitle', true );
	$bev_img      = get_post_meta( $post->ID, 'spfa_beverages_img', true );
	$cat_color    = get_post_meta( $post->ID, 'spfa_catering_color', true ) ?: '#A98856';
	$cat_title    = get_post_meta( $post->ID, 'spfa_catering_title', true );
	$cat_sub      = get_post_meta( $post->ID, 'spfa_catering_subtitle', true );
	$cat_btn      = get_post_meta( $post->ID, 'spfa_catering_btn_text', true ) ?: 'Start Your Catering Inquiry';
	$cat_link     = get_post_meta( $post->ID, 'spfa_catering_btn_link', true ) ?: '/contact-us/';
	?>
	<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
		<div>
			<p><label>Main Color:</label><input type="color" name="spfa_color" value="<?php echo esc_attr( $color ?: '#A52A2A' ); ?>"></p>
			<p><label>Nav Subtitle:</label><input type="text" name="spfa_nav_subtitle" value="<?php echo esc_attr( $nav_subtitle ); ?>" style="width:100%;"></p>
			<p><label>Header Subtitle:</label><br><?php wp_editor( $subtitle, 'spfa_subtitle', array( 'textarea_name' => 'spfa_subtitle', 'media_buttons' => false, 'textarea_rows' => 2, 'teeny' => true ) ); ?></p>
			<p><label>Bev Image:</label><input type="text" name="spfa_beverages_img" id="spfa_beverages_img" value="<?php echo esc_attr( $bev_img ); ?>" style="width:70%;"><button type="button" class="button spfa-upload-img" data-target="spfa_beverages_img">Select</button></p>
		</div>
		<div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
			<p><label>Catering Color:</label><input type="color" name="spfa_catering_color" value="<?php echo esc_attr( $cat_color ); ?>"></p>
			<p><label>Catering Title:</label><br><?php wp_editor( $cat_title, 'spfa_catering_title', array( 'textarea_name' => 'spfa_catering_title', 'media_buttons' => false, 'textarea_rows' => 2, 'teeny' => true ) ); ?></p>
			<p><label>Catering Subtitle:</label><br><?php wp_editor( $cat_sub, 'spfa_catering_subtitle', array( 'textarea_name' => 'spfa_catering_subtitle', 'media_buttons' => false, 'textarea_rows' => 2, 'teeny' => true ) ); ?></p>
			<p><label>Btn Text:</label><input type="text" name="spfa_catering_btn_text" value="<?php echo esc_attr( $cat_btn ); ?>" style="width:100%;"></p>
			<p><label>Btn Link:</label><input type="text" name="spfa_catering_btn_link" value="<?php echo esc_attr( $cat_link ); ?>" style="width:100%;"></p>
		</div>
	</div>
	<p><label>Beverages Bar:</label><br><?php wp_editor( get_post_meta( $post->ID, 'spfa_beverages', true ), 'spfa_beverages', array( 'textarea_name' => 'spfa_beverages', 'media_buttons' => false, 'textarea_rows' => 4, 'teeny' => true ) ); ?></p>
	<script>
	jQuery(document).ready(function($){
		$('.spfa-upload-img').click(function(e) {
			e.preventDefault();
			var target = $('#' + $(this).data('target'));
			var frame = wp.media({ title: 'Select Image', button: { text: 'Use Image' }, multiple: false });
			frame.on('select', function() { target.val(frame.state().get('selection').first().toJSON().url); }).open();
		});
	});
	</script>
	<?php
}

/**
 * ASSIGNMENT: Section Parent Callback.
 *
 * @param WP_Post $post The post object.
 * @return void
 */
function spfa_cat_parent_cb( $post ) {
	$parent_id = get_post_meta( $post->ID, 'spfa_parent_section', true );
	$sections  = get_posts( array( 'post_type' => 'menu_section', 'posts_per_page' => -1 ) );
	?>
	<p><select name="spfa_parent_section" style="width:100%;"><option value="">-- Choose Section --</option><?php foreach ( $sections as $s ) : ?><option value="<?php echo $s->ID; ?>" <?php selected( $parent_id, $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option><?php endforeach; ?></select></p>
	<?php
}

/**
 * METABOXES: Add Meta Boxes.
 *
 * @return void
 */
function spfa_add_custom_meta_boxes() {
	add_meta_box( 'spfa_sec_config', 'Section Config', 'spfa_sec_config_cb', 'menu_section', 'normal', 'high' );
	add_meta_box( 'spfa_cat_parent', 'Assignment', 'spfa_cat_parent_cb', 'menu_item', 'side', 'high' );
	add_meta_box( 'spfa_item_meta', 'Dish Meta', 'spfa_item_meta_cb', 'menu_item', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'spfa_add_custom_meta_boxes' );

/**
 * SAVE POST: Save Meta Boxes.
 *
 * @param int $post_id The ID of the post.
 * @return void
 */
function spfa_save_custom_meta( $post_id ) {
	if ( ! isset( $_POST['spfa_master_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spfa_master_nonce'] ) ), 'spfa_save_master' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	$post_type = get_post_type( $post_id );
	if ( 'menu_section' === $post_type ) {
		$fields = array( 'spfa_color', 'spfa_subtitle', 'spfa_nav_subtitle', 'spfa_beverages', 'spfa_beverages_img', 'spfa_catering_color', 'spfa_catering_title', 'spfa_catering_subtitle', 'spfa_catering_btn_text', 'spfa_catering_btn_link' );
		foreach ( $fields as $f ) {
			if ( isset( $_POST[ $f ] ) ) {
				update_post_meta( $post_id, $f, wp_kses_post( wp_unslash( $_POST[ $f ] ) ) );
			}
		}
	}
	if ( 'menu_item' === $post_type ) {
		if ( isset( $_POST['spfa_parent_section'] ) ) {
			update_post_meta( $post_id, 'spfa_parent_section', sanitize_text_field( wp_unslash( $_POST['spfa_parent_section'] ) ) );
		}
		if ( isset( $_POST['spfa_item_subtitle'] ) ) {
			update_post_meta( $post_id, 'spfa_item_subtitle', sanitize_text_field( wp_unslash( $_POST['spfa_item_subtitle'] ) ) );
		}
		if ( isset( $_POST['spfa_item_group'] ) ) {
			update_post_meta( $post_id, 'spfa_item_group', sanitize_text_field( wp_unslash( $_POST['spfa_item_group'] ) ) );
		}
		if ( isset( $_POST['spfa_item_details'] ) ) {
			update_post_meta( $post_id, 'spfa_item_details', wp_kses_post( wp_unslash( $_POST['spfa_item_details'] ) ) );
		}
	}
}
add_action( 'save_post', 'spfa_save_custom_meta' );

/**
 * Register REST meta.
 *
 * @return void
 */
function spfa_register_rest_meta() {
	register_meta( 'term', 'spfa_cat_description', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true ) );
}
add_action( 'init', 'spfa_register_rest_meta' );
