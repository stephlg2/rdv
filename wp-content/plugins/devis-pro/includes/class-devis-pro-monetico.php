<?php
/**
 * Intégration Monetico Paiement — conformité migration oct. 2026
 *
 * - contexte_commande (JSON UTF-8 Base64) obligatoire
 * - MAC = paires NomChamp=ValeurChamp triées alphabétiquement, séparées par *
 * - IPN : validation sur tous les champs reçus (dont authentification)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_Monetico {

    const VERSION = '3.0';
    const PAYMENT_URL = 'https://p.monetico-services.com/paiement.cgi';
    const PAYMENT_URL_TEST = 'https://p.monetico-services.com/test/paiement.cgi';

    /**
     * Champs de la requête « Aller » inclus dans le MAC (ordre alphabétique ASCII).
     * Les options optionnelles absentes sont valorisées à ''.
     */
    const REQUEST_MAC_KEYS = array(
        'TPE',
        'ThreeDSecureChallenge',
        'contexte_commande',
        'date',
        'dateech1',
        'dateech2',
        'dateech3',
        'dateech4',
        'lgue',
        'mail',
        'montant',
        'montantech1',
        'montantech2',
        'montantech3',
        'montantech4',
        'nbrech',
        'reference',
        'societe',
        'texte-libre',
        'url_retour_err',
        'url_retour_ok',
        'version',
    );

    /**
     * Clé opérationnelle (binaire) à partir de la clé hexadécimale commerçant.
     *
     * @param string $hex_key Clé 40 caractères
     * @return string|false
     */
    public static function get_usable_key($hex_key) {
        if (empty($hex_key) || strlen($hex_key) < 40) {
            return false;
        }

        $hex_str_key = substr($hex_key, 0, 38);
        $hex_final   = substr($hex_key, 38, 2) . '00';
        $cca0        = ord($hex_final);

        if ($cca0 > 70 && $cca0 < 97) {
            $hex_str_key .= chr($cca0 - 23) . substr($hex_final, 1, 1);
        } elseif (substr($hex_final, 1, 1) === 'M') {
            $hex_str_key .= substr($hex_final, 0, 1) . '0';
        } else {
            $hex_str_key .= substr($hex_final, 0, 2);
        }

        return pack('H*', $hex_str_key);
    }

    /**
     * Calcule le sceau MAC (HMAC-SHA1, hex majuscules).
     *
     * @param array  $fields Champs Nom => Valeur (sans MAC)
     * @param string $usable_key Clé binaire
     * @return string
     */
    public static function compute_seal(array $fields, $usable_key) {
        ksort($fields, SORT_STRING);

        $parts = array();
        foreach ($fields as $name => $value) {
            if ($name === 'MAC' || $name === 'action') {
                continue;
            }
            $parts[] = $name . '=' . $value;
        }

        $data = implode('*', $parts);

        return strtoupper(hash_hmac('sha1', $data, $usable_key));
    }

    /**
     * Construit le contexte_commande (Base64 JSON UTF-8).
     *
     * @param object $devis
     * @return string
     */
    public static function build_contexte_commande($devis) {
        $civ_map = array(
            'Mr'   => 'M',
            'M.'   => 'M',
            'Mme'  => 'Mme',
            'Mlle' => 'Mlle',
            'Dr'   => 'Dr',
        );
        $civ = isset($devis->civ) ? trim((string) $devis->civ) : '';
        $civility = isset($civ_map[$civ]) ? $civ_map[$civ] : $civ;

        $cp    = trim((string) ($devis->cp ?? ''));
        $ville = trim((string) ($devis->ville ?? ''));
        $tel   = preg_replace('/\s+/', '', (string) ($devis->tel ?? ''));

        // Champs obligatoires billing — fallbacks si absents du formulaire devis
        $billing = array(
            'addressLine1' => 'Adresse non communiquee',
            'city'         => $ville !== '' ? $ville : 'Non communiquee',
            'postalCode'   => $cp !== '' ? $cp : '00000',
            'country'      => 'FR',
        );

        if ($civility !== '') {
            $billing['civility'] = $civility;
        }
        if (!empty($devis->prenom)) {
            $billing['firstName'] = (string) $devis->prenom;
        }
        if (!empty($devis->nom)) {
            $billing['lastName'] = (string) $devis->nom;
        }
        if (!empty($devis->email)) {
            $billing['email'] = (string) $devis->email;
        }
        if ($tel !== '') {
            $billing['phone'] = $tel;
        }

        $item_name = self::resolve_voyage_label($devis);
        $unit_price = (int) round(((float) $devis->montant) * 100);

        $context = array(
            'billing' => $billing,
            'shoppingCart' => array(
                'shoppingCartItems' => array(
                    array(
                        'name'      => $item_name,
                        'unitPrice' => $unit_price,
                        'quantity'  => 1,
                        'productSKU'=> 'RDVASIE-' . (int) $devis->id,
                        'productRisk' => 'low',
                    ),
                ),
            ),
            'client' => array_filter(array(
                'civility'  => $civility !== '' ? $civility : null,
                'firstName' => !empty($devis->prenom) ? (string) $devis->prenom : null,
                'lastName'  => !empty($devis->nom) ? (string) $devis->nom : null,
                'email'     => !empty($devis->email) ? (string) $devis->email : null,
                'phone'     => $tel !== '' ? $tel : null,
            ), static function ($v) {
                return $v !== null && $v !== '';
            }),
        );

        // Pas de shipping physique (voyage) — ne pas envoyer d'objet vide

        $json = wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return base64_encode($json);
    }

    /**
     * @param object $devis
     * @return string
     */
    private static function resolve_voyage_label($devis) {
        if (!empty($devis->voyage) && is_numeric($devis->voyage)) {
            $title = get_the_title((int) $devis->voyage);
            if ($title) {
                return wp_strip_all_tags($title);
            }
        }
        if (!empty($devis->voyage) && !is_numeric($devis->voyage)) {
            return wp_strip_all_tags((string) $devis->voyage);
        }
        if (!empty($devis->destination)) {
            return 'Voyage ' . wp_strip_all_tags((string) $devis->destination);
        }
        return 'Voyage RDV Asie #' . (int) $devis->id;
    }

    /**
     * Prépare tous les champs du formulaire de paiement (avec MAC).
     *
     * @param object $devis
     * @param array  $settings
     * @return array|null
     */
    public static function build_payment_fields($devis, array $settings) {
        if (empty($devis) || (int) $devis->status !== 1 || (float) $devis->montant <= 0) {
            return null;
        }

        $usable_key = self::get_usable_key($settings['monetico_cle'] ?? '');
        if ($usable_key === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Devis Pro Monetico] Clé Monetico manquante ou invalide');
            }
            return null;
        }

        $currency  = !empty($settings['default_currency']) ? $settings['default_currency'] : 'EUR';
        $montant   = number_format((float) $devis->montant, 2, '.', '') . $currency;
        $reference = 'RDVASIE-' . str_pad((string) $devis->id, 5, '0', STR_PAD_LEFT);
        $date      = wp_date('d/m/Y:H:i:s');

        $fields = array(
            'TPE'                   => (string) ($settings['monetico_tpe'] ?? ''),
            'ThreeDSecureChallenge' => '',
            'contexte_commande'     => self::build_contexte_commande($devis),
            'date'                  => $date,
            'dateech1'              => '',
            'dateech2'              => '',
            'dateech3'              => '',
            'dateech4'              => '',
            'lgue'                  => 'FR',
            'mail'                  => (string) $devis->email,
            'montant'               => $montant,
            'montantech1'           => '',
            'montantech2'           => '',
            'montantech3'           => '',
            'montantech4'           => '',
            'nbrech'                => '',
            'reference'             => $reference,
            'societe'               => (string) ($settings['monetico_societe'] ?? ''),
            'texte-libre'           => "Rendez-vous avec l'Asie",
            'url_retour_err'        => home_url('/paiement-annule/'),
            'url_retour_ok'         => home_url('/paiement-accepte/'),
            'version'               => self::VERSION,
        );

        // Ne garder que les clés prévues pour le MAC / formulaire
        $mac_fields = array();
        foreach (self::REQUEST_MAC_KEYS as $key) {
            $mac_fields[$key] = isset($fields[$key]) ? $fields[$key] : '';
        }

        $mac = self::compute_seal($mac_fields, $usable_key);
        $mac_fields['MAC'] = $mac;
        $mac_fields['payment_url'] = self::PAYMENT_URL;

        return $mac_fields;
    }

    /**
     * Valide le MAC de l'IPN (tous les champs POST reçus sauf MAC/action).
     * Tente d'abord le format alphabétique (migration 2026), puis l'ancien format fixe.
     *
     * @param array $post Données $_POST brutes
     * @param array $settings
     * @return bool
     */
    public static function validate_ipn_seal(array $post, array $settings) {
        if (empty($post['MAC'])) {
            return false;
        }

        $usable_key = self::get_usable_key($settings['monetico_cle'] ?? '');
        if ($usable_key === false) {
            return false;
        }

        $expected_tpe = (string) ($settings['monetico_tpe'] ?? '');
        if ($expected_tpe !== '' && isset($post['TPE']) && (string) $post['TPE'] !== $expected_tpe) {
            error_log('[Devis Pro Monetico] IPN TPE mismatch: ' . $post['TPE']);
            return false;
        }

        $received = strtoupper((string) $post['MAC']);

        // 1) Nouveau format : NomChamp=ValeurChamp triés alphabétiquement
        $fields = $post;
        unset($fields['MAC'], $fields['action']);
        foreach ($fields as $k => $v) {
            if (is_array($v)) {
                unset($fields[$k]);
                continue;
            }
            $fields[$k] = (string) $v;
        }

        $computed_new = self::compute_seal($fields, $usable_key);
        if (hash_equals($computed_new, $received)) {
            return true;
        }

        // 2) Ancien format (transition jusqu'à migration Monetico 03/10/2026)
        $computed_legacy = self::compute_legacy_ipn_seal($post, $usable_key);
        if ($computed_legacy && hash_equals($computed_legacy, $received)) {
            error_log('[Devis Pro Monetico] IPN validée via MAC legacy');
            return true;
        }

        error_log('[Devis Pro Monetico] IPN MAC invalid. new=' . $computed_new . ' legacy=' . ($computed_legacy ?: 'n/a') . ' received=' . $received);
        return false;
    }

    /**
     * Ancien MAC IPN : concaténation ordonnée des valeurs (sans Nom=).
     *
     * @param array  $post
     * @param string $usable_key
     * @return string
     */
    public static function compute_legacy_ipn_seal(array $post, $usable_key) {
        $get = static function ($key) use ($post) {
            return isset($post[$key]) ? (string) $post[$key] : '';
        };

        // Chaîne historique Monetico (doc ≤ v2.0 « Retour »)
        $data = $get('TPE') . '*'
            . $get('date') . '*'
            . $get('montant') . '*'
            . $get('reference') . '*'
            . $get('texte-libre') . '*'
            . '3.0*'
            . $get('code-retour') . '*'
            . $get('cvx') . '*'
            . $get('vld') . '*'
            . $get('brand') . '*'
            . $get('status3ds') . '*'
            . $get('numauto') . '*'
            . $get('motifrefus') . '*'
            . $get('originecb') . '*'
            . $get('bincb') . '*'
            . $get('hpancb') . '*'
            . $get('ipclient') . '*'
            . $get('originetr') . '*'
            . $get('veres') . '*'
            . $get('pares') . '*';

        return strtoupper(hash_hmac('sha1', $data, $usable_key));
    }

    /**
     * Décode le champ authentification (Base64 JSON) de l'IPN.
     *
     * @param array $post
     * @return array|null
     */
    public static function decode_authentification(array $post) {
        if (empty($post['authentification'])) {
            return null;
        }

        $raw = base64_decode((string) $post['authentification'], true);
        if ($raw === false) {
            return null;
        }

        // Monetico peut envoyer "null" encodé
        if (trim($raw) === 'null') {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Indique si le code-retour correspond à un paiement accepté.
     *
     * @param string $code_retour
     * @return bool
     */
    public static function is_payment_accepted($code_retour) {
        $code = (string) $code_retour;
        if ($code === 'paiement' || $code === 'payetest') {
            return true;
        }
        // Paiements fractionnés : paiement_pf2, paiement_pf3, paiement_pf4
        return (bool) preg_match('/^paiement_pf[2-4]$/', $code);
    }
}
