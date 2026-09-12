<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * البنرات — Custom Post Type منفصل في الداشبورد.
 * كل بنر: عنوان (عربي = عنوان البوست) + عنوان إنجليزي + وصف عربي/إنجليزي
 * + صورة (Featured Image) + actionType + actionTarget.
 * بيترجع في الـ API من كومبوننت "banners" في الهوم الجديدة (bookapp/v2/home).
 */
class BookApp_API_Banners {

	const CPT = 'bookapp_banner';

	public static function action_types() {
		return array( '', 'category', 'tag', 'product', 'author', 'publisher', 'url' );
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
	}

	public static function register_cpt() {
		register_post_type(
			self::CPT,
			array(
				'labels'              => array(
					'name'                  => 'بنرات التطبيق',
					'singular_name'         => 'بنر',
					'add_new'               => 'إضافة بنر',
					'add_new_item'          => 'إضافة بنر جديد',
					'edit_item'             => 'تعديل بنر',
					'new_item'              => 'بنر جديد',
					'view_item'             => 'عرض البنر',
					'search_items'          => 'بحث في البنرات',
					'not_found'             => 'مفيش بنرات لسه — أضف أول بنر.',
					'not_found_in_trash'    => 'مفيش بنرات في السلة.',
					'all_items'             => 'البنرات',
					'menu_name'             => 'البنرات',
					'featured_image'        => 'صورة البنر',
					'set_featured_image'    => 'اختيار صورة البنر',
					'remove_featured_image' => 'إزالة صورة البنر',
					'use_featured_image'    => 'استخدام كصورة للبنر',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'bookapp-app',
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'supports'            => array( 'title', 'thumbnail' ),
			)
		);
	}

	public static function meta_boxes() {
		add_meta_box( 'bookapp_banner_fields', 'بيانات البنر', array( __CLASS__, 'render_meta_box' ), self::CPT, 'normal', 'high' );
		add_meta_box( 'bookapp_banner_help', 'إزاي البنر يظهر في التطبيق؟', array( __CLASS__, 'render_help_box' ), self::CPT, 'side', 'low' );
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'bookapp_banner_save', 'bookapp_banner_nonce' );
		$title_en    = get_post_meta( $post->ID, '_bookapp_title_en', true );
		$subtitle_ar = get_post_meta( $post->ID, '_bookapp_subtitle_ar', true );
		$subtitle_en = get_post_meta( $post->ID, '_bookapp_subtitle_en', true );
		$action_type = get_post_meta( $post->ID, '_bookapp_action_type', true );
		$action_id   = get_post_meta( $post->ID, '_bookapp_action_target', true );
		$labels      = array(
			''          => 'بدون أكشن',
			'category'  => 'تصنيف (category)',
			'tag'       => 'وسم (tag)',
			'product'   => 'منتج (product)',
			'author'    => 'مؤلف (author)',
			'publisher' => 'دار نشر (publisher)',
			'url'       => 'رابط (url)',
		);
		?>
		<table class="form-table">
			<tr>
				<th><label for="bookapp_title_en">العنوان بالإنجليزي</label></th>
				<td>
					<input type="text" class="large-text" id="bookapp_title_en" name="bookapp_banner[title_en]" value="<?php echo esc_attr( $title_en ); ?>">
					<p class="description">عنوان البوست فوق = العنوان العربي. لو ده فاضي هيرجع العربي للإنجليزي كمان.</p>
				</td>
			</tr>
			<tr>
				<th><label for="bookapp_subtitle_ar">الوصف عربي (subtitle)</label></th>
				<td><textarea class="large-text" rows="2" id="bookapp_subtitle_ar" name="bookapp_banner[subtitle_ar]"><?php echo esc_textarea( $subtitle_ar ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="bookapp_subtitle_en">الوصف إنجليزي</label></th>
				<td><textarea class="large-text" rows="2" id="bookapp_subtitle_en" name="bookapp_banner[subtitle_en]"><?php echo esc_textarea( $subtitle_en ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="bookapp_action_type">actionType — لما المستخدم يدوس على البنر</label></th>
				<td>
					<select id="bookapp_action_type" name="bookapp_banner[action_type]">
						<?php foreach ( self::action_types() as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $action_type, $type ); ?>><?php echo esc_html( $labels[ $type ] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="bookapp_action_target">actionTarget — الـ ID أو الرابط</label></th>
				<td>
					<input type="text" class="large-text" id="bookapp_action_target" name="bookapp_banner[action_target]" value="<?php echo esc_attr( $action_id ); ?>">
					<p class="description">ID التصنيف / الوسم / المنتج / المؤلف / دار النشر — أو الرابط الكامل (https://...) لو اخترت actionType = url. سيبه فاضي لو بدون أكشن.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function render_help_box( $post ) {
		?>
		<p><strong>ID البنر ده: <?php echo (int) $post->ID; ?></strong></p>
		<p>حط الـ ID ده في كومبوننت «البنرات» في صفحة <a href="<?php echo esc_url( admin_url( 'admin.php?page=bookapp-home-builder' ) ); ?>">Home Builder</a>.</p>
		<p>الصورة = «صورة البنر» (Featured Image) على اليمين. البنر لازم يكون «منشور» علشان يظهر في التطبيق.</p>
		<?php
	}

	public static function save_meta( $post_id, $post ) {
		if ( empty( $_POST['bookapp_banner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bookapp_banner_nonce'] ) ), 'bookapp_banner_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['bookapp_banner'] ) && is_array( $_POST['bookapp_banner'] ) ? wp_unslash( $_POST['bookapp_banner'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		$action_type = isset( $in['action_type'] ) ? sanitize_key( $in['action_type'] ) : '';
		if ( ! in_array( $action_type, self::action_types(), true ) ) {
			$action_type = '';
		}

		update_post_meta( $post_id, '_bookapp_title_en', sanitize_text_field( $in['title_en'] ?? '' ) );
		update_post_meta( $post_id, '_bookapp_subtitle_ar', sanitize_textarea_field( $in['subtitle_ar'] ?? '' ) );
		update_post_meta( $post_id, '_bookapp_subtitle_en', sanitize_textarea_field( $in['subtitle_en'] ?? '' ) );
		update_post_meta( $post_id, '_bookapp_action_type', $action_type );
		update_post_meta( $post_id, '_bookapp_action_target', BookApp_API_Home_V2::sanitize_action_target( $action_type, $in['action_target'] ?? '' ) );
	}

	public static function columns( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$out['bookapp_image'] = 'الصورة';
				$out[ $key ]          = $label;
				$out['bookapp_id']    = 'ID';
				$out['bookapp_action'] = 'الأكشن';
			} else {
				$out[ $key ] = $label;
			}
		}
		return $out;
	}

	public static function column_content( $column, $post_id ) {
		if ( 'bookapp_id' === $column ) {
			echo '<strong>' . (int) $post_id . '</strong>';
		} elseif ( 'bookapp_image' === $column ) {
			$thumb = get_the_post_thumbnail( $post_id, array( 80, 50 ), array( 'style' => 'border-radius:4px;object-fit:cover' ) );
			echo $thumb ? $thumb : '—'; // phpcs:ignore WordPress.Security.EscapeOutput
		} elseif ( 'bookapp_action' === $column ) {
			$type   = get_post_meta( $post_id, '_bookapp_action_type', true );
			$target = get_post_meta( $post_id, '_bookapp_action_target', true );
			echo $type ? esc_html( $type . ( $target ? ' → ' . $target : '' ) ) : '—';
		}
	}

	/**
	 * بيرجع بنرات جاهزة للـ API.
	 *
	 * @param array  $ids  IDs بالترتيب المطلوب. فاضية = كل البنرات المنشورة (الأحدث أولًا).
	 * @param string $lang ar|en
	 */
	public static function items( $ids, $lang = 'ar' ) {
		$ids   = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );
		$posts = array();

		if ( $ids ) {
			foreach ( $ids as $id ) {
				$post = get_post( $id );
				if ( $post && self::CPT === $post->post_type && 'publish' === $post->post_status ) {
					$posts[] = $post;
				}
			}
		} else {
			$posts = get_posts(
				array(
					'post_type'   => self::CPT,
					'post_status' => 'publish',
					'numberposts' => -1,
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
		}

		$out = array();
		foreach ( $posts as $post ) {
			$out[] = self::item( $post, $lang );
		}
		return $out;
	}

	public static function item( $post, $lang ) {
		$title_en    = (string) get_post_meta( $post->ID, '_bookapp_title_en', true );
		$subtitle_ar = (string) get_post_meta( $post->ID, '_bookapp_subtitle_ar', true );
		$subtitle_en = (string) get_post_meta( $post->ID, '_bookapp_subtitle_en', true );
		$image       = get_the_post_thumbnail_url( $post, 'full' );

		return array(
			'id'           => (int) $post->ID,
			'title'        => ( 'en' === $lang && '' !== $title_en ) ? $title_en : $post->post_title,
			'subtitle'     => ( 'en' === $lang && '' !== $subtitle_en ) ? $subtitle_en : $subtitle_ar,
			'imageUrl'     => $image ? $image : '',
			'actionType'   => (string) get_post_meta( $post->ID, '_bookapp_action_type', true ),
			'actionTarget' => (string) get_post_meta( $post->ID, '_bookapp_action_target', true ),
		);
	}
}
