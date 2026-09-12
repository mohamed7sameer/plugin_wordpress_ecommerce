<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * «العرض في التطبيق» — فلاج إخفاء منتجات / تصنيفات / وسوم / مؤلفين / دور نشر من API الموبايل.
 *
 * - التخزين: meta «_bookapp_hide_in_app» بقيمة '1' على العنصر المخفي فقط. غياب الـ meta = ظاهر (الافتراضي).
 * - الفلترة تشتغل فقط أثناء طلبات REST بتاعة التطبيق (bookapp/* + wc/store/*) — الموقع لا يتأثر.
 * - إخفاء تصنيف رئيسي (product_cat) يخفي كل التصنيفات الفرعية تحته (بيتكتب الـ meta عليهم وقت الحفظ).
 * - الفلاج بيتزامن مع ترجمات Polylang تلقائيًا.
 * - المنتج المخفي: 404 في المسارات المفردة، ويختفي من القوائم والبحث والهوم والويشليست والمقارنة،
 *   لكنه يظل ظاهرًا داخل تفاصيل الطلبات القديمة وكروت المنتج نفسها بتحتفظ بأسماء التصنيفات/المؤلفين.
 */
class BookApp_API_Visibility {

	const META = '_bookapp_hide_in_app';

	private static $active = false;

	public static function taxonomies() {
		return array( 'product_cat', 'product_tag', 'pa_writer', 'pa_publisher' );
	}

	public static function init() {
		// REST: تفعيل الفلترة على مسارات التطبيق + بوابة 404 للمنتج المخفي.
		add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'rest_gate' ), 9, 3 );
		// استعلامات المنتجات (شغالة فقط والفلاج active).
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_product_query' ) );
		// استعلامات التيرمز — SQL مباشر عشان يغطي Store API (WP_Term_Query بدون فلتر get_terms).
		add_filter( 'terms_clauses', array( __CLASS__, 'filter_terms_clauses' ), 10, 3 );

		// أدمن: حقول التيرمز (تصنيفات / وسوم / مؤلفين / دور نشر).
		foreach ( self::taxonomies() as $tax ) {
			add_action( "{$tax}_add_form_fields", array( __CLASS__, 'term_add_field' ) );
			add_action( "{$tax}_edit_form_fields", array( __CLASS__, 'term_edit_field' ) );
			add_action( "created_{$tax}", array( __CLASS__, 'save_term' ) );
			add_action( "edited_{$tax}", array( __CLASS__, 'save_term' ) );
			add_filter( "manage_edit-{$tax}_columns", array( __CLASS__, 'admin_column' ) );
			add_filter( "manage_{$tax}_custom_column", array( __CLASS__, 'term_column_content' ), 10, 3 );
		}

		// صفحة تعريف الصفة (Products → Attributes → الناشر/المؤلف) مش قائمة التيرمز.
		add_action( 'woocommerce_after_edit_attribute_fields', array( __CLASS__, 'attribute_edit_notice' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 25 );

		// أدمن: المنتجات (metabox + عمود + Quick/Bulk Edit).
		add_action( 'add_meta_boxes_product', array( __CLASS__, 'product_metabox' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save_product' ), 10, 2 );
		add_filter( 'manage_edit-product_columns', array( __CLASS__, 'admin_column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'product_column_content' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'quick_edit_field' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( __CLASS__, 'quick_edit_field' ), 10, 2 );
	}

	public static function menu() {
		add_submenu_page(
			'bookapp-app',
			'دور النشر',
			'دور النشر',
			'manage_options',
			'edit-tags.php?taxonomy=pa_publisher&post_type=product'
		);
		add_submenu_page(
			'bookapp-app',
			'المؤلفون',
			'المؤلفون',
			'manage_options',
			'edit-tags.php?taxonomy=pa_writer&post_type=product'
		);
	}

	public static function attribute_edit_notice() {
		$id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $id || ! function_exists( 'wc_get_attribute' ) ) {
			return;
		}
		$attr = wc_get_attribute( $id );
		if ( ! $attr || empty( $attr->slug ) ) {
			return;
		}
		$tax = $attr->slug;
		if ( ! in_array( $tax, array( 'pa_publisher', 'pa_writer' ), true ) ) {
			return;
		}
		$is_pub = 'pa_publisher' === $tax;
		$label  = $is_pub ? 'دور النشر' : 'المؤلفين';
		$url    = admin_url( 'edit-tags.php?taxonomy=' . $tax . '&post_type=product' );
		?>
		<tr class="form-field">
			<th scope="row">عرض في التطبيق</th>
			<td>
				<p>
					الـ checkbox بيتظبط على كل <?php echo $is_pub ? 'دار نشر' : 'مؤلف'; ?> لوحده، مش على الصفة كلها.
					العمود اسمه «التطبيق» في قائمة <?php echo esc_html( $label ); ?>.
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">افتح قائمة <?php echo esc_html( $label ); ?></a>
				</p>
			</td>
		</tr>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * الحالة
	 * ------------------------------------------------------------------- */

	public static function is_product_hidden( $product_id ) {
		return '1' === get_post_meta( (int) $product_id, self::META, true );
	}

	public static function is_term_hidden( $term_id ) {
		return '1' === get_term_meta( (int) $term_id, self::META, true );
	}

	public static function filter_product_ids( $ids ) {
		$out = array();
		foreach ( (array) $ids as $id ) {
			$id = (int) $id;
			if ( $id && ! self::is_product_hidden( $id ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}

	public static function set_product_hidden( $product_id, $hidden ) {
		$ids = array( (int) $product_id );
		if ( function_exists( 'pll_get_post_translations' ) ) {
			foreach ( pll_get_post_translations( (int) $product_id ) as $tid ) {
				$ids[] = (int) $tid;
			}
		}
		foreach ( array_unique( array_filter( $ids ) ) as $id ) {
			if ( $hidden ) {
				update_post_meta( $id, self::META, '1' );
			} else {
				delete_post_meta( $id, self::META );
			}
		}
	}

	public static function set_term_hidden( $term_id, $hidden ) {
		$term_id = (int) $term_id;
		$term    = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		$targets = array( $term_id );

		// إخفاء تصنيف رئيسي = إخفاء كل الفرعي تحته (والعكس عند الإظهار).
		if ( 'product_cat' === $term->taxonomy ) {
			$args = array(
				'taxonomy'   => 'product_cat',
				'child_of'   => $term_id,
				'hide_empty' => false,
				'fields'     => 'ids',
			);
			if ( function_exists( 'PLL' ) ) {
				$args['lang'] = '';
			}
			$children = get_terms( $args );
			if ( ! is_wp_error( $children ) ) {
				$targets = array_merge( $targets, array_map( 'intval', $children ) );
			}
		}

		// مزامنة ترجمات Polylang لكل عنصر.
		if ( function_exists( 'pll_get_term_translations' ) ) {
			$with_translations = $targets;
			foreach ( $targets as $tid ) {
				foreach ( pll_get_term_translations( $tid ) as $trans_id ) {
					$with_translations[] = (int) $trans_id;
				}
			}
			$targets = $with_translations;
		}

		foreach ( array_unique( array_filter( $targets ) ) as $tid ) {
			if ( $hidden ) {
				update_term_meta( $tid, self::META, '1' );
			} else {
				delete_term_meta( $tid, self::META );
			}
		}
	}

	/* ---------------------------------------------------------------------
	 * REST — التفعيل + بوابة 404
	 * ------------------------------------------------------------------- */

	public static function rest_gate( $response, $handler, $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return $response;
		}
		$route = $request->get_route();
		if ( 0 !== strpos( $route, '/bookapp/' ) && 0 !== strpos( $route, '/wc/store' ) ) {
			return $response;
		}
		self::$active = true;

		$product_id = 0;
		if ( preg_match( '#^/bookapp/v\d+/products/(\d+)(?:/reviews)?$#', $route, $m ) ) {
			$product_id = (int) $m[1];
		} elseif ( preg_match( '#^/wc/store/v1/products/([^/]+)$#', $route, $m ) ) {
			$segment = $m[1];
			$static  = array( 'categories', 'tags', 'attributes', 'brands', 'collection-data', 'reviews' );
			if ( ! in_array( $segment, $static, true ) ) {
				if ( ctype_digit( $segment ) ) {
					$product_id = (int) $segment;
				} else {
					$found      = get_page_by_path( sanitize_title( $segment ), OBJECT, 'product' );
					$product_id = $found ? (int) $found->ID : 0;
				}
			}
		}

		if ( $product_id && self::is_product_hidden( $product_id ) ) {
			return BookApp_API_Helpers::error( 'bookapp_product_missing', 'Product not found.', 404 );
		}

		return $response;
	}

	/* ---------------------------------------------------------------------
	 * فلترة الاستعلامات (شغالة فقط أثناء طلبات API التطبيق)
	 * ------------------------------------------------------------------- */

	public static function product_meta_clause() {
		return array(
			'relation' => 'OR',
			array(
				'key'     => self::META,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => self::META,
				'value'   => '1',
				'compare' => '!=',
			),
		);
	}

	public static function filter_product_query( $query ) {
		if ( ! self::$active || ! $query instanceof WP_Query ) {
			return;
		}
		if ( ! in_array( 'product', (array) $query->get( 'post_type' ), true ) ) {
			return;
		}
		$meta   = (array) $query->get( 'meta_query' );
		$meta[] = self::product_meta_clause();
		$query->set( 'meta_query', $meta );
	}

	public static function filter_terms_clauses( $clauses, $taxonomies, $args ) {
		if ( ! self::$active ) {
			return $clauses;
		}
		if ( ! array_intersect( (array) $taxonomies, self::taxonomies() ) ) {
			return $clauses;
		}
		// استعلامات تيرمز منتج معيّن (كروت المنتج) بتفضل كاملة — الإخفاء من القوائم فقط.
		if ( ! empty( $args['object_ids'] ) ) {
			return $clauses;
		}
		global $wpdb;
		$clauses['where'] .= $wpdb->prepare(
			" AND t.term_id NOT IN ( SELECT tm.term_id FROM {$wpdb->termmeta} tm WHERE tm.meta_key = %s AND tm.meta_value = '1' )",
			self::META
		);
		return $clauses;
	}

	/* ---------------------------------------------------------------------
	 * أدمن — التيرمز
	 * ------------------------------------------------------------------- */

	public static function term_add_field() {
		wp_nonce_field( 'bookapp_term_visibility', 'bookapp_term_visibility_nonce' );
		?>
		<div class="form-field">
			<label><input type="checkbox" name="bookapp_display_in_app" value="1" checked> عرض في التطبيق</label>
			<p>شيل العلامة عشان العنصر ده مايظهرش في تطبيق الموبايل. الموقع مش بيتأثر.</p>
		</div>
		<?php
	}

	public static function term_edit_field( $term ) {
		wp_nonce_field( 'bookapp_term_visibility', 'bookapp_term_visibility_nonce' );
		?>
		<tr class="form-field">
			<th scope="row">عرض في التطبيق</th>
			<td>
				<label><input type="checkbox" name="bookapp_display_in_app" value="1" <?php checked( ! self::is_term_hidden( $term->term_id ) ); ?>> ظاهر في تطبيق الموبايل</label>
				<p class="description">
					شيل العلامة عشان تخفيه من قوائم التطبيق والهوم.
					<?php if ( 'product_cat' === $term->taxonomy ) : ?>
						إخفاء تصنيف رئيسي بيخفي التصنيفات الفرعية اللي تحته تلقائيًا.
					<?php endif; ?>
					الموقع مش بيتأثر.
				</p>
			</td>
		</tr>
		<?php
	}

	public static function save_term( $term_id ) {
		if ( empty( $_POST['bookapp_term_visibility_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bookapp_term_visibility_nonce'] ) ), 'bookapp_term_visibility' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$hidden = empty( $_POST['bookapp_display_in_app'] );

		// تصنيف جديد اتضاف تحت أب مخفي → يتخفي معاه حتى لو العلامة متعلّمة.
		if ( ! $hidden ) {
			$term = get_term( (int) $term_id );
			if ( $term && ! is_wp_error( $term ) && 'product_cat' === $term->taxonomy ) {
				foreach ( get_ancestors( (int) $term_id, 'product_cat', 'taxonomy' ) as $ancestor ) {
					if ( self::is_term_hidden( $ancestor ) ) {
						$hidden = true;
						break;
					}
				}
			}
		}

		self::set_term_hidden( $term_id, $hidden );
	}

	public static function term_column_content( $content, $column, $term_id ) {
		if ( 'bookapp_app' !== $column ) {
			return $content;
		}
		return self::badge( self::is_term_hidden( $term_id ) );
	}

	/* ---------------------------------------------------------------------
	 * أدمن — المنتجات
	 * ------------------------------------------------------------------- */

	public static function product_metabox() {
		add_meta_box( 'bookapp-visibility', 'تطبيق الموبايل', array( __CLASS__, 'render_product_metabox' ), 'product', 'side', 'high' );
	}

	public static function render_product_metabox( $post ) {
		wp_nonce_field( 'bookapp_product_visibility', 'bookapp_product_visibility_nonce' );
		?>
		<label><input type="checkbox" name="bookapp_display_in_app" value="1" <?php checked( ! self::is_product_hidden( $post->ID ) ); ?>> عرض في التطبيق</label>
		<p class="description">شيل العلامة عشان الكتاب ده يختفي من تطبيق الموبايل (القوائم والبحث والهوم وصفحة المنتج). بيفضل ظاهر في الموقع وفي الطلبات القديمة.</p>
		<?php
	}

	public static function save_product( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// حفظ عادي من صفحة المنتج (metabox).
		if ( isset( $_POST['bookapp_product_visibility_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bookapp_product_visibility_nonce'] ) ), 'bookapp_product_visibility' ) ) {
				return;
			}
			self::set_product_hidden( $post_id, empty( $_POST['bookapp_display_in_app'] ) );
			return;
		}

		// Quick Edit / Bulk Edit — select ثلاثي الحالة (الووردبريس بيتحقق من nonce الـ inline/bulk قبل ما save_post يشتغل).
		if ( isset( $_REQUEST['bookapp_display_qe'] ) && '' !== $_REQUEST['bookapp_display_qe'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$val = sanitize_key( wp_unslash( $_REQUEST['bookapp_display_qe'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( in_array( $val, array( 'show', 'hide' ), true ) ) {
				self::set_product_hidden( $post_id, 'hide' === $val );
			}
		}
	}

	public static function product_column_content( $column, $post_id ) {
		if ( 'bookapp_app' !== $column ) {
			return;
		}
		echo self::badge( self::is_product_hidden( $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public static function quick_edit_field( $column_name, $post_type ) {
		if ( 'bookapp_app' !== $column_name || 'product' !== $post_type ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label>
					<span class="title">التطبيق</span>
					<select name="bookapp_display_qe">
						<option value="">— بدون تغيير —</option>
						<option value="show">ظاهر في التطبيق</option>
						<option value="hide">مخفي من التطبيق</option>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * مشترك
	 * ------------------------------------------------------------------- */

	public static function admin_column( $columns ) {
		$columns['bookapp_app'] = 'التطبيق';
		return $columns;
	}

	private static function badge( $hidden ) {
		return $hidden
			? '<span style="color:#b32d2e;font-weight:600">مخفي</span>'
			: '<span style="color:#00a32a">ظاهر</span>';
	}
}
