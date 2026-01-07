/**
 * Devis Pro - Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialisation
    $(document).ready(function() {
        initCharts();
        initBulkActions();
        initChartPeriodSelector();
        initArchiveActions();
    });
    
    /**
     * Actions d'archivage
     */
    function initArchiveActions() {
        // Archiver
        $(document).on('click', '.devis-archive', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            
            if (!confirm('Archiver ce devis ?')) return;
            
            $.ajax({
                url: devisProAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'devis_pro_archive',
                    nonce: devisProAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Erreur');
                    }
                }
            });
        });
        
        // Restaurer
        $(document).on('click', '.devis-restore', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            
            if (!confirm('Restaurer ce devis ?')) return;
            
            $.ajax({
                url: devisProAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'devis_pro_restore',
                    nonce: devisProAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Erreur');
                    }
                }
            });
        });
    }

    /**
     * Initialiser les graphiques (si Chart.js est chargé)
     */
    function initCharts() {
        // Les graphiques sont initialisés directement dans les vues PHP
        // car les données sont générées côté serveur
    }

    /**
     * Changement de période pour les graphiques
     */
    function initChartPeriodSelector() {
        $('#chart-period').on('change', function() {
            var period = $(this).val();
            
            $.ajax({
                url: devisProAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'devis_pro_get_stats',
                    nonce: devisProAdmin.nonce,
                    period: period
                },
                success: function(response) {
                    if (response.success) {
                        updateEvolutionChart(response.data);
                    }
                }
            });
        });
    }

    /**
     * Mettre à jour le graphique d'évolution
     */
    function updateEvolutionChart(data) {
        var chart = Chart.getChart('devis-evolution-chart');
        if (chart && data.timeline) {
            chart.data.labels = data.timeline.map(d => d.date);
            chart.data.datasets[0].data = data.timeline.map(d => d.count);
            chart.data.datasets[1].data = data.timeline.map(d => d.amount);
            chart.update();
        }
    }

    /**
     * Actions groupées
     */
    function initBulkActions() {
        $('#doaction, #doaction2').on('click', function(e) {
            var action = $(this).prev('select').val();
            var ids = [];
            
            $('input[name="devis[]"]:checked').each(function() {
                ids.push($(this).val());
            });
            
            if (ids.length === 0) {
                alert('Veuillez sélectionner au moins un devis.');
                e.preventDefault();
                return false;
            }
            
            if (action === 'delete') {
                if (!confirm(devisProAdmin.strings.confirm_bulk_delete)) {
                    e.preventDefault();
                    return false;
                }
            }
            
            // Pour les actions AJAX
            if (['delete', 'mark_sent', 'send_reminder'].indexOf(action) !== -1) {
                e.preventDefault();
                
                $.ajax({
                    url: devisProAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'devis_pro_bulk_action',
                        nonce: devisProAdmin.nonce,
                        bulk_action: action,
                        ids: ids
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data);
                            location.reload();
                        } else {
                            alert(response.data || devisProAdmin.strings.error);
                        }
                    }
                });
            }
        });
    }

    /**
     * Confirmer la suppression
     */
    window.confirmDelete = function(message) {
        return confirm(message || devisProAdmin.strings.confirm_delete);
    };

})(jQuery);

