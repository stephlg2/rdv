<?php
/**
 * Classe de gestion des emails - Version fonctionnelle
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_Email {

    private $settings;
    private $from_email;
    private $from_name;

    public function __construct() {
        $this->settings = get_option('devis_pro_settings');
        $this->from_email = $this->settings['email_from_address'] ?? 'devis@rdvasie.com';
        // Utiliser un nom sans apostrophe pour éviter les problèmes d'encodage
        $this->from_name = 'Rendez-vous avec l Asie';
    }

    /**
     * Envoyer un email
     */
    private function send($to, $subject, $message) {
        // Encoder le nom de l'expéditeur en UTF-8 pour éviter les problèmes d'accents
        $encoded_name = '=?UTF-8?B?' . base64_encode($this->from_name) . '?=';
        
        $headers = array(
            'From: ' . $encoded_name . ' <' . $this->from_email . '>',
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . ($this->settings['email_admin'] ?? 'contact@rdvasie.com')
        );

        // Ajouter le header et footer HTML
        $html_message = $this->wrap_html($message);

        // Envoyer l'email
        $result = wp_mail($to, $subject, $html_message, $headers);

        // Log pour debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Devis Pro Email] To: %s | Subject: %s | Result: %s',
                $to,
                $subject,
                $result ? 'SUCCESS' : 'FAILED'
            ));
        }

        return $result;
    }

    /**
     * Wrapper HTML pour les emails
     */
    private function wrap_html($content) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f5f5f5;">
    <div style="max-width:600px;margin:0 auto;padding:20px;">
        <div style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
            <div style="background:#fff;padding:25px;text-align:center;border-bottom:1px solid #de5b09;">
                <img src="https://www.rdvasie.com/wp-content/uploads/2018/11/rdv-asie.png" alt="RDV Asie" style="max-width:180px;height:auto;">
            </div>
            <div style="padding:30px;">
                ' . $content . '
            </div>
            <div style="background:#f9f9f9;padding:20px;text-align:center;border-top:1px solid #eee;font-size:13px;color:#666;">
                <p style="margin:0;">Rendez-vous avec l\'Asie<br>
                <a href="mailto:contact@rdvasie.com" style="color:#de5b09;">contact@rdvasie.com</a> | 
                <a href="https://www.rdvasie.com" style="color:#de5b09;">www.rdvasie.com</a><br>
                Tél: 02 14 00 12 53</p>
                <p style="margin:15px 0 0;padding-top:15px;border-top:1px solid #e0e0e0;">
                    <a href="https://www.facebook.com/rdvAsie/" target="_blank" style="text-decoration:none;margin:0 8px;display:inline-block;vertical-align:middle;">
                        <img src="https://cdn-icons-png.flaticon.com/32/733/733547.png" alt="Facebook" width="28" height="28" style="display:inline-block;border:0;">
                    </a>
                    <a href="https://www.instagram.com/rdvasie/" target="_blank" style="text-decoration:none;margin:0 8px;display:inline-block;vertical-align:middle;">
                        <img src="https://cdn-icons-png.flaticon.com/32/2111/2111463.png" alt="Instagram" width="28" height="28" style="display:inline-block;border:0;">
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Envoyer la notification admin pour une nouvelle demande
     */
    public function send_new_request_notification($devis) {
        if (!$devis || !isset($devis->email)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro Email] send_new_request_notification: devis invalide');
            }
            return false;
        }
        
        $admin_email = $this->settings['email_admin'] ?? get_option('admin_email');
        if (empty($admin_email)) {
            $admin_email = get_option('admin_email');
        }
        
        if (empty($admin_email)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro Email] send_new_request_notification: admin_email vide');
            }
            return false;
        }
        
        try {
            $voyage = $this->get_voyage_title($devis->voyage);
        } catch (Exception $e) {
            $voyage = !empty($devis->destination) ? $devis->destination : 'Voyage en Asie';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro Email] Erreur get_voyage_title: ' . $e->getMessage());
            }
        }

        $subject = sprintf('[Nouveau devis] Demande de %s %s', $devis->prenom, $devis->nom);

        $message = '
            <h2 style="color:#de5b09;margin-top:0;">Nouvelle demande de devis</h2>
            <p>Vous avez reçu une nouvelle demande de devis.</p>
            
            <table style="width:100%;border-collapse:collapse;margin:20px 0;">
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;width:150px;"><strong>Client</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($devis->civ . ' ' . $devis->prenom . ' ' . $devis->nom) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Email</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;"><a href="mailto:' . esc_attr($devis->email) . '">' . esc_html($devis->email) . '</a></td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Téléphone</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($devis->tel) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Code postal</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($devis->cp) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Ville</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($devis->ville) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Voyage</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($voyage) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Dates</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($devis->depart) . ' → ' . esc_html($devis->retour) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Durée</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($devis->duree) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Participants</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . intval($devis->adulte) . ' adulte(s), ' . intval($devis->enfant) . ' enfant(s), ' . intval($devis->bebe) . ' bébé(s)</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Vol inclus</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . esc_html($devis->vol) . '</td>
                </tr>
                <tr>
                    <td style="padding:10px;border:1px solid #ddd;background:#f9f9f9;"><strong>Message</strong></td>
                    <td style="padding:10px;border:1px solid #ddd;">' . nl2br(esc_html($devis->message)) . '</td>
                </tr>
            </table>
            
            <p style="text-align:center;">
                <a href="' . admin_url('admin.php?page=devis-pro-detail&id=' . $devis->id) . '" style="display:inline-block;background:#de5b09;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Voir le devis dans l\'admin</a>
            </p>
        ';

        $result = $this->send($admin_email, $subject, $message);
        
        if (!$result && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Devis Pro Email] Échec envoi notification admin à: ' . $admin_email);
        }
        
        return $result;
    }

    /**
     * Envoyer la confirmation au client
     */
    public function send_confirmation_to_client($devis) {
        if (!$devis || !isset($devis->email) || empty($devis->email)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro Email] send_confirmation_to_client: devis invalide ou email vide');
            }
            return false;
        }
        
        // Essayer de récupérer le titre du voyage
        $voyage = $this->get_voyage_title($devis->voyage);
        
        // Si le titre n'a pas pu être récupéré, utiliser la destination
        if (empty($voyage) || $voyage === 'Voyage en Asie' || $voyage === $devis->voyage) {
            if (!empty($devis->destination)) {
                $voyage = $devis->destination;
            } else {
                $voyage = 'Voyage en Asie';
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Devis Pro Email] send_confirmation_to_client - voyage: ' . $voyage . ' | destination: ' . ($devis->destination ?? 'vide') . ' | voyage_data: ' . ($devis->voyage ?? 'vide'));
        }
        
        // Générer le lien d'accès direct à l'espace client
        $token = md5($devis->email . wp_salt());
        $access_url = home_url('/espace-client/?email=' . urlencode($devis->email) . '&token=' . $token);

        $subject = 'Nous avons bien reçu votre demande de voyage !';

        // Préparer le descriptif du projet si présent
        $descriptif_html = '';
        if (!empty($devis->message)) {
            $descriptif_html = '
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Descriptif de votre projet :</strong> <em style="color:#666;">' . nl2br(esc_html($devis->message)) . '</em></li>
            ';
        }

        // Préparer l'adresse complète
        $adresse_complete = trim($devis->cp . ' ' . $devis->ville);
        if (empty($adresse_complete)) {
            $adresse_complete = '-';
        }

        $message = '
            <h2 style="color:#de5b09;margin-top:0;">Merci pour votre demande !</h2>
            <p>Bonjour ' . esc_html($devis->prenom) . ',</p>
            <p>Nous avons bien reçu votre demande de devis et nous vous en remercions.</p>
            <p>Notre équipe va étudier votre projet et vous recontactera très rapidement.</p>
            
            <h3 style="color:#333;border-bottom:2px solid #de5b09;padding-bottom:10px;">Récapitulatif de votre demande</h3>
            
            <ul style="list-style:none;padding:0;">
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Voyage :</strong> ' . esc_html($voyage) . '</li>
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Dates :</strong> ' . esc_html($devis->depart) . ' → ' . esc_html($devis->retour) . '</li>
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Durée :</strong> ' . esc_html($devis->duree) . '</li>
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Participants :</strong> ' . intval($devis->adulte) . ' adulte(s), ' . intval($devis->enfant) . ' enfant(s), ' . intval($devis->bebe) . ' bébé(s)</li>
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Vol inclus :</strong> ' . esc_html($devis->vol) . '</li>
                ' . $descriptif_html . '
            </ul>
            
            <h3 style="color:#333;border-bottom:2px solid #de5b09;padding-bottom:10px;margin-top:30px;">Vos coordonnées</h3>
            
            <ul style="list-style:none;padding:0;">
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Nom :</strong> ' . esc_html($devis->civ . ' ' . $devis->prenom . ' ' . $devis->nom) . '</li>
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Email :</strong> <a href="mailto:' . esc_attr($devis->email) . '" style="color:#de5b09;">' . esc_html($devis->email) . '</a></li>
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Téléphone :</strong> ' . esc_html($devis->tel) . '</li>
                <li style="padding:8px 0;"><strong>Adresse :</strong> ' . esc_html($adresse_complete) . '</li>
            </ul>
            
            <p style="text-align:center;margin:30px 0 20px;">
                <a href="' . esc_url($access_url) . '" style="display:inline-block;background:#de5b09;color:#fff;padding:15px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:bold;">Accéder à mon espace client</a>
            </p>
            
            <p style="color:#666;font-size:13px;margin-top:20px;">Une erreur dans vos coordonnées ? N\'hésitez pas à nous contacter par mail à <a href="mailto:contact@rdvasie.com" style="color:#de5b09;">contact@rdvasie.com</a></p>
            
            <p style="margin-top:30px;">À très bientôt !</p>
            <p><strong>L\'équipe Rendez-vous avec l\'Asie</strong></p>
        ';

        $result = $this->send($devis->email, $subject, $message);
        
        if (!$result && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Devis Pro Email] Échec envoi confirmation client à: ' . $devis->email);
        }
        
        return $result;
    }

    /**
     * Envoyer le lien de paiement
     * @param object $devis L'objet devis
     * @param string $custom_message Message personnalisé optionnel
     */
    public function send_payment_link($devis, $custom_message = '') {
        if (empty($devis->token) || $devis->montant <= 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro Email] send_payment_link: token vide ou montant <= 0');
            }
            return false;
        }

        $voyage = $this->get_voyage_title($devis->voyage);
        $payment_url = home_url('/paiement/?q=' . $devis->token);

        $subject = 'Votre devis personnalisé est prêt :)';

        // Bloc message personnalisé
        $custom_message_html = '';
        if (!empty($custom_message)) {
            $custom_message_html = '
            <div style="background:#fff;border:1px solid #ddd;padding:15px 20px;margin:20px 0;border-radius:8px;">
                <p style="margin:0 0 10px 0;font-weight:bold;color:#333;">Message de votre conseiller :</p>
                <p style="margin:0;color:#333;">' . nl2br(esc_html($custom_message)) . '</p>
            </div>';
        }

        $message = '
            <h2 style="color:#de5b09;margin-top:0;">Votre devis est prêt !</h2>
            <p>Bonjour ' . esc_html($devis->prenom) . ',</p>
            <p>Nous avons le plaisir de vous transmettre votre devis personnalisé pour votre voyage.</p>
            
            ' . $custom_message_html . '
            
            <div style="background:#f8f9fa;border-radius:10px;padding:20px;margin:25px 0;text-align:center;">
                <p style="margin:0;font-size:14px;color:#666;">Voyage : <strong>' . esc_html($voyage) . '</strong></p>
                <p style="margin:15px 0 0;font-size:36px;color:#de5b09;font-weight:bold;">' . number_format($devis->montant, 0, ',', ' ') . ' €</p>
            </div>
            
            <p style="text-align:center;">
                <a href="' . esc_url($payment_url) . '" style="display:inline-block;background:#de5b09;color:#fff;padding:18px 50px;text-decoration:none;border-radius:8px;font-size:18px;font-weight:bold;">Procéder au paiement</a>
            </p>
            
            <p style="text-align:center;color:#666;font-size:13px;margin-top:20px;">
                🔒 Paiement sécurisé via Monetico CIC
            </p>
            
            ' . $this->get_client_access_button($devis->email) . '
            
            <p style="margin-top:30px;">Une question ? N\'hésitez pas à nous contacter !</p>
            <p><strong>L\'équipe Rendez-vous avec l\'Asie</strong></p>
        ';

        return $this->send($devis->email, $subject, $message);
    }

    /**
     * Envoyer une relance
     */
    public function send_reminder($devis) {
        if (empty($devis->token) || $devis->montant <= 0) {
            return false;
        }

        $payment_url = home_url('/paiement/?q=' . $devis->token);

        $subject = 'Votre devis voyage vous attend toujours !';

        $message = '
            <h2 style="color:#de5b09;margin-top:0;">Votre devis voyage vous attend !</h2>
            <p>Bonjour ' . esc_html($devis->prenom) . ',</p>
            <p>Nous vous rappelons que votre devis est prêt et n\'attend que vous.</p>
            
            <div style="background:#fff5f0;border:2px solid #de5b09;border-radius:10px;padding:25px;margin:25px 0;text-align:center;">
                <p style="margin:0;font-size:32px;color:#de5b09;font-weight:bold;">' . number_format($devis->montant, 0, ',', ' ') . ' €</p>
            </div>
            
            <p style="text-align:center;">
                <a href="' . esc_url($payment_url) . '" style="display:inline-block;background:#de5b09;color:#fff;padding:18px 50px;text-decoration:none;border-radius:8px;font-size:18px;font-weight:bold;">Accéder au paiement</a>
            </p>
            
            ' . $this->get_client_access_button($devis->email) . '
            
            <p style="margin-top:30px;">N\'hésitez pas à nous contacter si vous avez des questions.</p>
            <p><strong>L\'équipe Rendez-vous avec l\'Asie</strong></p>
        ';

        return $this->send($devis->email, $subject, $message);
    }

    /**
     * Envoyer le lien d'accès à l'espace client
     */
    public function send_client_access_link($email) {
        // Vérifier qu'il y a des devis pour cet email
        $db = new Devis_Pro_DB();
        $devis_list = $db->get_devis_by_email($email);

        if (empty($devis_list)) {
            // Log mais retourne true pour ne pas révéler si l'email existe
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro Email] send_client_access_link: aucun devis pour ' . $email);
            }
            return true;
        }

        // Utiliser le nouveau système de tokens sécurisés
        $token = Devis_Pro_Security::generate_access_token($email);
        $access_url = home_url('/espace-client/?email=' . urlencode($email) . '&token=' . $token);

        $subject = 'Votre lien d\'accès à votre espace voyage';

        $message = '
            <h2 style="color:#de5b09;margin-top:0;">Accès à votre espace client</h2>
            <p>Bonjour,</p>
            <p>Vous avez demandé un accès à votre espace client pour consulter vos offres de voyage.</p>
            <p>Cliquez sur le bouton ci-dessous pour y accéder :</p>
            
            <p style="text-align:center;margin:30px 0;">
                <a href="' . esc_url($access_url) . '" style="display:inline-block;background:#de5b09;color:#fff;padding:18px 50px;text-decoration:none;border-radius:8px;font-size:18px;font-weight:bold;">Accéder à mon espace</a>
            </p>
            
            <p style="color:#666;font-size:13px;background:#f9f9f9;padding:15px;border-radius:5px;">
                ⚠️ Ce lien est personnel et valable pendant 24 heures.<br>
                Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.
            </p>
            
            <p><strong>L\'équipe Rendez-vous avec l Asie</strong></p>
        ';

        return $this->send($email, $subject, $message);
    }

    /**
     * Envoyer l'email de statut "Accepté"
     */
    public function send_status_accepted($devis) {
        $voyage = $this->get_voyage_title($devis->voyage);
        
        // Générer le lien d'accès direct à l'espace client
        $token = md5($devis->email . wp_salt());
        $access_url = home_url('/espace-client/?email=' . urlencode($devis->email) . '&token=' . $token);

        $subject = 'Bonne nouvelle : votre projet de voyage est accepté !';

        $message = '
            <h2 style="color:#28a745;margin-top:0;">🎉 Votre projet est accepté !</h2>
            <p>Bonjour ' . esc_html($devis->prenom) . ',</p>
            <p>Excellente nouvelle ! Votre projet de voyage a été accepté.</p>
            
            <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:10px;padding:25px;margin:25px 0;text-align:center;">
                <p style="margin:0;font-size:18px;color:#155724;font-weight:bold;">' . esc_html($voyage) . '</p>
                ' . ($devis->montant > 0 ? '<p style="margin:15px 0 0;font-size:28px;color:#155724;font-weight:bold;">' . number_format($devis->montant, 0, ',', ' ') . ' €</p>' : '') . '
            </div>
            
            <p>Notre équipe va maintenant préparer les prochaines étapes pour concrétiser votre voyage de rêve.</p>
            
            <p style="text-align:center;margin:30px 0;">
                <a href="' . esc_url($access_url) . '" style="display:inline-block;background:#de5b09;color:#fff;padding:15px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:bold;">Suivre mon dossier</a>
            </p>
            
            <p>Une question ? N\'hésitez pas à nous contacter !</p>
            <p><strong>L\'équipe Rendez-vous avec l\'Asie</strong></p>
        ';

        return $this->send($devis->email, $subject, $message);
    }

    /**
     * Envoyer l'email de statut "Annulé"
     */
    public function send_status_cancelled($devis) {
        $voyage = $this->get_voyage_title($devis->voyage);

        $subject = 'Information concernant votre projet de voyage';

        $message = '
            <h2 style="color:#6c757d;margin-top:0;">Information sur votre demande</h2>
            <p>Bonjour ' . esc_html($devis->prenom) . ',</p>
            <p>Nous vous informons que votre demande de devis pour le voyage suivant a été annulée :</p>
            
            <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:10px;padding:20px;margin:25px 0;">
                <p style="margin:0;font-size:16px;color:#333;"><strong>' . esc_html($voyage) . '</strong></p>
            </div>
            
            <p>Si vous souhaitez reprendre ce projet ou en démarrer un nouveau, nous restons à votre entière disposition.</p>
            
            <p style="text-align:center;margin:30px 0;">
                <a href="' . home_url('/demande-de-devis/') . '" style="display:inline-block;background:#de5b09;color:#fff;padding:15px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:bold;">Nouvelle demande de devis</a>
            </p>
            
            ' . $this->get_client_access_button($devis->email) . '
            
            <p>À bientôt peut-être pour une nouvelle aventure !</p>
            <p><strong>L\'équipe Rendez-vous avec l\'Asie</strong></p>
        ';

        return $this->send($devis->email, $subject, $message);
    }

    /**
     * Envoyer la confirmation de paiement
     */
    public function send_payment_confirmation($devis) {
        $voyage = $this->get_voyage_title($devis->voyage);

        $subject = 'Félicitations ! Votre voyage est confirmé';

        $message = '
            <h2 style="color:#28a745;margin-top:0;">✅ Paiement confirmé !</h2>
            <p>Bonjour ' . esc_html($devis->prenom) . ',</p>
            <p>Nous avons bien reçu votre paiement. Merci pour votre confiance !</p>
            
            <div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:10px;padding:25px;margin:25px 0;text-align:center;">
                <p style="margin:0;font-size:28px;color:#155724;font-weight:bold;">' . number_format($devis->montant, 0, ',', ' ') . ' €</p>
                <p style="margin:10px 0 0;color:#155724;">Paiement reçu</p>
            </div>
            
            <h3 style="color:#333;">Détails de votre réservation</h3>
            <ul style="list-style:none;padding:0;">
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Voyage :</strong> ' . esc_html($voyage) . '</li>
                <li style="padding:8px 0;border-bottom:1px solid #eee;"><strong>Référence :</strong> RDVASIE-' . str_pad($devis->id, 5, "0", STR_PAD_LEFT) . '</li>
            </ul>
            
            <p>Vous recevrez prochainement tous les détails de votre voyage par email.</p>
            
            ' . $this->get_client_access_button($devis->email) . '
            
            <p style="margin-top:30px;">À très bientôt pour l\'aventure !</p>
            <p><strong>L\'équipe Rendez-vous avec l\'Asie</strong></p>
        ';

        // Envoyer au client
        $result = $this->send($devis->email, $subject, $message);

        // Notifier l'admin
        $admin_email = $this->settings['email_admin'] ?? get_option('admin_email');
        $admin_subject = sprintf('[Paiement reçu] Devis #%d - %s %s - %s€', $devis->id, $devis->prenom, $devis->nom, number_format($devis->montant, 0, ',', ' '));
        $this->send($admin_email, $admin_subject, $message);

        return $result;
    }

    /**
     * Générer le lien "Accéder à mon espace client"
     */
    private function get_client_access_button($email) {
        // Utiliser le nouveau système de tokens sécurisés (24h d'expiration)
        $token = Devis_Pro_Security::generate_access_token($email);
        $access_url = home_url('/espace-client/?email=' . urlencode($email) . '&token=' . $token);
        
        return '
            <p style="text-align:center;margin:25px 0 10px;">
                <a href="' . esc_url($access_url) . '" style="color:#de5b09;text-decoration:underline;font-size:14px;">Accéder à mon espace client</a>
            </p>
        ';
    }

    /**
     * Obtenir le titre du voyage
     */
    private function get_voyage_title($voyage_data) {
        if (empty($voyage_data)) {
            return '';
        }

        // Si c'est déjà un texte (pas un ID), le retourner tel quel
        if (!is_numeric($voyage_data) && strpos($voyage_data, '-;-') === false) {
            return $voyage_data;
        }

        $ids = explode("-;-", $voyage_data);
        $titles = array();

        foreach ($ids as $id) {
            $id = trim($id);
            if (!empty($id) && is_numeric($id)) {
                // Essayer d'abord avec le post_type 'tripzzy'
                $post = get_post($id);
                if ($post && ($post->post_type === 'tripzzy' || $post->post_type === 'product')) {
                    $title = get_the_title($id);
                    if ($title && $title !== 'Auto Draft' && $title !== '') {
                        $titles[] = $title;
                    }
                }
            }
        }

        return !empty($titles) ? implode(', ', $titles) : '';
    }
}
