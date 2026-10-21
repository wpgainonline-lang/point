<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Shared helpers for pages whose words and photos are edited under Meat Shop. */

function pcs_pc_all( $option, $defaults ) {
	static $cache = array();
	if ( ! isset( $cache[ $option ] ) ) {
		$cache[ $option ] = wp_parse_args( array_filter( (array) get_option( $option, array() ), function ( $v ) { return '' !== $v; } ), $defaults );
	}
	return $cache[ $option ];
}

function pcs_pc_get( $option, $defaults, $key ) {
	$all = pcs_pc_all( $option, $defaults );
	return $all[ $key ] ?? '';
}

/** "Title|Text" lines as arrays. */
function pcs_pc_lines( $option, $defaults, $key ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) pcs_pc_get( $option, $defaults, $key ) ) as $l ) {
		$l = trim( $l );
		if ( '' !== $l ) {
			$out[] = array_map( 'trim', explode( '|', $l, 2 ) );
		}
	}
	return $out;
}

function pcs_pc_admin( $option, $fields, $defaults, $title, $intro, $page_slug ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_enqueue_media();
	$nonce = 'pcs_pc_' . $option;
	if ( isset( $_POST['pcs_pc_nonce'] ) && wp_verify_nonce( $_POST['pcs_pc_nonce'], $nonce ) ) {
		$new = array();
		foreach ( $fields as $group ) {
			foreach ( $group as $k => $f ) {
				$v         = isset( $_POST[ $k ] ) ? wp_unslash( $_POST[ $k ] ) : '';
				$new[ $k ] = 'textarea' === $f[1] ? sanitize_textarea_field( $v ) : ( 'image' === $f[1] ? esc_url_raw( $v ) : sanitize_text_field( $v ) );
			}
		}
		update_option( $option, $new );
		do_action( 'litespeed_purge_all' );
		$pg = get_page_by_path( $page_slug );
		echo '<div class="notice notice-success"><p>Saved.' . ( $pg ? ' <a href="' . esc_url( get_permalink( $pg ) ) . '" target="_blank" rel="noopener">View the page</a>' : '' ) . '</p></div>';
		echo '<script>setTimeout(function(){location.href=location.href},600)</script>';
	}
	$cur = wp_parse_args( array_filter( (array) get_option( $option, array() ), function ( $v ) { return '' !== $v; } ), $defaults );
	echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $intro ) . ' Clear a box and save to bring back the original text.</p><form method="post">';
	wp_nonce_field( $nonce, 'pcs_pc_nonce' );
	foreach ( $fields as $group => $gf ) {
		echo '<h2>' . esc_html( $group ) . '</h2><table class="form-table">';
		foreach ( $gf as $k => $f ) {
			$v = $cur[ $k ] ?? '';
			echo '<tr><th><label for="' . esc_attr( $k ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
			if ( 'textarea' === $f[1] ) {
				$rows = substr_count( (string) $v, "\n" ) >= 3 ? 8 : 4;
				echo '<textarea class="large-text" rows="' . (int) $rows . '" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '">' . esc_textarea( $v ) . '</textarea>';
			} elseif ( 'image' === $f[1] ) {
				echo '<input class="regular-text pcs-img" type="text" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '"> <button type="button" class="button pcs-pick" data-target="' . esc_attr( $k ) . '">Choose image</button>';
				echo '<div style="margin-top:8px">' . ( $v ? '<img src="' . esc_url( $v ) . '" style="max-width:240px;height:auto">' : '' ) . '</div>';
			} else {
				echo '<input class="large-text" type="text" name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
			}
			if ( ! empty( $f[2] ) ) {
				echo '<p class="description">' . esc_html( $f[2] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
	echo '<p><button class="button button-primary">Save</button></p></form></div>';
	?>
	<script>
	jQuery(function($){
		$('.pcs-pick').on('click',function(e){
			e.preventDefault();var t=$('#'+$(this).data('target'));
			var f=wp.media({title:'Choose image',multiple:false,library:{type:'image'}});
			f.on('select',function(){t.val(f.state().get('selection').first().toJSON().url);});
			f.open();
		});
	});
	</script>
	<?php
}
