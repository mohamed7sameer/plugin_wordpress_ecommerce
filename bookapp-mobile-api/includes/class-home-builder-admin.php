<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * صفحة Home Builder — إدارة كومبوننتس الهوم الجديدة (bookapp/v2/home).
 * منفصلة تمامًا عن صفحة إعدادات BookApp القديمة ولا تلمس option الهوم القديمة.
 */
class BookApp_API_Home_Builder_Admin {

	private static $hook = '';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu() {
		self::$hook = add_submenu_page(
			'bookapp-app',
			'Home Builder — الهوم الجديدة',
			'Home Builder',
			'manage_options',
			'bookapp-home-builder',
			array( __CLASS__, 'render' )
		);
	}

	public static function assets( $hook ) {
		if ( $hook !== self::$hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'bookapp-admin', BOOKAPP_API_URL . 'assets/admin.css', array( 'wp-color-picker' ), BOOKAPP_API_VERSION );
		wp_enqueue_style( 'bookapp-home-builder', BOOKAPP_API_URL . 'assets/home-builder.css', array( 'bookapp-admin' ), BOOKAPP_API_VERSION );
		wp_enqueue_script( 'bookapp-admin', BOOKAPP_API_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), BOOKAPP_API_VERSION, true );
		wp_enqueue_script( 'bookapp-home-builder', BOOKAPP_API_URL . 'assets/home-builder.js', array( 'jquery', 'jquery-ui-sortable' ), BOOKAPP_API_VERSION, true );
	}

	private static function labels() {
		return array(
			'types'   => array(
				'announcement'     => 'شريط إعلان (announcement)',
				'banners'          => 'بنرات (banners)',
				'component_ids'    => 'كروت بالـ IDs (component_ids)',
				'products_section' => 'سلايدر منتجات (products_section)',
				'call_to_action'   => 'دعوة لإجراء (call_to_action)',
			),
			'actions' => array(
				''          => 'بدون أكشن',
				'category'  => 'تصنيف (category)',
				'tag'       => 'وسم (tag)',
				'product'   => 'منتج (product)',
				'author'    => 'مؤلف (author)',
				'publisher' => 'دار نشر (publisher)',
				'url'       => 'رابط (url)',
			),
			'sources' => array(
				'all'       => 'كل المنتجات (all)',
				'sale'      => 'المخفّض (sale)',
				'featured'  => 'المميز (featured)',
				'category'  => 'تصنيف (category)',
				'tag'       => 'وسم (tag)',
				'author'    => 'مؤلف (author)',
				'publisher' => 'دار نشر (publisher)',
				'product'   => 'منتج واحد (product)',
			),
			'sorts'   => array(
				'newest'            => 'الأحدث (newest)',
				'oldest'            => 'الأقدم (oldest)',
				'best_selling'      => 'الأكثر مبيعًا (best_selling)',
				'price_low_to_high' => 'السعر من الأقل (price_low_to_high)',
				'price_high_to_low' => 'السعر من الأعلى (price_high_to_low)',
			),
		);
	}

	public static function save() {
		if ( empty( $_POST['bookapp_home_builder_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bookapp_home_builder_nonce'] ) ), 'bookapp_home_builder_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$in         = isset( $_POST['components'] ) && is_array( $_POST['components'] ) ? wp_unslash( $_POST['components'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$components = array();

		foreach ( $in as $row ) {
			if ( ! is_array( $row ) || empty( $row['type'] ) ) {
				continue;
			}
			$type = sanitize_key( $row['type'] );
			if ( ! in_array( $type, BookApp_API_Home_V2::types(), true ) ) {
				continue;
			}
			$components[] = array(
				'type'    => $type,
				'enabled' => empty( $row['enabled'] ) ? 0 : 1,
				'data'    => self::sanitize_data( $type, $row ),
			);
		}

		update_option( BookApp_API_Home_V2::OPTION, $components, false );
		add_settings_error( 'bookapp_home_builder', 'saved', 'تم حفظ الهوم الجديدة. جرّب <code>GET /wp-json/bookapp/v2/home</code> — ولو الرد قديم امسح الكاش.', 'updated' );
	}

	private static function sanitize_data( $type, $row ) {
		switch ( $type ) {
			case 'announcement':
				$action_type = self::valid_action( $row['action_type'] ?? '', BookApp_API_Home_V2::action_types(), '' );
				return array(
					'text'          => array(
						'ar' => sanitize_textarea_field( $row['text_ar'] ?? '' ),
						'en' => sanitize_textarea_field( $row['text_en'] ?? '' ),
					),
					'action_type'   => $action_type,
					'action_target' => BookApp_API_Home_V2::sanitize_action_target( $action_type, $row['action_target'] ?? '' ),
				);

			case 'banners':
				return array( 'banner_ids' => self::ids_csv( $row['banner_ids'] ?? '' ) );

			case 'component_ids':
				return array(
					'title'         => array(
						'ar' => sanitize_text_field( $row['title_ar'] ?? '' ),
						'en' => sanitize_text_field( $row['title_en'] ?? '' ),
					),
					'button'        => array(
						'ar' => sanitize_text_field( $row['button_ar'] ?? '' ),
						'en' => sanitize_text_field( $row['button_en'] ?? '' ),
					),
					'action_type'   => self::valid_action( $row['action_type'] ?? 'category', BookApp_API_Home_V2::entity_types(), 'category' ),
					'ids'           => self::ids_csv( $row['ids'] ?? '' ),
					'default_image' => esc_url_raw( $row['default_image'] ?? '' ),
				);

			case 'products_section':
				return array(
					'title'         => array(
						'ar' => sanitize_text_field( $row['title_ar'] ?? '' ),
						'en' => sanitize_text_field( $row['title_en'] ?? '' ),
					),
					'button'        => array(
						'ar' => sanitize_text_field( $row['button_ar'] ?? '' ),
						'en' => sanitize_text_field( $row['button_en'] ?? '' ),
					),
					'sort_by'       => self::valid_action( $row['sort_by'] ?? 'newest', BookApp_API_Home_V2::sorts(), 'newest' ),
					'action_type'   => self::valid_action( $row['action_type'] ?? 'all', BookApp_API_Home_V2::product_sources(), 'all' ),
					'action_target' => self::digits( $row['action_target'] ?? '' ),
					'limit'         => max( 1, min( 24, absint( $row['limit'] ?? 10 ) ) ),
				);

			case 'call_to_action':
				$action_type = self::valid_action( $row['action_type'] ?? '', BookApp_API_Home_V2::action_types(), '' );
				return array(
					'title'         => array(
						'ar' => sanitize_text_field( $row['title_ar'] ?? '' ),
						'en' => sanitize_text_field( $row['title_en'] ?? '' ),
					),
					'subtitle'      => array(
						'ar' => sanitize_textarea_field( $row['subtitle_ar'] ?? '' ),
						'en' => sanitize_textarea_field( $row['subtitle_en'] ?? '' ),
					),
					'button'        => array(
						'ar' => sanitize_text_field( $row['button_ar'] ?? '' ),
						'en' => sanitize_text_field( $row['button_en'] ?? '' ),
					),
					'action_type'   => $action_type,
					'action_target' => BookApp_API_Home_V2::sanitize_action_target( $action_type, $row['action_target'] ?? '' ),
				);
		}
		return array();
	}

	private static function valid_action( $value, $allowed, $fallback ) {
		$value = sanitize_key( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private static function digits( $value ) {
		return preg_replace( '/\D/', '', (string) $value );
	}

	private static function ids_csv( $value ) {
		return array_values( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		settings_errors( 'bookapp_home_builder' );

		$saved      = get_option( BookApp_API_Home_V2::OPTION, null );
		$components = is_array( $saved ) ? $saved : BookApp_API_Home_V2::defaults();
		$labels     = self::labels();
		$banners    = get_posts(
			array(
				'post_type'   => BookApp_API_Banners::CPT,
				'post_status' => array( 'publish', 'draft', 'pending' ),
				'numberposts' => 50,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
		?>
		<div class="wrap bookapp-admin bookapp-builder">
			<h1>Home Builder — الهوم الجديدة</h1>
			<p>
				الهوم هنا كومبوننتس: أضف، رتّب بالسحب من المقبض، فعّل/عطّل، أو امسح. التطبيق بيقرأ
				<code>GET <?php echo esc_html( rest_url( 'bookapp/v2/home' ) ); ?>?lang=ar</code>
				— <a href="<?php echo esc_url( rest_url( 'bookapp/v2/home' ) . '?lang=ar' ); ?>" target="_blank">شوف الرد الحالي</a>.
				الهوم القديمة (<code>bookapp/v1/home</code>) شغالة زي ما هي ومش بتتأثر بالصفحة دي.
			</p>
			<?php if ( ! is_array( $saved ) ) : ?>
				<div class="notice notice-info"><p>دي كومبوننتس افتراضية للبداية — أول ضغطة «حفظ» بتثبتها وتقدر تعدل براحتك.</p></div>
			<?php endif; ?>

			<div class="bookapp-builder-banners">
				<strong>البنرات المتاحة (علشان تاخد الـ IDs):</strong>
				<?php if ( $banners ) : ?>
					<ul>
						<?php foreach ( $banners as $banner ) : ?>
							<li>
								<code><?php echo (int) $banner->ID; ?></code> — <?php echo esc_html( $banner->post_title ); ?>
								<?php if ( 'publish' !== $banner->post_status ) : ?><em>(مش منشور — مش هيظهر)</em><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p>مفيش بنرات لسه — <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . BookApp_API_Banners::CPT ) ); ?>">أضف أول بنر</a>.</p>
				<?php endif; ?>
			</div>

			<form method="post">
				<?php wp_nonce_field( 'bookapp_home_builder_save', 'bookapp_home_builder_nonce' ); ?>

				<div id="bookapp-components" data-next-index="<?php echo (int) count( $components ); ?>">
					<?php
					foreach ( $components as $i => $component ) {
						self::render_row( $i, $component['type'], isset( $component['data'] ) ? $component['data'] : array(), ! isset( $component['enabled'] ) || $component['enabled'] );
					}
					?>
				</div>

				<div class="bookapp-builder-add">
					<select id="bookapp-comp-type">
						<?php foreach ( $labels['types'] as $type => $label ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button button-secondary" id="bookapp-add-component">+ إضافة كومبوننت</button>
				</div>

				<?php submit_button( 'حفظ الهوم الجديدة' ); ?>
			</form>

			<?php foreach ( array_keys( $labels['types'] ) as $type ) : ?>
				<script type="text/html" id="tmpl-bookapp-comp-<?php echo esc_attr( $type ); ?>">
					<?php self::render_row( '__i__', $type, array(), true ); ?>
				</script>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_row( $i, $type, $data, $enabled ) {
		$labels = self::labels();
		$name   = 'components[' . $i . ']';
		$title  = isset( $data['title'] ) ? BookApp_API_Helpers::i18n( $data['title'], 'ar' ) : ( isset( $data['text'] ) ? BookApp_API_Helpers::i18n( $data['text'], 'ar' ) : '' );
		?>
		<div class="bookapp-comp<?php echo $enabled ? '' : ' bookapp-comp-disabled'; ?>">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="<?php echo esc_attr( $type ); ?>">
			<div class="bookapp-comp-header">
				<span class="bookapp-comp-handle dashicons dashicons-move" title="اسحب لإعادة الترتيب"></span>
				<strong><?php echo esc_html( $labels['types'][ $type ] ); ?></strong>
				<span class="bookapp-comp-title"><?php echo esc_html( mb_substr( (string) $title, 0, 60 ) ); ?></span>
				<span class="bookapp-comp-actions">
					<label class="bookapp-comp-enabled">
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
						<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $enabled ); ?>> ظاهر
					</label>
					<button type="button" class="button-link bookapp-comp-toggle">فتح/قفل</button>
					<button type="button" class="button-link-delete bookapp-comp-remove">مسح</button>
				</span>
			</div>
			<div class="bookapp-comp-body">
				<?php
				switch ( $type ) {
					case 'announcement':
						self::field_textarea( $name . '[text_ar]', 'النص عربي', $data['text']['ar'] ?? '' );
						self::field_textarea( $name . '[text_en]', 'النص إنجليزي', $data['text']['en'] ?? '' );
						self::field_select( $name . '[action_type]', 'actionType', $labels['actions'], $data['action_type'] ?? '' );
						self::field_text( $name . '[action_target]', 'actionTarget (ID أو رابط)', $data['action_target'] ?? '', 'ID للتصنيف/المنتج/... أو الرابط الكامل لو actionType = url.' );
						break;

					case 'banners':
						self::field_text( $name . '[banner_ids]', 'IDs البنرات (مفصولة بفاصلة)', implode( ',', array_map( 'absint', (array) ( $data['banner_ids'] ?? array() ) ) ), 'مثال: 12,15,9 — بترجع بنفس الترتيب. فاضية = كل البنرات المنشورة (الأحدث أولًا).' );
						break;

					case 'component_ids':
						self::field_text( $name . '[title_ar]', 'العنوان عربي', $data['title']['ar'] ?? '' );
						self::field_text( $name . '[title_en]', 'العنوان إنجليزي', $data['title']['en'] ?? '' );
						self::field_text( $name . '[button_ar]', 'نص الزرار عربي', $data['button']['ar'] ?? '' );
						self::field_text( $name . '[button_en]', 'نص الزرار إنجليزي', $data['button']['en'] ?? '' );
						self::field_select( $name . '[action_type]', 'نوع العناصر (actionType)', array_intersect_key( $labels['actions'], array_flip( BookApp_API_Home_V2::entity_types() ) ), $data['action_type'] ?? 'category' );
						self::field_text( $name . '[ids]', 'الـ IDs (مفصولة بفاصلة)', implode( ',', array_map( 'absint', (array) ( $data['ids'] ?? array() ) ) ), 'لو النوع category وسيبتها فاضية → بيرجع كل التصنيفات الرئيسية.' );
						self::field_image( $name . '[default_image]', 'صورة افتراضية', $data['default_image'] ?? '' );
						break;

					case 'products_section':
						self::field_text( $name . '[title_ar]', 'العنوان عربي', $data['title']['ar'] ?? '' );
						self::field_text( $name . '[title_en]', 'العنوان إنجليزي', $data['title']['en'] ?? '' );
						self::field_text( $name . '[button_ar]', 'نص الزرار عربي', $data['button']['ar'] ?? '' );
						self::field_text( $name . '[button_en]', 'نص الزرار إنجليزي', $data['button']['en'] ?? '' );
						self::field_select( $name . '[sort_by]', 'الترتيب (sort_by)', $labels['sorts'], $data['sort_by'] ?? 'newest' );
						self::field_select( $name . '[action_type]', 'المصدر (action_type)', $labels['sources'], $data['action_type'] ?? 'all' );
						self::field_number( $name . '[action_target]', 'action_target (ID)', $data['action_target'] ?? '', 'مطلوب مع category/tag/author/publisher/product. مش محتاجه مع all/sale/featured.' );
						self::field_number( $name . '[limit]', 'عدد المنتجات', $data['limit'] ?? 10, 'من 1 لـ 24.' );
						break;

					case 'call_to_action':
						self::field_text( $name . '[title_ar]', 'العنوان عربي', $data['title']['ar'] ?? '' );
						self::field_text( $name . '[title_en]', 'العنوان إنجليزي', $data['title']['en'] ?? '' );
						self::field_textarea( $name . '[subtitle_ar]', 'الوصف عربي', $data['subtitle']['ar'] ?? '' );
						self::field_textarea( $name . '[subtitle_en]', 'الوصف إنجليزي', $data['subtitle']['en'] ?? '' );
						self::field_text( $name . '[button_ar]', 'نص الزرار عربي', $data['button']['ar'] ?? '' );
						self::field_text( $name . '[button_en]', 'نص الزرار إنجليزي', $data['button']['en'] ?? '' );
						self::field_select( $name . '[action_type]', 'actionType', $labels['actions'], $data['action_type'] ?? '' );
						self::field_text( $name . '[action_target]', 'actionTarget (ID أو رابط)', $data['action_target'] ?? '', 'ID للتصنيف/المنتج/... أو الرابط الكامل لو actionType = url.' );
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	private static function field_text( $name, $label, $value, $hint = '' ) {
		?>
		<p class="bookapp-field">
			<label><?php echo esc_html( $label ); ?></label>
			<input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php if ( $hint ) : ?><span class="description"><?php echo esc_html( $hint ); ?></span><?php endif; ?>
		</p>
		<?php
	}

	private static function field_textarea( $name, $label, $value ) {
		?>
		<p class="bookapp-field">
			<label><?php echo esc_html( $label ); ?></label>
			<textarea class="large-text" rows="2" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
		</p>
		<?php
	}

	private static function field_number( $name, $label, $value, $hint = '' ) {
		?>
		<p class="bookapp-field">
			<label><?php echo esc_html( $label ); ?></label>
			<input type="number" min="0" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php if ( $hint ) : ?><span class="description"><?php echo esc_html( $hint ); ?></span><?php endif; ?>
		</p>
		<?php
	}

	private static function field_select( $name, $label, $options, $current ) {
		?>
		<p class="bookapp-field">
			<label><?php echo esc_html( $label ); ?></label>
			<select name="<?php echo esc_attr( $name ); ?>">
				<?php foreach ( $options as $value => $text ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $current, (string) $value ); ?>><?php echo esc_html( $text ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	private static function field_image( $name, $label, $value ) {
		?>
		<p class="bookapp-field">
			<label><?php echo esc_html( $label ); ?></label>
			<input class="regular-text bookapp-image" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<button type="button" class="button bookapp-pick">اختيار صورة</button>
			<span class="bookapp-image-preview">
				<?php if ( $value ) : ?>
					<br><img src="<?php echo esc_url( $value ); ?>" alt="" style="max-width:220px;margin-top:8px">
				<?php endif; ?>
			</span>
		</p>
		<?php
	}
}
