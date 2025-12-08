<?php
/**
 * Plugin Name: RDV Asie - Google Reviews
 * Description: Récupère et affiche les avis Google de l'agence
 * Version: 1.0.0
 * Author: Steph
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RDVASIE_REVIEWS_VERSION', '1.0.0' );
define( 'RDVASIE_REVIEWS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RDVASIE_REVIEWS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class RDVAsie_Google_Reviews {
	
	private static $instance = null;
	
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'rdvasie_reviews', array( $this, 'render_reviews_shortcode' ) );
		add_shortcode( 'rdvasie_rating', array( $this, 'render_rating_shortcode' ) );
		add_action( 'init', array( $this, 'create_reviews_page' ) );
	}
	
	/**
	 * Ajouter le menu admin
	 */
	public function add_admin_menu() {
		add_options_page(
			'Avis Google',
			'Avis Google',
			'manage_options',
			'rdvasie-reviews',
			array( $this, 'render_admin_page' )
		);
	}
	
	/**
	 * Enregistrer les paramètres
	 */
	public function register_settings() {
		register_setting( 'rdvasie_reviews_settings', 'rdvasie_reviews_place_id' );
		register_setting( 'rdvasie_reviews_settings', 'rdvasie_reviews_api_key' );
		register_setting( 'rdvasie_reviews_settings', 'rdvasie_reviews_cache_duration', array( 'default' => 3600 ) );
	}
	
	/**
	 * Page d'administration
	 */
	public function render_admin_page() {
		?>
		<div class="wrap">
			<h1>Avis Google - Configuration</h1>
			
			<!-- Encart Shortcodes -->
			<div class="notice notice-info" style="padding: 20px; margin: 20px 0; border-left: 4px solid #de5b09;">
				<h2 style="margin-top: 0;">📋 Shortcodes disponibles</h2>
				
				<h3>🌟 Afficher la note (pour header)</h3>
				<p><strong>Usage de base :</strong></p>
				<code style="background: #f0f0f0; padding: 5px 10px; display: inline-block; margin: 5px 0;">[rdvasie_rating]</code>
				<p><strong>Résultat :</strong> ★★★★★ 4,9/5</p>
				
				<p><strong>Options avancées :</strong></p>
				<ul>
					<li><code>[rdvasie_rating]</code> - Étoiles + note (défaut : ★★★★★ 4,9/5)</li>
					<li><code>[rdvasie_rating show_stars="no"]</code> - Afficher seulement "4,9/5"</li>
					<li><code>[rdvasie_rating show_score="no"]</code> - Afficher seulement les étoiles ★★★★★</li>
					<li><code>[rdvasie_rating show_count="yes"]</code> - Ajouter le nombre d'avis (★★★★★ 4,9/5 35 avis)</li>
					<li><code>[rdvasie_rating format="block"]</code> - Affichage vertical (étoiles au-dessus)</li>
				</ul>
				
				<h3>💬 Afficher la liste des avis (pour page dédiée)</h3>
				<p><strong>Usage de base :</strong></p>
				<code style="background: #f0f0f0; padding: 5px 10px; display: inline-block; margin: 5px 0;">[rdvasie_reviews]</code>
				<p><strong>Résultat :</strong> Liste complète des avis avec résumé (EXCELLENT + étoiles)</p>
				
				<p><strong>Options avancées :</strong></p>
				<ul>
					<li><code>[rdvasie_reviews limit="5"]</code> - Limiter à 5 avis (par défaut : tous)</li>
					<li><code>[rdvasie_reviews show_rating="no"]</code> - Masquer le résumé "EXCELLENT"</li>
					<li><code>[rdvasie_reviews debug="yes"]</code> - Mode debug (admin uniquement)</li>
				</ul>
				
				<p style="background: #fff3cd; padding: 10px; border-left: 3px solid #ffc107; margin-top: 15px;">
					<strong>⚠️ Note importante :</strong> L'API Google Places ne retourne que les <strong>5 derniers avis</strong>. 
					C'est une limitation de Google, pas du plugin.
				</p>
			</div>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'rdvasie_reviews_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="rdvasie_reviews_place_id">Place ID Google</label>
						</th>
						<td>
							<input type="text" id="rdvasie_reviews_place_id" name="rdvasie_reviews_place_id" 
								value="<?php echo esc_attr( get_option( 'rdvasie_reviews_place_id', '' ) ); ?>" 
								class="regular-text" />
							<p class="description">Trouvez votre Place ID sur <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Google Places API</a></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="rdvasie_reviews_api_key">Clé API Google</label>
						</th>
						<td>
							<input type="text" id="rdvasie_reviews_api_key" name="rdvasie_reviews_api_key" 
								value="<?php echo esc_attr( get_option( 'rdvasie_reviews_api_key', '' ) ); ?>" 
								class="regular-text" />
							<p class="description">Créez une clé API sur <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="rdvasie_reviews_cache_duration">Durée du cache (secondes)</label>
						</th>
						<td>
							<input type="number" id="rdvasie_reviews_cache_duration" name="rdvasie_reviews_cache_duration" 
								value="<?php echo esc_attr( get_option( 'rdvasie_reviews_cache_duration', 3600 ) ); ?>" 
								class="small-text" />
							<p class="description">Par défaut: 3600 (1 heure)</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr>
			<h2>Gestion du cache</h2>
			<button type="button" class="button" id="clear-cache-reviews">Vider le cache des avis</button>
			<p class="description">Vide le cache pour forcer une nouvelle récupération des avis depuis Google.</p>
			
			<hr>
			<h2>Test de récupération</h2>
			<button type="button" class="button" id="test-fetch-reviews">Tester la récupération des avis</button>
			<div id="test-results" style="margin-top: 20px;"></div>
			
			<hr>
			<h2>⚠️ Limitation importante</h2>
			<p><strong>L'API Google Places ne retourne que les 5 derniers avis.</strong> Pour afficher tous vos 35 avis, vous devrez utiliser Trustindex ou un autre service qui scrappe directement Google Maps.</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#clear-cache-reviews').on('click', function() {
				var btn = $(this);
				btn.prop('disabled', true).text('Vidage en cours...');
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'rdvasie_clear_cache_reviews'
					},
					success: function(response) {
						btn.prop('disabled', false).text('Vider le cache des avis');
						alert('Cache vidé ! Les avis seront récupérés à nouveau à la prochaine visite de la page.');
					}
				});
			});
			
			$('#test-fetch-reviews').on('click', function() {
				$('#test-results').html('<p>Récupération en cours...</p>');
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'rdvasie_test_fetch_reviews'
					},
					success: function(response) {
						$('#test-results').html('<pre>' + JSON.stringify(response, null, 2) + '</pre>');
					}
				});
			});
		});
		</script>
		<?php
	}
	
	/**
	 * Récupérer les avis depuis Google Places API
	 */
	public function fetch_reviews() {
		$place_id = get_option( 'rdvasie_reviews_place_id' );
		$api_key = get_option( 'rdvasie_reviews_api_key' );
		
		if ( empty( $place_id ) || empty( $api_key ) ) {
			return array(
				'error' => 'Place ID ou clé API manquant',
				'place_id' => $place_id,
				'has_api_key' => !empty($api_key)
			);
		}
		
		// Vérifier le cache
		$cache_key = 'rdvasie_reviews_' . md5( $place_id );
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}
		
		// Appel API Google Places (New API)
		$url = 'https://places.googleapis.com/v1/places/' . urlencode( $place_id ) . '?languageCode=fr';
		
		$response = wp_remote_get( $url, array(
			'timeout' => 15,
			'sslverify' => true,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Goog-Api-Key' => $api_key,
				'X-Goog-FieldMask' => 'displayName,rating,userRatingCount,reviews'
			)
		) );
		
		if ( is_wp_error( $response ) ) {
			return array(
				'error' => 'Erreur réseau: ' . $response->get_error_message(),
				'url' => $url
			);
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		// Debug : retourner toute la réponse si erreur
		if ( isset( $data['error'] ) ) {
			return array(
				'error' => 'Erreur API Google',
				'status' => isset( $data['error']['status'] ) ? $data['error']['status'] : 'UNKNOWN',
				'error_message' => isset( $data['error']['message'] ) ? $data['error']['message'] : '',
				'raw_response' => $data
			);
		}
		
		if ( ! isset( $data['reviews'] ) ) {
			return array(
				'error' => 'Pas de reviews dans la réponse',
				'raw_response' => $data
			);
		}
		
		// Convertir le format de la nouvelle API vers l'ancien format
		$reviews = array();
		foreach ( $data['reviews'] as $review ) {
			// Utiliser le texte original ou traduit en priorité
			$review_text = '';
			if ( isset( $review['originalText']['text'] ) ) {
				$review_text = $review['originalText']['text'];
			} elseif ( isset( $review['text']['text'] ) ) {
				$review_text = $review['text']['text'];
			}
			
			$reviews[] = array(
				'author_name' => isset( $review['authorAttribution']['displayName'] ) ? $review['authorAttribution']['displayName'] : 'Anonyme',
				'rating' => isset( $review['rating'] ) ? intval( $review['rating'] ) : 0,
				'text' => $review_text,
				'time' => isset( $review['publishTime'] ) ? strtotime( $review['publishTime'] ) : 0,
				'profile_photo_url' => isset( $review['authorAttribution']['photoUri'] ) ? $review['authorAttribution']['photoUri'] : '',
				'relative_time_description' => isset( $review['relativePublishTimeDescription'] ) ? $review['relativePublishTimeDescription'] : ''
			);
		}
		
		$rating = isset( $data['rating'] ) ? floatval( $data['rating'] ) : 0;
		$total = isset( $data['userRatingCount'] ) ? intval( $data['userRatingCount'] ) : 0;
		
		$result = array(
			'rating' => $rating,
			'total' => $total,
			'reviews' => $reviews
		);
		
		// Mettre en cache
		$cache_duration = get_option( 'rdvasie_reviews_cache_duration', 3600 );
		set_transient( $cache_key, $result, $cache_duration );
		
		return $result;
	}
	
	/**
	 * Shortcode pour afficher la note et le nombre d'avis (pour header)
	 */
	public function render_rating_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'show_stars' => 'yes',
			'show_count' => 'no',
			'show_score' => 'yes',
			'format' => 'inline' // inline ou block
		), $atts );
		
		$reviews_data = $this->fetch_reviews();
		
		if ( ! $reviews_data || isset( $reviews_data['error'] ) ) {
			return ''; // Ne rien afficher si erreur
		}
		
		$rating = isset( $reviews_data['rating'] ) ? floatval( $reviews_data['rating'] ) : 0;
		$total = isset( $reviews_data['total'] ) ? intval( $reviews_data['total'] ) : 0;
		
		if ( $rating == 0 && $total == 0 ) {
			return '';
		}
		
		$display_class = $atts['format'] === 'block' ? 'rdvasie-rating-block' : 'rdvasie-rating-inline';
		
		ob_start();
		?>
		<span class="rdvasie-rating-header <?php echo esc_attr( $display_class ); ?>">
			<?php if ( $atts['show_stars'] === 'yes' ) : ?>
				<span class="rdvasie-rating-stars">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<span class="star <?php echo $i <= round( $rating ) ? 'filled' : ''; ?>">★</span>
					<?php endfor; ?>
				</span>
			<?php endif; ?>
			<?php if ( $atts['show_score'] === 'yes' && $rating > 0 ) : ?>
				<span class="rdvasie-rating-score"><?php echo number_format( $rating, 1, ',', '' ); ?>/5</span>
			<?php endif; ?>
			<?php if ( $atts['show_count'] === 'yes' && $total > 0 ) : ?>
				<span class="rdvasie-rating-count"><?php echo esc_html( $total ); ?> avis</span>
			<?php endif; ?>
		</span>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Shortcode pour afficher les avis
	 */
	public function render_reviews_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'limit' => -1, // -1 = tous les avis
			'show_rating' => 'yes',
			'debug' => 'no'
		), $atts );
		
		$reviews_data = $this->fetch_reviews();
		
		// Mode debug
		if ( $atts['debug'] === 'yes' && current_user_can('manage_options') ) {
			echo '<pre style="background:#f0f0f0;padding:20px;margin:20px 0;border:1px solid #ccc;">';
			echo 'Place ID: ' . esc_html( get_option( 'rdvasie_reviews_place_id', 'Non configuré' ) ) . "\n";
			echo 'API Key: ' . ( get_option( 'rdvasie_reviews_api_key' ) ? 'Configurée (***...)' : 'Non configurée' ) . "\n";
			echo 'Cache duration: ' . esc_html( get_option( 'rdvasie_reviews_cache_duration', 3600 ) ) . " secondes\n\n";
			echo 'Résultat API:' . "\n";
			print_r( $reviews_data );
			echo '</pre>';
		}
		
		if ( ! $reviews_data || empty( $reviews_data['reviews'] ) ) {
			$error_msg = '<p>Aucun avis disponible pour le moment.</p>';
			if ( current_user_can('manage_options') ) {
				$error_msg .= '<p style="color:#d00;"><strong>Admin :</strong> Vérifiez votre configuration dans Réglages > Avis Google. Ajoutez <code>debug="yes"</code> au shortcode pour voir les détails.</p>';
			}
			return $error_msg;
		}
		
		$reviews = $reviews_data['reviews'];
		$rating = $reviews_data['rating'];
		$total = $reviews_data['total'];
		
		// Limiter le nombre d'avis si demandé
		if ( $atts['limit'] > 0 ) {
			$reviews = array_slice( $reviews, 0, intval( $atts['limit'] ) );
		}
		
		ob_start();
		?>
		<div class="rdvasie-reviews-container">
			<?php if ( $atts['show_rating'] === 'yes' ) : ?>
				<div class="rdvasie-reviews-header">
					<h2><span class="rdvasie-title-orange">Nos clients</span> <span class="rdvasie-title-dark">parlent de nous !</span></h2>
					<div class="rdvasie-reviews-summary">
						<div class="rdvasie-rating-display">
							<span class="rdvasie-rating-label">EXCELLENT</span>
							<div class="rdvasie-stars">
								<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
									<span class="star <?php echo $i <= round( $rating ) ? 'filled' : ''; ?>">★</span>
								<?php endfor; ?>
							</div>
							<p class="rdvasie-reviews-count">Basée sur <?php echo esc_html( $total ); ?> avis</p>
							<div class="rdvasie-google-logo">
								<img src="https://www.google.com/images/branding/googlelogo/1x/googlelogo_color_272x92dp.png" alt="Google" style="height: 20px;">
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
			
			<div class="rdvasie-reviews-list">
				<?php foreach ( $reviews as $review ) : 
					$author_name = isset( $review['author_name'] ) ? $review['author_name'] : 'Anonyme';
					$rating = isset( $review['rating'] ) ? intval( $review['rating'] ) : 0;
					$text = isset( $review['text'] ) ? $review['text'] : '';
					$time = isset( $review['time'] ) ? $review['time'] : 0;
					$profile_photo = isset( $review['profile_photo_url'] ) ? $review['profile_photo_url'] : '';
					$relative_time = isset( $review['relative_time_description'] ) ? $review['relative_time_description'] : '';
					
					// Convertir le timestamp en date française
					$date = $time ? date_i18n( 'd/m/Y', $time ) : '';
				?>
					<div class="rdvasie-review-card">
						<div class="rdvasie-review-header">
							<?php if ( $profile_photo ) : ?>
								<img src="<?php echo esc_url( $profile_photo ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" class="rdvasie-review-avatar">
							<?php else : ?>
								<div class="rdvasie-review-avatar-placeholder"><?php echo esc_html( substr( $author_name, 0, 1 ) ); ?></div>
							<?php endif; ?>
							<div class="rdvasie-review-author">
								<strong><?php echo esc_html( $author_name ); ?></strong>
								<span class="rdvasie-review-date"><?php echo esc_html( $date ); ?></span>
							</div>
							<div class="rdvasie-google-icon">
								<img src="https://www.google.com/favicon.ico" alt="Google" style="width: 16px; height: 16px;">
							</div>
						</div>
						<div class="rdvasie-review-stars">
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
								<span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
							<?php endfor; ?>
							<?php if ( isset( $review['translated'] ) && $review['translated'] ) : ?>
								<span class="rdvasie-verified-badge">✓</span>
							<?php endif; ?>
						</div>
						<div class="rdvasie-review-text">
							<?php echo nl2br( esc_html( $text ) ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	
	/**
	 * Enqueue scripts et styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'rdvasie-reviews-style', RDVASIE_REVIEWS_PLUGIN_URL . 'assets/style.css', array(), RDVASIE_REVIEWS_VERSION );
	}
	
	/**
	 * Créer la page "Tous les avis" si elle n'existe pas
	 */
	public function create_reviews_page() {
		if ( ! get_option( 'rdvasie_reviews_page_created' ) ) {
			$page_id = wp_insert_post( array(
				'post_title' => 'Tous nos avis clients',
				'post_content' => '[rdvasie_reviews]',
				'post_status' => 'publish',
				'post_type' => 'page',
				'post_slug' => 'tous-nos-avis-clients'
			) );
			
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( 'rdvasie_reviews_page_id', $page_id );
				update_option( 'rdvasie_reviews_page_created', true );
			}
		}
	}
}

// AJAX pour tester la récupération
add_action( 'wp_ajax_rdvasie_test_fetch_reviews', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}
	$plugin = RDVAsie_Google_Reviews::get_instance();
	$result = $plugin->fetch_reviews();
	wp_send_json_success( $result );
} );

// AJAX pour vider le cache
add_action( 'wp_ajax_rdvasie_clear_cache_reviews', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}
	$place_id = get_option( 'rdvasie_reviews_place_id' );
	$cache_key = 'rdvasie_reviews_' . md5( $place_id );
	delete_transient( $cache_key );
	wp_send_json_success( 'Cache vidé' );
} );

// Initialiser le plugin
add_action( 'plugins_loaded', function() {
	RDVAsie_Google_Reviews::get_instance();
} );

