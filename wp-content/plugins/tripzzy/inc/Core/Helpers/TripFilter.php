<?php
/**
 * Trips.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Helpers\Taxonomy;
use Tripzzy\Core\Helpers\FilterPlus;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Helpers\Cookie;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Currencies;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;

use Tripzzy\Core\Forms\Inputs\Range;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripFilter' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class TripFilter {

		/**
		 * Get All filters.
		 */
		public static function get() {
			$settings   = Settings::get();
			$defaults   = Settings::default_settings();
			$taxonomies = TaxonomyBase::get_args();

			$filters = self::taxonomy_filters();
			// add settings to show/hide filter.
			foreach ( $filters as $taxonomy => $taxonomy_args ) {

				if ( $taxonomy_args['custom'] ) { // Apply custom filter settings.
					if ( ! isset( $settings['filters']['custom'][ $taxonomy ] ) ) {
						$settings['filters']['custom'][ $taxonomy ] = $defaults['filters']['custom'][ $taxonomy ];
					}
					$show = $settings['filters']['custom'][ $taxonomy ]['show'];
				} else { // Apply default taxonomy filter settings.
					if ( ! isset( $settings['filters']['default'][ $taxonomy ] ) ) {
						$settings['filters']['default'][ $taxonomy ] = $defaults['filters']['default'][ $taxonomy ];
					}
					$show = $settings['filters']['default'][ $taxonomy ]['show'];
				}
				$filters[ $taxonomy ]['show'] = $show;
			}
			$range_filters = self::range_filters( $settings );
			foreach ( $range_filters as $range => $range_args ) {
				if ( ! isset( $settings['filters']['range'][ $range ] ) ) {
					$settings['filters']['range'][ $range ] = $defaults['filters']['range'][ $range ];
				}
				$show                            = $settings['filters']['range'][ $range ]['show'];
				$range_filters[ $range ]['show'] = $show;
			}
			// Merge/add range slider data.
			$filters = wp_parse_args( $filters, $range_filters );

			$filters = apply_filters( 'tripzzy_filter_trip_filters', $filters );
			return ArrayHelper::sort_by_priority( $filters ); // Sort array by priority.
		}

		/**
		 * Get view mode for archive page.
		 */
		public static function get_view_mode() {
			$default_view_mode = 'list';
			$default_view_mode = apply_filters( 'tripzzy_filter_default_view_mode', $default_view_mode );
			$view_mode         = $default_view_mode;
			if ( Cookie::get( 'view_mode' ) ) {
				return Cookie::get( 'view_mode' );
			}
			return $view_mode;
		}

		/**
		 * Check if it has active filter/s or not.
		 *
		 * @since 1.0.0
		 * @since 1.2.2 Fixed filter not displaying if only price filter is enabled.
		 * @return bool
		 */
		public static function has_active_filters() {
			$filters = self::get();
			$active  = false;
			foreach ( $filters as $taxonomy => $filter ) {
				if ( $filter['show'] ) {
					switch ( $filter['type'] ) {
						case 'taxonomy':
							$terms = Taxonomy::get_terms_hierarchy( $taxonomy );
							if ( count( $terms ) ) {
								$active = true;
								break;
							}
							break;
						default:
							$active = true;
							break;
					}
				}
			}
			return $active;
		}

		/**
		 * Check if it has active filter/s or not.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public static function has_filter_button() {
			$settings = Settings::get();
			return $settings['show_filter_button'];
		}



		/**
		 * Render HTML for Taxonomies.
		 *
		 * @param string $taxonomy Taxonomy name.
		 * @param array  $filter Arguments.
		 * @since 1.0.0
		 */
		public static function taxonomies_render( $taxonomy, $filter ) {
			if ( ! $filter['show'] ) {
				return;
			}
			$label = isset( $filter['label'] ) ? $filter['label'] : __( 'Category', 'tripzzy' );
			$terms = Taxonomy::get_terms_hierarchy( $taxonomy );
			if ( ! count( $terms ) ) {
				return;
			}
			?>
			<div class="tz-filter-widget <?php echo esc_attr( $taxonomy ); ?>">
				<div class="tz-filter-widget-title"><?php echo esc_html( $label ); ?></div>
				<div class="tz-filter-widget-content">
					<?php self::get_terms_markup( $terms, $taxonomy, $filter ); ?>
				</div>
			</div>

			<?php
		}

		/**
		 * Render HTML for Range Slider.
		 *
		 * @param string $name Slider name.
		 * @param array  $filter Arguments.
		 * @since 1.1.4
		 */
		public static function range_render( $name, $filter ) {
			if ( ! $filter['show'] ) {
				return;
			}
			$label       = isset( $filter['label'] ) ? $filter['label'] : __( 'Range', 'tripzzy' );
			$placeholder = isset( $filter['placeholder'] ) ? $filter['placeholder'] : __( 'Select', 'tripzzy' );

			// All Range Attributes.
			$all_attributes = self::range_filters_attributes();
			?>
			<div class="tz-filter-widget <?php echo esc_attr( $name ); ?>">
				<div class="tz-filter-widget-title h3"><?php echo esc_html( $label ); ?></div>
				<div class="tz-filter-widget-content">
					<?php
					$field = array(
						'type'          => 'range',
						'name'          => $name,
						'id'            => $name,
						'class'         => $name,
						'placeholder'   => $placeholder,
						'required'      => true,
						'priority'      => $filter['priority'] ?? 10,
						'wrapper_class' => 'sm',

						// Additional configurations.
						'attributes'    => $all_attributes[ $name ] ?? array(),
					);
					Range::render( $field );
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Render Template markup for taxonomy including hierarchy.
		 *
		 * @param array  $terms List of term.
		 * @param string $taxonomy Taxonomy name.
		 * @param array  $filter Field args.
		 * @param bool   $children Has children or not.
		 * @since 1.0.0
		 * @since 1.2.5 Added class tripzzy-multiselect for multiselect dropdown.
		 * @since 1.2.7 Added $filter args.
		 */
		public static function get_terms_markup( $terms, $taxonomy = null, $filter = array(), $children = false ) {

			$parent_count = 0;
			$placeholder  = isset( $filter['placeholder'] ) ? $filter['placeholder'] : __( 'Select', 'tripzzy' );
			if ( is_array( $terms ) && count( $terms ) > 0 ) :
				$selected_terms = self::get_requested_taxonomy_terms( $taxonomy );
				if ( ! $children ) {
					?>
					<select name="<?php echo esc_attr( $taxonomy ); ?>" id="<?php echo esc_attr( $taxonomy ); ?>" class="tripzzy-filter-dropdown tripzzy-multiselect" multiple  data-allow-multiple="true" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" style="display:none">
					<?php
				}
				foreach ( $terms as $term ) {
					// Exclure les termes parents (sans parent et avec enfants) pour la taxonomie de destination
					if ( ! $children && 'tripzzy_trip_destination' === $taxonomy ) {
						$term_parent = isset( $term->parent ) ? $term->parent : 0;
						$has_children = is_array( $term->children ) && count( $term->children ) > 0;
						if ( 0 === $term_parent && $has_children ) {
							// Afficher uniquement les enfants, pas le parent
							if ( $has_children ) {
								$_children = array();
								foreach ( $term->children as $term_child ) {
									$_children[ $term_child->term_id ] = $term_child;
								}
								call_user_func( array( __CLASS__, __FUNCTION__ ), $_children, $taxonomy, $filter, true ); // recursion if has child.
							}
							continue; // Skip le terme parent
						}
					}
					$term_class = $children ? 'child-term' : '';
					?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" class="<?php echo esc_attr( $term_class ); ?>" <?php echo esc_attr( in_array( $term->slug, $selected_terms, true ) ? 'selected' : '' ); ?> >
						<?php echo esc_attr( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
					</option>
					<?php
					if ( is_array( $term->children ) && count( $term->children ) > 0 ) {
						$_children = array();
						foreach ( $term->children as $term_child ) {
							$_children[ $term_child->term_id ] = $term_child;
						}
						call_user_func( array( __CLASS__, __FUNCTION__ ), $_children, $taxonomy, $filter, true ); // recursion if has child.
					}
				}
				if ( ! $children ) {
					?>
					</select>
					<?php
				}
			endif;
		}


		/**
		 * Callback to render Trip Filter section with all the filters.
		 *
		 * @hooked tripzzy_archive_before_content
		 */
		public static function render_trip_filters() {
			$has_filter_button = self::has_filter_button();

			if ( self::has_active_filters() ) :
				$labels = Strings::get()['labels'];
				?>
				<div><button type="button" class="tz-filter-toggle" aria-expanded="false" aria-controls="tz-filter-widget-area">
				   <i class="fa fa-thin fa-sliders"></i> <?php esc_html_e( 'Filtres', 'tripzzy' ); ?>
				</button></div>
				<div id="tz-filter-overlay" class="tz-filter-overlay"></div>
				<div id="tz-filter-widget-area" class="tz-filter-widget-area collapsed">
					<form id="tripzzy-filter-form" method="post">
						<?php \Tripzzy\Core\Http\Nonce::create_field(); ?>
						<div class="tz-filter-widget-container">
							<div class="tz-filter-header">
								<div class="tz-filter-title"><?php echo esc_html( $labels['filter_by'] ?? '' ); ?></div>
								<button type="button" class="tz-btn tz-btn-sm tz-btn-close-filter" aria-label="Fermer">
									<i class="fa fa-times"></i>
								</button>
								<div>
									<button class="tz-btn tz-btn-sm tz-btn-reset tz-btn-reset-filter" type="reset" style="display:none"><?php echo esc_html( $labels['ok'] ?? '' ); ?></button>
									<input type="hidden" name="paged" value='1' id='tripzzy-paged' /> 
									<input type="hidden" name="has_filter_button" class="tripzzy-has-filter-button" value="<?php echo esc_attr( $has_filter_button ); ?>" />
								</div>
							</div>
							<?php
							$tripzzy_filters = self::get();
							if ( is_array( $tripzzy_filters ) ) {
								foreach ( $tripzzy_filters as $tripzzy_filter_taxonomy => $tripzzy_filter ) {
									if ( $tripzzy_filter['show'] ) {
										call_user_func( $tripzzy_filter['callback'], $tripzzy_filter_taxonomy, $tripzzy_filter );
									}
								}
							}
							?>
							<?php if ( $has_filter_button ) : ?>
								<button type="submit" class="tz-btn tz-btn-solid w-full" id="tz-filter-form-submit-btn">
									<span class="tz-submit-btn-text"><?php esc_html_e( 'Afficher les résultats', 'tripzzy' ); ?></span>
									<span class="tz-submit-btn-count" style="display:none;"></span>
								</button>
								<a href="#" class="tz-filter-clear-all" id="tz-filter-clear-all" style="display:none;text-align:center;margin-top:15px;color:#666;text-decoration:underline;font-size:14px;"><?php esc_html_e( 'Réinitialiser les filtres', 'tripzzy' ); ?></a>
							<?php endif; ?>
							
							<!-- Zone des résultats en bas du panneau -->
							<div class="tz-filter-results" id="tz-filter-results" style="margin-top:20px;padding-top:20px;border-top:2px solid #de5b09;display:none;">
								<div class="tz-toolbar-title" id="tripzzy-filter-found-posts" style="margin-bottom:15px;font-weight:bold;color:#333;font-size:16px;"></div>
								<div id="tripzzy-filter-results-list" class="tripzzy-trips-list" style="max-height:400px;overflow-y:auto;padding:10px 0;">
									<!-- Les résultats s'afficheront ici -->
								</div>
							</div>
						</div>
					</form>
				</div>
				<script>
				(function(){
				  function initTripzzyFilterToggle(){
					var toggleBtn = document.querySelector('.tz-filter-toggle');
					var filterArea = document.querySelector('#tz-filter-widget-area');
					var overlay = document.querySelector('#tz-filter-overlay');
					var submitBtn = document.querySelector('#tz-filter-form-submit-btn');
					var resetBtn = document.querySelector('.tz-btn-reset-filter');
					var closeBtn = document.querySelector('.tz-btn-close-filter');
					var toolbarRight = document.querySelector('.tz-toolbar-right');
					var toggleWrapper = toggleBtn ? toggleBtn.parentNode : null; // Le div qui entoure le bouton
					var toggleParent = toggleWrapper ? toggleWrapper.parentNode : null; // Le parent du div
					var toggleNextSibling = toggleWrapper ? toggleWrapper.nextElementSibling : null;

					if (!toggleBtn || !filterArea) return;

					function openArea(){
					  if (window.innerWidth <= 768) {
					    // Mode mobile : drawer with overlay - force fixed positioning so it doesn't appear "under" the page
					    filterArea.style.display = 'block';
					    // apply fixed drawer styles
					    filterArea.style.position = 'fixed';
					    filterArea.style.top = '0';
					    filterArea.style.right = '0';
					    filterArea.style.bottom = '0';
					    filterArea.style.width = '85%';
					    filterArea.style.maxWidth = '380px';
					    filterArea.style.background = '#fff';
					    filterArea.style.zIndex = '999999';
					    filterArea.style.transform = 'translateX(100%)';
					    filterArea.style.transition = 'transform 0.3s ease';

					    setTimeout(function() {
					      filterArea.classList.remove('collapsed');
					      filterArea.classList.add('drawer-open');
					      // slide in
					      filterArea.style.transform = 'translateX(0)';
					      if (overlay) {
					        overlay.style.display = 'block';
					        overlay.style.position = 'fixed';
					        overlay.style.top = '0';
					        overlay.style.left = '0';
					        overlay.style.right = '0';
					        overlay.style.bottom = '0';
					        overlay.style.background = 'rgba(0,0,0,0.45)';
					        overlay.style.zIndex = '999998';
					        setTimeout(function() {
					          overlay.classList.add('active');
					        }, 10);
					      }
					    }, 10);
					    document.body.style.overflow = 'hidden';
					  } else {
					    // Mode desktop : affichage normal
					    filterArea.classList.remove('collapsed');
					    filterArea.style.display = 'block';
					  }
					  toggleBtn.setAttribute('aria-expanded', 'true');
					}
					function closeArea(){
					  if (window.innerWidth <= 768) {
					    // Mode mobile : fermer drawer
					    // slide out
					    filterArea.style.transform = 'translateX(100%)';
					    filterArea.classList.remove('drawer-open');
					    if (overlay) {
					      overlay.classList.remove('active');
					      setTimeout(function() {
					        overlay.style.display = 'none';
					      }, 300);
					    }
					    setTimeout(function() {
					      filterArea.classList.add('collapsed');
					      // remove fixed positioning but keep display none after transition
					      filterArea.style.display = 'none';
					      filterArea.style.position = '';
					      filterArea.style.top = '';
					      filterArea.style.right = '';
					      filterArea.style.bottom = '';
					      filterArea.style.width = '';
					      filterArea.style.maxWidth = '';
					      filterArea.style.background = '';
					      filterArea.style.zIndex = '';
					      filterArea.style.transform = '';
					      filterArea.style.transition = '';
					    }, 300);
					  } else {
					    // Mode desktop
					    filterArea.classList.add('collapsed');
					    filterArea.style.display = 'none';
					  }
					  document.body.style.overflow = '';
					  toggleBtn.setAttribute('aria-expanded', 'false');
					}

					function relocateToggle(){
					  if (!toggleBtn) {
						return;
					  }
					  var toggleWrapper = toggleBtn.parentNode; // Le div qui entoure le bouton
					  toolbarRight = document.querySelector('.tz-toolbar-right');
					  if (window.innerWidth <= 768) {
						if (toolbarRight && toggleWrapper && toggleWrapper.parentNode !== toolbarRight) {
						  toolbarRight.insertBefore(toggleWrapper, toolbarRight.firstChild || null);
						}
					  } else if (toggleParent && toggleWrapper && toggleWrapper.parentNode !== toggleParent) {
						if (toggleNextSibling) {
						  toggleParent.insertBefore(toggleWrapper, toggleNextSibling);
						} else {
						  toggleParent.appendChild(toggleWrapper);
						}
					  }
					}

					// État initial : replié sur mobile, ouvert sinon
					if (window.innerWidth <= 768) { closeArea(); } else { filterArea.style.display = ''; }
					relocateToggle();

					toggleBtn.addEventListener('click', function(){
					  if (filterArea.classList.contains('collapsed')) { openArea(); } else { closeArea(); }
					});

					window.addEventListener('resize', function(){
					  relocateToggle();
					  if (window.innerWidth > 768 && filterArea.classList.contains('collapsed')) {
						filterArea.classList.remove('collapsed');
						filterArea.style.display = '';
					  }
					});

					if (submitBtn) {
					  submitBtn.addEventListener('click', function(){
						if (window.innerWidth <= 768) { closeArea(); }
					  });
					}

					if (resetBtn) {
					  resetBtn.addEventListener('click', function(){
						if (window.innerWidth <= 768) { closeArea(); }
					  });
					}
					if (closeBtn) {
					  closeBtn.addEventListener('click', function(){
						closeArea();
					  });
					}

					if (overlay) {
					  overlay.addEventListener('click', function(){
						closeArea();
					  });
					}
					
					// Fonction pour vérifier si des filtres sont actifs
					function hasActiveFilters() {
					  var hasActive = false;
					  
					  // Vérifier les selects
					  var selects = filterArea.querySelectorAll('select.tripzzy-filter-dropdown');
					  selects.forEach(function(select) {
						if (select.multiple) {
						  var selected = Array.from(select.selectedOptions).filter(function(opt) {
							return opt.value && opt.value !== '';
						  });
						  if (selected.length > 0) {
							hasActive = true;
						  }
						} else if (select.value && select.value !== '') {
						  hasActive = true;
						}
					  });
					  
					  // Vérifier les sliders (prix et durée)
					  var priceSlider = filterArea.querySelector('[name="tripzzy_price"]');
					  var durationSlider = filterArea.querySelector('[name="tripzzy_trip_duration"]');
					  
					  if (priceSlider && priceSlider.noUiSlider) {
						var priceValues = priceSlider.noUiSlider.get();
						var priceMin = priceSlider.noUiSlider.options.range.min;
						var priceMax = priceSlider.noUiSlider.options.range.max;
						if (priceValues[0] != priceMin || priceValues[1] != priceMax) {
						  hasActive = true;
						}
					  }
					  
					  if (durationSlider && durationSlider.noUiSlider) {
						var durationValues = durationSlider.noUiSlider.get();
						var durationMin = durationSlider.noUiSlider.options.range.min;
						var durationMax = durationSlider.noUiSlider.options.range.max;
						if (durationValues[0] != durationMin || durationValues[1] != durationMax) {
						  hasActive = true;
						}
					  }
					  
					  return hasActive;
					}
					
					// Fonction pour mettre à jour la visibilité du lien de réinitialisation
					function updateClearAllButtonVisibility() {
					  var clearAllBtn = document.querySelector('#tz-filter-clear-all');
					  if (clearAllBtn) {
						if (hasActiveFilters()) {
						  clearAllBtn.style.display = 'block';
						} else {
						  clearAllBtn.style.display = 'none';
						}
					  }
					}
					
					// Gérer le bouton "Réinitialiser les filtres"
					var clearAllBtn = document.querySelector('#tz-filter-clear-all');
					if (clearAllBtn) {
					  clearAllBtn.addEventListener('click', function(e){
						e.preventDefault();
						
						// Réinitialiser tous les selects
						var selects = filterArea.querySelectorAll('select.tripzzy-filter-dropdown');
						if (typeof jQuery !== 'undefined') {
						  selects.forEach(function(select) {
							jQuery(select).val(null).trigger('change');
						  });
						} else {
						  selects.forEach(function(select) {
							if (select.multiple) {
							  Array.from(select.options).forEach(function(option) {
								option.selected = false;
							  });
							} else {
							  select.selectedIndex = 0;
							}
							select.dispatchEvent(new Event('change'));
						  });
						}
						
						// Réinitialiser les sliders de prix et durée
						var priceSlider = filterArea.querySelector('[name="tripzzy_price"]');
						var durationSlider = filterArea.querySelector('[name="tripzzy_trip_duration"]');
						if (priceSlider && priceSlider.noUiSlider) {
						  var priceMin = priceSlider.noUiSlider.options.range.min;
						  var priceMax = priceSlider.noUiSlider.options.range.max;
						  priceSlider.noUiSlider.set([priceMin, priceMax]);
						}
						if (durationSlider && durationSlider.noUiSlider) {
						  var durationMin = durationSlider.noUiSlider.options.range.min;
						  var durationMax = durationSlider.noUiSlider.options.range.max;
						  durationSlider.noUiSlider.set([durationMin, durationMax]);
						}
						
						// Masquer la zone de résultats
						var resultsZone = document.querySelector('#tz-filter-results');
						if (resultsZone) {
						  resultsZone.style.display = 'none';
						}
						
						// Réinitialiser le texte du bouton
						var submitBtnText = document.querySelector('#tz-filter-form-submit-btn .tz-submit-btn-text');
						if (submitBtnText) {
						  submitBtnText.textContent = '<?php esc_html_e( 'Afficher les résultats', 'tripzzy' ); ?>';
						}
						
						// Masquer le lien de réinitialisation
						updateClearAllButtonVisibility();
						
						// Réinitialiser aussi les résultats principaux
						var mainResults = document.querySelector('#tripzzy-trip-listings');
						if (mainResults) {
						  mainResults.innerHTML = '';
						}
						
						// Réinitialiser le compteur dans la toolbar
						var toolbarCounter = document.querySelector('.tz-toolbar-left .tz-toolbar-title');
						if (toolbarCounter) {
						  toolbarCounter.textContent = '';
						}
						
						// Recharger la page pour réinitialiser complètement
						window.location.href = window.location.pathname;
					  });
					}
					
					// Fonction pour mettre à jour le texte du bouton avec le nombre de résultats
					var updateButtonTimeout;
					function updateButtonWithCount() {
					  // Annuler le timeout précédent (debounce)
					  if (updateButtonTimeout) {
						clearTimeout(updateButtonTimeout);
					  }
					  
					  // Attendre 300ms après le dernier changement avant de faire la requête
					  updateButtonTimeout = setTimeout(function() {
						if (!hasActiveFilters()) {
						  // Pas de filtres actifs, remettre le texte par défaut
						  var submitBtnText = document.querySelector('#tz-filter-form-submit-btn .tz-submit-btn-text');
						  if (submitBtnText) {
							submitBtnText.textContent = '<?php esc_html_e( 'Afficher les résultats', 'tripzzy' ); ?>';
						  }
						  return;
						}
						
						// Récupérer les données du formulaire
						var form = document.querySelector('#tripzzy-filter-form');
						if (!form || typeof jQuery === 'undefined') {
						  return;
						}
						
						var data = {};
						
						// Récupérer manuellement les valeurs des selects multiselect
						var selects = form.querySelectorAll('select.tripzzy-filter-dropdown');
						selects.forEach(function(select) {
						  var selectName = select.name || select.id;
						  if (selectName) {
							// Essayer d'abord avec jQuery.val() (fonctionne avec les plugins multiselect)
							var values = jQuery(select).val();
							
							// Si ça ne fonctionne pas, récupérer directement depuis les options sélectionnées
							if (!values || (Array.isArray(values) && values.length === 0)) {
							  values = [];
							  var options = select.querySelectorAll('option:checked');
							  options.forEach(function(option) {
								if (option.value && option.value !== '') {
								  values.push(option.value);
								}
							  });
							}
							
							// Filtrer les valeurs vides et ajouter aux données
							if (values) {
							  if (Array.isArray(values)) {
								values = values.filter(function(v) { return v && v !== ''; });
								if (values.length > 0) {
								  data[selectName] = values;
								}
							  } else if (values !== '') {
								data[selectName] = [values];
							  }
							}
						  }
						});
						
						// Récupérer les autres champs du formulaire (champs cachés, etc.) avec serializeArray
						var formData = jQuery(form).serializeArray();
						formData.forEach(function(item) {
						  // Ignorer les selects car déjà traités ci-dessus
						  var isSelect = false;
						  selects.forEach(function(select) {
							if ((select.name || select.id) === item.name) {
							  isSelect = true;
							}
						  });
						  
						  if (!isSelect) {
							if (data[item.name]) {
							  // Si la clé existe déjà, convertir en tableau
							  if (!Array.isArray(data[item.name])) {
								data[item.name] = [data[item.name]];
							  }
							  data[item.name].push(item.value);
							} else {
							  data[item.name] = item.value;
							}
						  }
						});
						
						// Récupérer les valeurs des sliders et les ajouter
						var priceSlider = filterArea.querySelector('[name="tripzzy_price"]');
						var durationSlider = filterArea.querySelector('[name="tripzzy_trip_duration"]');
						
						if (priceSlider && priceSlider.noUiSlider) {
						  var priceValues = priceSlider.noUiSlider.get();
						  var priceMin = priceSlider.noUiSlider.options.range.min;
						  var priceMax = priceSlider.noUiSlider.options.range.max;
						  if (priceValues[0] != priceMin || priceValues[1] != priceMax) {
							data['tripzzy_price[]'] = priceValues;
							data.tripzzy_price_changed = true;
						  }
						}
						
						if (durationSlider && durationSlider.noUiSlider) {
						  var durationValues = durationSlider.noUiSlider.get();
						  var durationMin = durationSlider.noUiSlider.options.range.min;
						  var durationMax = durationSlider.noUiSlider.options.range.max;
						  if (durationValues[0] != durationMin || durationValues[1] != durationMax) {
							data['tripzzy_trip_duration[]'] = durationValues;
							data.tripzzy_trip_duration_changed = true;
						  }
						}
						
						// Ajouter les paramètres nécessaires pour la requête
						data.action = 'tripzzy_get_trips';
						data.loadDataFromFilters = true;
						data.paged = 1;
						data.is_trips = true;
						data.is_taxonomy = false;
						
						// Debug: afficher les données envoyées (à retirer en production)
						console.log('Données brutes:', data);
						
						// Préparer les données pour l'envoi AJAX
						// Les taxonomies doivent être envoyées comme tableaux sans []
						// Les sliders doivent être envoyés avec []
						var ajaxData = {};
						
						for (var key in data) {
						  if (data.hasOwnProperty(key)) {
							// Les sliders ont déjà le format correct avec []
							if (key.indexOf('[]') !== -1) {
							  ajaxData[key] = data[key];
							} else if (Array.isArray(data[key])) {
							  // Les taxonomies : envoyer comme tableau directement
							  ajaxData[key] = data[key];
							} else {
							  ajaxData[key] = data[key];
							}
						  }
						}
						
						console.log('Données formatées pour AJAX:', ajaxData);
						
						// Le backend attend les données en JSON dans le body (INPUT_PAYLOAD)
						// Récupérer le nonce depuis le formulaire
						var nonceInput = form.querySelector('input[name="tripzzy_nonce"]');
						var nonceValue = '';
						if (nonceInput) {
						  nonceValue = nonceInput.value;
						  // Ajouter le nonce dans les données JSON aussi (au cas où)
						  ajaxData.tripzzy_nonce = nonceValue;
						}
						
						// Préparer l'URL avec les paramètres de base (action et nonce pour WordPress)
						// Le nonce dans l'URL permet à WordPress de le vérifier si nécessaire
						var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
						var urlWithParams = ajaxUrl + '?action=tripzzy_get_trips';
						if (nonceValue) {
						  urlWithParams += '&tripzzy_nonce=' + encodeURIComponent(nonceValue);
						}
						
						// Faire la requête AJAX en JSON avec fetch (comme le code original)
						if (typeof fetch !== 'undefined') {
						  fetch(urlWithParams, {
							method: 'POST',
							headers: {
							  'Content-Type': 'application/json; charset=utf-8'
							},
							body: JSON.stringify(ajaxData)
						  })
						  .then(function(response) {
							if (!response.ok) {
							  throw new Error('HTTP error! status: ' + response.status);
							}
							return response.json();
						  })
						  .then(function(data) {
							console.log('Réponse AJAX:', data);
							if (data && data.success && data.data) {
							  var count = data.data.found_posts || 0;
							  var submitBtnText = document.querySelector('#tz-filter-form-submit-btn .tz-submit-btn-text');
							  
							  console.log('Nombre de voyages trouvés:', count);
							  
							  if (submitBtnText) {
								if (count > 0) {
								  if (count === 1) {
									submitBtnText.textContent = '<?php esc_html_e( 'Afficher le voyage', 'tripzzy' ); ?>';
								  } else {
									submitBtnText.textContent = '<?php echo esc_js( __( 'Afficher les', 'tripzzy' ) ); ?> ' + count + ' <?php echo esc_js( __( 'voyages', 'tripzzy' ) ); ?>';
								  }
								} else {
								  submitBtnText.textContent = '<?php esc_html_e( 'Aucun résultat', 'tripzzy' ); ?>';
								}
							  }
							} else {
							  console.error('Réponse invalide:', data);
							}
						  })
						  .catch(function(error) {
							console.error('Erreur AJAX:', error);
							// En cas d'erreur, garder le texte par défaut
							var submitBtnText = document.querySelector('#tz-filter-form-submit-btn .tz-submit-btn-text');
							if (submitBtnText) {
							  submitBtnText.textContent = '<?php esc_html_e( 'Afficher les résultats', 'tripzzy' ); ?>';
							}
						  });
						}
					  }, 300);
					}
					
					// Écouter les changements sur les filtres pour mettre à jour la visibilité et le bouton
					if (typeof jQuery !== 'undefined') {
					  // Écouter les changements sur les selects
					  jQuery(document).on('change', '#tz-filter-widget-area select.tripzzy-filter-dropdown', function() {
						setTimeout(updateClearAllButtonVisibility, 100);
						updateButtonWithCount();
					  });
					  
					  // Écouter les changements sur les sliders (après leur initialisation)
					  setTimeout(function() {
						var priceSlider = filterArea.querySelector('[name="tripzzy_price"]');
						var durationSlider = filterArea.querySelector('[name="tripzzy_trip_duration"]');
						
						if (priceSlider && priceSlider.noUiSlider) {
						  priceSlider.noUiSlider.on('update', function() {
							updateClearAllButtonVisibility();
							updateButtonWithCount();
						  });
						}
						
						if (durationSlider && durationSlider.noUiSlider) {
						  durationSlider.noUiSlider.on('update', function() {
							updateClearAllButtonVisibility();
							updateButtonWithCount();
						  });
						}
					  }, 1000);
					}
					
					// Vérifier l'état initial des filtres
					setTimeout(function() {
					  updateClearAllButtonVisibility();
					  updateButtonWithCount();
					}, 500);
					
					// Écouter les réponses AJAX pour mettre à jour les résultats dans le panneau
					if (typeof jQuery !== 'undefined') {
					  jQuery(document).on('tripzzy_filter_results_updated', function(e, data) {
						var resultsZone = document.querySelector('#tz-filter-results');
						var resultsList = document.querySelector('#tripzzy-filter-results-list');
						var foundPostsEl = document.querySelector('#tripzzy-filter-found-posts');
						
						if (resultsZone && data && data.found_posts_html) {
						  // Afficher la zone de résultats
						  resultsZone.style.display = 'block';
						  
						  // Mettre à jour le compteur
						  if (foundPostsEl) {
							foundPostsEl.textContent = data.found_posts_html;
						  }
						  
						  // Mettre à jour la liste des résultats (premiers résultats seulement)
						  if (resultsList && data.trips) {
							resultsList.innerHTML = data.trips;
							// Scroll vers le haut de la zone de résultats
							resultsList.scrollTop = 0;
						  }
						}
					  });
					}
				  }

				  if (document.readyState === 'loading') {
					document.addEventListener('DOMContentLoaded', initTripzzyFilterToggle);
				  } else {
					initTripzzyFilterToggle();
				  }
				})();
				</script>
				<script>
				// Hook pour intercepter les réponses AJAX et mettre à jour les résultats dans le panneau
				(function() {
				  if (typeof jQuery === 'undefined') return;
				  
				  // Intercepter les appels AJAX réussis
				  jQuery(document).ajaxSuccess(function(event, xhr, settings) {
					if (settings.url && settings.url.indexOf('tripzzy_render_trips') !== -1) {
					  try {
						var response = JSON.parse(xhr.responseText);
						if (response.success && response.data) {
						  // Déclencher l'événement personnalisé
						  jQuery(document).trigger('tripzzy_filter_results_updated', [response.data]);
						}
					  } catch(e) {
						console.error('Erreur parsing AJAX response:', e);
					  }
					}
				  });
				})();
				</script>
				<?php
			endif;
		}

		/**
		 * All Range filters to add it in settings and filters as well.
		 *
		 * @since 1.3.0 Added range filters for trip duration.
		 * @since 1.1.4
		 */
		public static function range_filters() {
			$filters = array(
				'tripzzy_price'         => array(
					'label'       => __( 'Budget', 'tripzzy' ),
					'placeholder' => __( 'Select', 'tripzzy' ),
					'callback'    => array( __CLASS__, 'range_render' ),
					'custom'      => false, // Always false for range.
					'type'        => 'range',
					'priority'    => 30,
				),
				'tripzzy_trip_duration' => array(
					'label'       => __( 'Duration', 'tripzzy' ),
					'placeholder' => __( 'Select', 'tripzzy' ),
					'callback'    => array( __CLASS__, 'range_render' ),
					'custom'      => false, // Always false for range.
					'type'        => 'range',
					'priority'    => 20,
				),
			);
			return apply_filters( 'tripzzy_filter_range_filters', $filters );
		}

		/**
		 * All Range filters to add it in settings and filters as well.
		 *
		 * @since 1.3.0 Added range filters for trip duration.
		 * @since 1.1.4
		 */
		private static function range_filters_attributes() {
			$settings  = Settings::get();
			$min_price = MetaHelpers::get_option( 'min_price', 0 );
			$max_price = MetaHelpers::get_option( 'max_price', 20000 );

			$min_duration   = 0;
			$max_duration   = MetaHelpers::get_option( 'max_duration', 30 );
			$duration_label = __( 'Days', 'tripzzy' );
			if ( 'hours' === $settings['filter_duration_in'] ) {
				$max_duration   = 24; // 24 hours in a day.
				$duration_label = __( 'Hours', 'tripzzy' );

			}

			// Request Data.
			$tripzzy_price = get_query_var( 'tripzzy_price' );
			if ( ! $tripzzy_price ) {
				$tripzzy_price = array();
			}
			$tripzzy_price_changed = get_query_var( 'tripzzy_price_changed' );

			$tripzzy_trip_duration = get_query_var( 'tripzzy_trip_duration' );
			if ( ! $tripzzy_trip_duration ) {
				$tripzzy_trip_duration = array();
			}
			$tripzzy_trip_duration_changed = get_query_var( 'tripzzy_trip_duration_changed' );
			$attributes                    = array(
				'tripzzy_price'         => array(
					'min'                   => $min_price,
					'max'                   => $max_price,
					'step'                  => 1,
					'round'                 => 2,
					'generate-labels-units' => Currencies::get_symbol(),
					'unit_position'         => 'left',
					'value1'                => $tripzzy_price[0] ?? $min_price,
					'value2'                => $tripzzy_price[1] ?? $max_price,
					'changed'               => $tripzzy_price_changed ?? false,
				),
				'tripzzy_trip_duration' => array(
					'min'                   => $min_duration,
					'max'                   => $max_duration,
					'step'                  => 1,
					'round'                 => 2,
					'generate-labels-units' => $duration_label,
					'unit_position'         => 'right_with_space',
					'value1'                => $tripzzy_trip_duration[0] ?? $min_duration,
					'value2'                => $tripzzy_trip_duration[1] ?? $max_duration,
					'changed'               => $tripzzy_trip_duration_changed ?? false,
					'description'           => __( 'Display Duration in the search filter section of archive page or Trip search form.', 'tripzzy' ),
				),
			);
			return $attributes;
		}

	/**
	 * All Taxonomy filters to add it in settings and filters as well.
	 *
	 * @since 1.0.0
	 */
	public static function taxonomy_filters() {
		$taxonomies = TaxonomyBase::get_args();

		$filters  = array();
		$priority = 100;
		
		// Traiter d'abord la taxonomie destination avec une priorité basse
		$destination_taxonomy = 'tripzzy_trip_destination';
		if ( isset( $taxonomies[ $destination_taxonomy ] ) && ! in_array( $destination_taxonomy, self::skipped_taxonomies(), true ) ) {
			$filters[ $destination_taxonomy ] = array(
				'label'       => $taxonomies[ $destination_taxonomy ]['labels']['name'],
				'placeholder' => __( 'Select', 'tripzzy' ),
				'callback'    => array( __CLASS__, 'taxonomies_render' ),
				'custom'      => false, // whether custom filters or not.
				'type'        => 'taxonomy', // To make all taxonomy filter as query args automatically.
				'priority'    => 10, // Priorité basse pour apparaître en premier
			);
		}
		
		// Traiter ensuite la taxonomie type de voyage avec une priorité de 15
		$trip_type_taxonomy = 'tripzzy_trip_type';
		if ( isset( $taxonomies[ $trip_type_taxonomy ] ) && ! in_array( $trip_type_taxonomy, self::skipped_taxonomies(), true ) ) {
			$filters[ $trip_type_taxonomy ] = array(
				'label'       => $taxonomies[ $trip_type_taxonomy ]['labels']['name'],
				'placeholder' => __( 'Select', 'tripzzy' ),
				'callback'    => array( __CLASS__, 'taxonomies_render' ),
				'custom'      => false, // whether custom filters or not.
				'type'        => 'taxonomy', // To make all taxonomy filter as query args automatically.
				'priority'    => 15, // Priorité pour apparaître en deuxième position (après destination 10, avant durée 20)
			);
		}
		
		// Traiter les autres taxonomies
		foreach ( $taxonomies as $taxonomy => $taxonomy_args ) {
			if ( in_array( $taxonomy, self::skipped_taxonomies(), true ) ) {
				continue;
			}
			// Ignorer la destination et le type de voyage car déjà traités
			if ( $taxonomy === $destination_taxonomy || $taxonomy === $trip_type_taxonomy ) {
				continue;
			}
			$filters[ $taxonomy ] = array(
				'label'       => $taxonomy_args['labels']['name'],
				'placeholder' => __( 'Select', 'tripzzy' ),
				'callback'    => array( __CLASS__, 'taxonomies_render' ),
				'custom'      => false, // whether custom filters or not.
				'type'        => 'taxonomy', // To make all taxonomy filter as query args automatically.
				'priority'    => $priority,
			);
			$priority            += 10;
		}

		$custom_taxonomies = FilterPlus::get();
		if ( is_array( $custom_taxonomies ) && count( $custom_taxonomies ) > 0 ) {
			foreach ( $custom_taxonomies as $slug => $custom_taxonomy ) {
				$filters[ $slug ] = array(
					'label'    => $custom_taxonomy['label'],
					'callback' => array( __CLASS__, 'taxonomies_render' ),
					'custom'   => true, // whether custom filters or not.
					'type'     => 'taxonomy', // Just for data format consistency. because all custom filters are taxonomy itself.
					'priority' => $priority,
				);
				$priority        += 10;
			}
		}
		return apply_filters( 'tripzzy_filter_taxonomy_filters', $filters );
	}

		/**
		 * Default Settings key for filters.
		 *
		 * @param array $default_settings Default settings keys for filters.
		 * @since 1.0.0
		 * @since 1.1.4 Added Range filter keys in default settings keys.
		 */
		public static function default_settings_keys( $default_settings ) {
			$filters          = array();
			$taxonomy_filters = self::taxonomy_filters();
			$range_filters    = self::range_filters();
			foreach ( $range_filters as $name => $range_args ) {
				$filter                    = array(
					'show'  => true,
					'label' => $range_args['label'],
				);
				$filters['range'][ $name ] = $filter;
			}

			foreach ( $taxonomy_filters as $taxonomy => $taxonomy_args ) {
				if ( in_array( $taxonomy, self::skipped_taxonomies(), true ) ) {
					continue;
				}
				$filter = array(
					'show'  => true,
					'label' => $taxonomy_args['label'],
				);
				if ( isset( $taxonomy_args['custom'] ) && $taxonomy_args['custom'] ) {
					$filters['custom'][ $taxonomy ] = $filter;
				} else {
					$filters['default'][ $taxonomy ] = $filter;
				}
			}

			$default_settings['filters'] = $filters;
			return $default_settings;
		}

		/**
		 * Taxonomy which need to remove from filters.
		 *
		 * @since 1.0.0
		 * @since 1.2.8 Added filter hook to skip taxonomies.
		 * @return array
		 */
		public static function skipped_taxonomies() {
			$taxonomies = array(
				'tripzzy_trip_includes',
				'tripzzy_trip_excludes',
				'tripzzy_price_category',
			);
			return apply_filters( 'tripzzy_filter_skipped_taxonomies', $taxonomies );
		}

		/**
		 * Alternative way to whole get request method to get terms.
		 *
		 * @param string $taxonomy Taxonomoy name.
		 * @return array
		 */
		public static function get_requested_taxonomy_terms( $taxonomy = '' ) {
			if ( ! $taxonomy ) {
				return array();
			}

			if ( ! Nonce::verify() ) {
				return array();
			}
			// Nonce already verified using Nonce::verify method.
			$terms = isset( $_GET[ $taxonomy ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET[ $taxonomy ] ) ) : array(); // @codingStandardsIgnoreLine
			return $terms;
		}
	}
}
