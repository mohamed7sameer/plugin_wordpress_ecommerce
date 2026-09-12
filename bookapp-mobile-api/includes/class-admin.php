<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookApp_API_Admin {

	public static function init() {
		// Priority 9: must run before core's _add_post_type_submenus (10) so the
		// banners CPT (show_in_menu = bookapp-app) attaches under an existing parent
		// without knocking the settings link out of the submenu.
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu() {
		add_menu_page(
			'BookApp',
			'BookApp',
			'manage_options',
			'bookapp-app',
			array( __CLASS__, 'render' ),
			'dashicons-smartphone',
			58
		);
		// Explicit first submenu entry so the settings page (logo/colors/fonts)
		// always keeps a visible link even after other submenus register.
		add_submenu_page(
			'bookapp-app',
			'إعدادات تطبيق كتب أي حاجة',
			'الإعدادات',
			'manage_options',
			'bookapp-app',
			array( __CLASS__, 'render' )
		);
	}

	public static function assets( $hook ) {
		if ( 'toplevel_page_bookapp-app' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'bookapp-admin', BOOKAPP_API_URL . 'assets/admin.css', array( 'wp-color-picker' ), BOOKAPP_API_VERSION );
		wp_enqueue_script( 'bookapp-admin', BOOKAPP_API_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), BOOKAPP_API_VERSION, true );
	}

	public static function save() {
		if ( empty( $_POST['bookapp_app_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bookapp_app_nonce'] ) ), 'bookapp_app_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$in = isset( $_POST['bookapp'] ) ? wp_unslash( $_POST['bookapp'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		BookApp_API_Settings::save( $in );

		add_settings_error( 'bookapp_app', 'saved', 'تم الحفظ. اعمل Refresh في الأب بعد مسح الكاش.', 'updated' );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		settings_errors( 'bookapp_app' );
		$app = BookApp_API_Settings::get();
		$id  = $app['identity'];
		$ver = isset( $app['app'] ) && is_array( $app['app'] ) ? $app['app'] : array();
		?>
		<div class="wrap bookapp-admin">
			<h1>إعدادات تطبيق كتب أي حاجة</h1>
			<p>الدعم، الهوية، الألوان، الخطوط، ونسخ التطبيق. محتوى الهوم من <a href="<?php echo esc_url( admin_url( 'admin.php?page=bookapp-home-builder' ) ); ?>">Home Builder</a> — الأب بيقرأ <code>GET /wp-json/bookapp/v2/home</code>.</p>
			<form method="post">
				<?php wp_nonce_field( 'bookapp_app_save', 'bookapp_app_nonce' ); ?>

				<h2>عام</h2>
				<table class="form-table">
					<tr><th>واتساب</th><td><input class="regular-text" name="bookapp[whatsapp]" value="<?php echo esc_attr( $app['whatsapp'] ?? '' ); ?>"></td></tr>
					<tr><th>إيميل الدعم</th><td><input class="regular-text" type="email" name="bookapp[support_email]" value="<?php echo esc_attr( $app['support_email'] ?? '' ); ?>"></td></tr>
				</table>

				<h2>نسخ التطبيق</h2>
				<p class="description">الأب بيقرأها من <code>GET /bootstrap</code> و <code>GET /settings</code> في كائن <code>app</code>. لو النسخة المركبة أقل من الحد الأدنى → تحديث إجباري. لو أقل من الأحدث بس أكبر من أو تساوي الحد الأدنى → تحديث اختياري. ابعت <code>X-BookApp-Platform</code> و <code>X-BookApp-Version</code> عشان الـ API يحسب <code>app.status</code>.</p>
				<table class="form-table">
					<tr>
						<th>أندرويد</th>
						<td>
							<label>أحدث نسخة <input class="small-text" name="bookapp[android_latest]" value="<?php echo esc_attr( $ver['android']['latest'] ?? '1.0.0' ); ?>" placeholder="1.0.2"></label>
							<label>حد أدنى <input class="small-text" name="bookapp[android_minimum]" value="<?php echo esc_attr( $ver['android']['minimum'] ?? '1.0.0' ); ?>" placeholder="1.0.0"></label>
							<p><input class="regular-text" name="bookapp[android_store]" value="<?php echo esc_attr( $ver['android']['store_url'] ?? '' ); ?>" placeholder="https://play.google.com/store/apps/details?id=..."></p>
							<p class="description">رابط جوجل بلاي. الحد الأدنى = أقل نسخة مسموح تشتغل من غيرها يتقفل الأب.</p>
						</td>
					</tr>
					<tr>
						<th>آيفون</th>
						<td>
							<label>أحدث نسخة <input class="small-text" name="bookapp[ios_latest]" value="<?php echo esc_attr( $ver['ios']['latest'] ?? '1.0.0' ); ?>" placeholder="1.0.2"></label>
							<label>حد أدنى <input class="small-text" name="bookapp[ios_minimum]" value="<?php echo esc_attr( $ver['ios']['minimum'] ?? '1.0.0' ); ?>" placeholder="1.0.0"></label>
							<p><input class="regular-text" name="bookapp[ios_store]" value="<?php echo esc_attr( $ver['ios']['store_url'] ?? '' ); ?>" placeholder="https://apps.apple.com/app/id..."></p>
						</td>
					</tr>
					<tr>
						<th>عنوان التحديث عربي / إنجليزي</th>
						<td>
							<input class="regular-text" name="bookapp[app_title_ar]" value="<?php echo esc_attr( $ver['title']['ar'] ?? '' ); ?>" placeholder="تحديث جديد">
							<input class="regular-text" name="bookapp[app_title_en]" value="<?php echo esc_attr( $ver['title']['en'] ?? '' ); ?>" placeholder="New update">
						</td>
					</tr>
					<tr>
						<th>نص التحديث عربي</th>
						<td><textarea class="large-text" name="bookapp[app_message_ar]" rows="2"><?php echo esc_textarea( $ver['message']['ar'] ?? '' ); ?></textarea></td>
					</tr>
					<tr>
						<th>نص التحديث إنجليزي</th>
						<td><textarea class="large-text" name="bookapp[app_message_en]" rows="2"><?php echo esc_textarea( $ver['message']['en'] ?? '' ); ?></textarea></td>
					</tr>
				</table>

				<h2>هوية التطبيق</h2>
				<p class="description">اللوجو والألوان والخطوط تترجع للأب من <code>GET /wp-json/bookapp/v1/settings</code> ومن <code>bootstrap</code>.</p>
				<table class="form-table">
					<tr><th>اسم قصير عربي / إنجليزي</th><td>
						<input class="regular-text" name="bookapp[name_short_ar]" value="<?php echo esc_attr( $id['name']['short']['ar'] ); ?>" placeholder="كتب أي حاجة">
						<input class="regular-text" name="bookapp[name_short_en]" value="<?php echo esc_attr( $id['name']['short']['en'] ); ?>" placeholder="Ay7aaga Books">
					</td></tr>
					<tr><th>اسم كامل عربي / إنجليزي</th><td>
						<input class="regular-text" name="bookapp[name_full_ar]" value="<?php echo esc_attr( $id['name']['full']['ar'] ); ?>">
						<input class="regular-text" name="bookapp[name_full_en]" value="<?php echo esc_attr( $id['name']['full']['en'] ); ?>">
					</td></tr>
					<tr><th>سطر تعريفي</th><td>
						<input class="regular-text" name="bookapp[tagline_ar]" value="<?php echo esc_attr( $id['tagline']['ar'] ); ?>">
						<input class="regular-text" name="bookapp[tagline_en]" value="<?php echo esc_attr( $id['tagline']['en'] ); ?>">
					</td></tr>
					<tr><th>اللوجو</th><td><?php self::image_field( 'bookapp[logo_full]', $id['logo']['full'] ); ?></td></tr>
					<tr><th>الأيقونة</th><td><?php self::image_field( 'bookapp[logo_icon]', $id['logo']['icon'] ); ?></td></tr>
					<tr><th>سبلاش</th><td><?php self::image_field( 'bookapp[logo_splash]', $id['logo']['splash'] ); ?></td></tr>
				</table>

				<h2>درجات الألوان</h2>
				<table class="form-table">
					<?php
					$color_labels = array(
						'primary'    => 'أساسي',
						'secondary'  => 'ثانوي',
						'success'    => 'نجاح',
						'warning'    => 'تحذير',
						'error'      => 'خطأ / تخفيض',
						'text'       => 'نص',
						'title'      => 'عناوين',
						'muted'      => 'نص باهت',
						'background' => 'خلفية',
						'surface'    => 'سطح',
						'status_bar' => 'شريط الحالة',
						'link'       => 'رابط',
						'link_hover' => 'رابط عند المرور',
					);
					foreach ( $color_labels as $ckey => $clabel ) :
						$hex = $id['colors'][ $ckey ];
						?>
						<tr>
							<th><?php echo esc_html( $clabel ); ?></th>
							<td>
								<?php self::color_field( 'bookapp[color_' . $ckey . ']', $hex ); ?>
								<?php if ( in_array( $ckey, array( 'primary', 'secondary', 'success', 'warning', 'error' ), true ) ) : ?>
									<?php self::scale_swatches( $hex ); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2>الخطوط</h2>
				<table class="form-table">
					<tr><th>العائلة</th><td><input class="regular-text" name="bookapp[font_family]" value="<?php echo esc_attr( $id['fonts']['family'] ); ?>"></td></tr>
					<tr><th>رابط جوجل فونتس</th><td>
						<input class="large-text" name="bookapp[font_url]" value="<?php echo esc_attr( $id['fonts']['url'] ); ?>">
						<p class="description">لو فاضي بيتبني تلقائي من الاسم والأوزان.</p>
					</td></tr>
					<tr><th>الأوزان</th><td>
						<input class="regular-text" name="bookapp[font_weights]" value="<?php echo esc_attr( implode( ',', $id['fonts']['weights'] ) ); ?>">
						<p class="description">مثال: <code>400,500,600,700</code></p>
					</td></tr>
					<tr><th>المقاسات (px)</th><td>
						<label>نص <input type="number" name="bookapp[font_size_body]" value="<?php echo (int) $id['fonts']['sizes']['body']; ?>" style="width:70px"></label>
						<label>عناوين <input type="number" name="bookapp[font_size_heading]" value="<?php echo (int) $id['fonts']['sizes']['heading']; ?>" style="width:70px"></label>
						<label>عنوان قسم <input type="number" name="bookapp[font_size_title]" value="<?php echo (int) $id['fonts']['sizes']['title']; ?>" style="width:70px"></label>
						<label>زر <input type="number" name="bookapp[font_size_button]" value="<?php echo (int) $id['fonts']['sizes']['button']; ?>" style="width:70px"></label>
						<label>توضيح <input type="number" name="bookapp[font_size_caption]" value="<?php echo (int) $id['fonts']['sizes']['caption']; ?>" style="width:70px"></label>
					</td></tr>
				</table>

				<?php submit_button( 'حفظ إعدادات الأب' ); ?>
			</form>
		</div>
		<?php
	}

	private static function image_field( $name, $value ) {
		?>
		<input class="regular-text bookapp-image" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
		<button type="button" class="button bookapp-pick">اختيار صورة</button>
		<span class="bookapp-image-preview">
			<?php if ( $value ) : ?>
				<br><img src="<?php echo esc_url( $value ); ?>" alt="" style="max-width:220px;margin-top:8px">
			<?php endif; ?>
		</span>
		<?php
	}

	private static function color_field( $name, $value ) {
		?>
		<input class="bookapp-color" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $value ); ?>">
		<?php
	}

	private static function scale_swatches( $hex ) {
		$scale = BookApp_API_Settings::scale( $hex );
		echo '<div class="bookapp-scale" data-hex="' . esc_attr( $hex ) . '">';
		foreach ( $scale as $step => $color ) {
			echo '<span title="' . esc_attr( $step . ' ' . $color ) . '" style="background:' . esc_attr( $color ) . '"></span>';
		}
		echo '</div>';
	}
}
