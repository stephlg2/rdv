<?php
/**
 * Dashboard Admin - RDV Sitemap Pro
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap rdv-sitemap-wrap">
    <div class="rdv-sitemap-header">
        <h1>
            <span class="dashicons dashicons-networking"></span>
            RDV Sitemap Pro
        </h1>
        <p class="description">Sitemap XML/HTML optimisé pour les voyages + compatible LLM</p>
    </div>

    <!-- Stats Cards (Cliquables) -->
    <div class="rdv-sitemap-stats">
        <div class="stat-card total">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo number_format($stats['total_urls']); ?></span>
                <span class="stat-label">URLs indexées</span>
            </div>
        </div>
        
        <div class="stat-card voyages clickable" data-type="tripzzy" data-label="Voyages">
            <div class="stat-icon">
                <span class="dashicons dashicons-airplane"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo $stats['voyages']; ?></span>
                <span class="stat-label">Voyages</span>
            </div>
            <span class="click-hint">Cliquez pour gérer</span>
        </div>
        
        <div class="stat-card destinations clickable" data-type="tripzzy_trip_destination" data-taxonomy="1" data-label="Destinations">
            <div class="stat-icon">
                <span class="dashicons dashicons-location"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo $stats['destinations']; ?></span>
                <span class="stat-label">Destinations</span>
            </div>
            <span class="click-hint">Cliquez pour gérer</span>
        </div>
        
        <div class="stat-card faqs clickable" data-type="avada_faq" data-label="FAQ">
            <div class="stat-icon">
                <span class="dashicons dashicons-editor-help"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo $stats['faqs']; ?></span>
                <span class="stat-label">FAQ</span>
            </div>
            <span class="click-hint">Cliquez pour gérer</span>
        </div>
        
        <div class="stat-card articles clickable" data-type="post" data-label="Articles">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo $stats['articles']; ?></span>
                <span class="stat-label">Articles</span>
            </div>
            <span class="click-hint">Cliquez pour gérer</span>
        </div>
        
        <div class="stat-card pages clickable" data-type="page" data-label="Pages">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-page"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo $stats['pages']; ?></span>
                <span class="stat-label">Pages</span>
            </div>
            <span class="click-hint">Cliquez pour gérer</span>
        </div>
        
        <div class="stat-card images">
            <div class="stat-icon">
                <span class="dashicons dashicons-format-image"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo number_format($stats['images']); ?></span>
                <span class="stat-label">Images</span>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="rdv-sitemap-grid">
        <!-- Liens Sitemaps -->
        <div class="rdv-sitemap-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-admin-links"></span> Vos Sitemaps</h2>
            </div>
            <div class="card-body">
                <table class="sitemap-links-table">
                    <tbody>
                        <tr>
                            <td><strong>Sitemap XML (Index)</strong></td>
                            <td>
                                <a href="<?php echo home_url('/sitemap.xml'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/sitemap.xml'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge active">Actif</span></td>
                        </tr>
                        <tr>
                            <td><strong>Sitemap Voyages</strong></td>
                            <td>
                                <a href="<?php echo home_url('/sitemap-tripzzy.xml'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/sitemap-tripzzy.xml'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge active"><?php echo $stats['voyages']; ?> URLs</span></td>
                        </tr>
                        <tr>
                            <td><strong>Sitemap Destinations</strong></td>
                            <td>
                                <a href="<?php echo home_url('/sitemap-tax-tripzzy_trip_destination.xml'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/sitemap-tax-tripzzy_trip_destination.xml'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge active"><?php echo $stats['destinations']; ?> URLs</span></td>
                        </tr>
                        <tr>
                            <td><strong>Sitemap FAQ</strong></td>
                            <td>
                                <a href="<?php echo home_url('/sitemap-avada_faq.xml'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/sitemap-avada_faq.xml'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge active"><?php echo $stats['faqs']; ?> URLs</span></td>
                        </tr>
                        <tr>
                            <td><strong>Sitemap Articles</strong></td>
                            <td>
                                <a href="<?php echo home_url('/sitemap-post.xml'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/sitemap-post.xml'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge active"><?php echo $stats['articles']; ?> URLs</span></td>
                        </tr>
                        <tr>
                            <td><strong>Sitemap Pages</strong></td>
                            <td>
                                <a href="<?php echo home_url('/sitemap-page.xml'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/sitemap-page.xml'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge active"><?php echo $stats['pages']; ?> URLs</span></td>
                        </tr>
                        <tr>
                            <td><strong>Sitemap Images</strong></td>
                            <td>
                                <a href="<?php echo home_url('/sitemap-images.xml'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/sitemap-images.xml'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge active"><?php echo number_format($stats['images']); ?> images</span></td>
                        </tr>
                        <tr class="highlight-row">
                            <td><strong>🤖 llms.txt (IA)</strong></td>
                            <td>
                                <a href="<?php echo home_url('/llms.txt'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/llms.txt'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge llm">Pour ChatGPT, Perplexity...</span></td>
                        </tr>
                        <tr class="highlight-row">
                            <td><strong>📄 Plan du site (HTML)</strong></td>
                            <td>
                                <a href="<?php echo home_url('/plan-du-site/'); ?>" target="_blank" class="sitemap-link">
                                    <?php echo home_url('/plan-du-site/'); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td><span class="status-badge html">Pour visiteurs</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="rdv-sitemap-card">
            <div class="card-header">
                <h2><span class="dashicons dashicons-update"></span> Actions</h2>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <button type="button" id="regenerate-sitemap" class="button button-primary button-large">
                        <span class="dashicons dashicons-update"></span>
                        Régénérer le sitemap
                    </button>
                    
                    <button type="button" id="ping-search-engines" class="button button-secondary button-large">
                        <span class="dashicons dashicons-megaphone"></span>
                        Notifier Google & Bing
                    </button>
                </div>
                
                <div id="action-result" class="action-result" style="display:none;"></div>
                
                <div class="cron-info">
                    <h4>🔄 Régénération automatique</h4>
                    <?php
                    $next_cron = wp_next_scheduled('rdv_sitemap_weekly_cron');
                    $last_regenerate = get_option('rdv_sitemap_last_regenerate');
                    ?>
                    <p>
                        <strong>Prochaine :</strong> 
                        <?php echo $next_cron ? date_i18n('d/m/Y à H:i', $next_cron) : 'Non programmée'; ?>
                    </p>
                    <?php if ($last_regenerate) : ?>
                    <p>
                        <strong>Dernière :</strong> 
                        <?php echo date_i18n('d/m/Y à H:i', strtotime($last_regenerate)); ?>
                    </p>
                    <?php endif; ?>
                    <p class="description" style="margin-top:10px;font-size:12px;color:#666;">
                        Le sitemap est régénéré automatiquement chaque semaine + à chaque nouvelle publication.
                    </p>
                </div>
                
                <div class="robots-info">
                    <h4>📋 À ajouter dans robots.txt :</h4>
                    <pre class="robots-code">Sitemap: <?php echo home_url('/sitemap.xml'); ?></pre>
                    <button type="button" class="button copy-robots" data-copy="Sitemap: <?php echo home_url('/sitemap.xml'); ?>">
                        <span class="dashicons dashicons-admin-page"></span> Copier
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Réglages -->
    <div class="rdv-sitemap-card settings-card">
        <div class="card-header">
            <h2><span class="dashicons dashicons-admin-settings"></span> Réglages</h2>
        </div>
        <div class="card-body">
            <form id="sitemap-settings-form">
                <div class="settings-grid">
                    <!-- Options générales -->
                    <div class="settings-section">
                        <h3>Options générales</h3>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="enable_xml" value="1" <?php checked($this->settings['enable_xml']); ?>>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Activer le Sitemap XML</span>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="enable_html" value="1" <?php checked($this->settings['enable_html']); ?>>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Activer le Sitemap HTML</span>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="enable_llms_txt" value="1" <?php checked($this->settings['enable_llms_txt']); ?>>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Activer llms.txt (LLM/IA)</span>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="enable_images" value="1" <?php checked($this->settings['enable_images']); ?>>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Inclure les images</span>
                        </label>
                        
                        <label class="toggle-option">
                            <input type="checkbox" name="auto_ping" value="1" <?php checked($this->settings['auto_ping']); ?>>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Ping auto Google/Bing à chaque publication</span>
                        </label>
                    </div>
                    
                    <!-- Types de contenu -->
                    <div class="settings-section">
                        <h3>Types de contenu</h3>
                        
                        <table class="content-types-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Actif</th>
                                    <th>Priorité</th>
                                    <th>Fréquence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Voyages</strong></td>
                                    <td>
                                        <input type="checkbox" name="post_types[tripzzy][enabled]" value="1" 
                                            <?php checked($this->settings['post_types']['tripzzy']['enabled'] ?? true); ?>>
                                    </td>
                                    <td>
                                        <select name="post_types[tripzzy][priority]">
                                            <?php $this->render_priority_options($this->settings['post_types']['tripzzy']['priority'] ?? '0.9'); ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="post_types[tripzzy][changefreq]">
                                            <?php $this->render_changefreq_options($this->settings['post_types']['tripzzy']['changefreq'] ?? 'weekly'); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>FAQ</strong></td>
                                    <td>
                                        <input type="checkbox" name="post_types[avada_faq][enabled]" value="1" 
                                            <?php checked($this->settings['post_types']['avada_faq']['enabled'] ?? true); ?>>
                                    </td>
                                    <td>
                                        <select name="post_types[avada_faq][priority]">
                                            <?php $this->render_priority_options($this->settings['post_types']['avada_faq']['priority'] ?? '0.8'); ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="post_types[avada_faq][changefreq]">
                                            <?php $this->render_changefreq_options($this->settings['post_types']['avada_faq']['changefreq'] ?? 'monthly'); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Articles</strong></td>
                                    <td>
                                        <input type="checkbox" name="post_types[post][enabled]" value="1"
                                            <?php checked($this->settings['post_types']['post']['enabled'] ?? true); ?>>
                                    </td>
                                    <td>
                                        <select name="post_types[post][priority]">
                                            <?php $this->render_priority_options($this->settings['post_types']['post']['priority'] ?? '0.7'); ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="post_types[post][changefreq]">
                                            <?php $this->render_changefreq_options($this->settings['post_types']['post']['changefreq'] ?? 'weekly'); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Pages</strong></td>
                                    <td>
                                        <input type="checkbox" name="post_types[page][enabled]" value="1"
                                            <?php checked($this->settings['post_types']['page']['enabled'] ?? true); ?>>
                                    </td>
                                    <td>
                                        <select name="post_types[page][priority]">
                                            <?php $this->render_priority_options($this->settings['post_types']['page']['priority'] ?? '0.8'); ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="post_types[page][changefreq]">
                                            <?php $this->render_changefreq_options($this->settings['post_types']['page']['changefreq'] ?? 'monthly'); ?>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Taxonomies -->
                    <div class="settings-section">
                        <h3>Taxonomies</h3>
                        
                        <table class="content-types-table">
                            <thead>
                                <tr>
                                    <th>Taxonomie</th>
                                    <th>Actif</th>
                                    <th>Priorité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Destinations</strong></td>
                                    <td>
                                        <input type="checkbox" name="taxonomies[tripzzy_trip_destination][enabled]" value="1"
                                            <?php checked($this->settings['taxonomies']['tripzzy_trip_destination']['enabled'] ?? true); ?>>
                                    </td>
                                    <td>
                                        <select name="taxonomies[tripzzy_trip_destination][priority]">
                                            <?php $this->render_priority_options($this->settings['taxonomies']['tripzzy_trip_destination']['priority'] ?? '0.8'); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Types de voyage</strong></td>
                                    <td>
                                        <input type="checkbox" name="taxonomies[tripzzy_trip_type][enabled]" value="1"
                                            <?php checked($this->settings['taxonomies']['tripzzy_trip_type']['enabled'] ?? true); ?>>
                                    </td>
                                    <td>
                                        <select name="taxonomies[tripzzy_trip_type][priority]">
                                            <?php $this->render_priority_options($this->settings['taxonomies']['tripzzy_trip_type']['priority'] ?? '0.7'); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Catégories</strong></td>
                                    <td>
                                        <input type="checkbox" name="taxonomies[category][enabled]" value="1"
                                            <?php checked($this->settings['taxonomies']['category']['enabled'] ?? true); ?>>
                                    </td>
                                    <td>
                                        <select name="taxonomies[category][priority]">
                                            <?php $this->render_priority_options($this->settings['taxonomies']['category']['priority'] ?? '0.6'); ?>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Exclusions -->
                    <div class="settings-section">
                        <h3>Exclusions</h3>
                        
                        <div class="form-group">
                            <label for="excluded_ids">IDs à exclure (séparés par des virgules) :</label>
                            <input type="text" id="excluded_ids" name="excluded_ids" 
                                value="<?php echo implode(', ', $this->settings['excluded_ids'] ?? array()); ?>"
                                placeholder="ex: 123, 456, 789">
                            <p class="description">Les pages/articles avec ces IDs ne seront pas inclus dans le sitemap.</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="homepage_priority">Priorité page d'accueil :</label>
                            <select id="homepage_priority" name="homepage_priority">
                                <?php $this->render_priority_options($this->settings['homepage_priority'] ?? '1.0'); ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="settings-actions">
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-saved"></span>
                        Enregistrer les réglages
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour gérer les URLs -->
<div id="urls-modal" class="rdv-modal" style="display:none;">
    <div class="rdv-modal-overlay"></div>
    <div class="rdv-modal-content">
        <div class="rdv-modal-header">
            <h2 id="modal-title">
                <span class="dashicons dashicons-admin-links"></span>
                <span class="title-text">Gérer les URLs</span>
            </h2>
            <button type="button" class="rdv-modal-close">&times;</button>
        </div>
        <div class="rdv-modal-body">
            <div class="modal-toolbar">
                <div class="toolbar-left">
                    <label class="select-all-label">
                        <input type="checkbox" id="select-all-urls" checked>
                        <span>Tout sélectionner</span>
                    </label>
                    <span class="selected-count">(<span id="selected-count">0</span> sélectionnés)</span>
                </div>
                <div class="toolbar-right">
                    <input type="text" id="url-search" placeholder="Rechercher..." class="url-search-input">
                </div>
            </div>
            <div id="urls-list" class="urls-list">
                <div class="loading-spinner">
                    <span class="dashicons dashicons-update spin"></span>
                    Chargement...
                </div>
            </div>
        </div>
        <div class="rdv-modal-footer">
            <div class="footer-info">
                <span class="dashicons dashicons-info"></span>
                Les URLs décochées seront exclues du sitemap XML
            </div>
            <div class="footer-actions">
                <button type="button" class="button" id="modal-cancel">Annuler</button>
                <button type="button" class="button button-primary" id="modal-save">
                    <span class="dashicons dashicons-saved"></span>
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

