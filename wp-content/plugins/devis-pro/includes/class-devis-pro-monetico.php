<?php
/**
 * Intégration Monetico Paiement — 3DSecure v2 / migration oct. 2026
 *
 * - contexte_commande (JSON UTF-8 Base64) obligatoire
 * - MAC = paires NomChamp=ValeurChamp triées alphabétiquement ASCII, séparées par *
 * - Le sceau ne doit porter QUE sur les champs réellement POSTés au formulaire
 */

if (!defined('ABSPATH')) {
    exit;
}

class Devis_Pro_Monetico {

    const VERSION = '3.0';
    const PAYMENT_URL = 'https://p.monetico-services.com/paiement.cgi';
    const PAYMENT_URL_TEST = 'https://p.monetico-services.com/test/paiement.cgi';

    /**
     * Champs du formulaire « Aller » inclus dans le MAC.
     * Ne pas y mettre d'options vides (echeances, 3DS challenge, etc.)
     * sinon le sceau ne correspond plus au POST reçu par Monetico.
     */
    const REQUEST_MAC_KEYS = array(
        'TPE',
        'contexte_commande',
        'date',
        'lgue',
        'mail',
        'montant',
        'reference',
        'societe',
        'texte-libre',
        'url_retour_err',
        'url_retour_ok',
        'version',
    );

    /**
     * @param string $hex_key Clé commerçant (40 caractères)
     * @return string|false Clé binaire opérationnelle
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
     * Sceau MAC (HMAC-SHA1, hex majuscules).
     *
     * @param array  $fields Nom => Valeur (sans MAC)
     * @param string $usable_key Clé binaire
     * @return string
     */
    public static function compute_seal(array $fields, $usable_key) {
        unset($fields['MAC'], $fields['action'], $fields['payment_url']);
        ksort($fields, SORT_STRING);

        $parts = array();
        foreach ($fields as $name => $value) {
            if (is_array($value)) {
                continue;
            }
            $parts[] = $name . '=' . $value;
        }

        return strtoupper(hash_hmac('sha1', implode('*', $parts), $usable_key));
    }

    /**
     * contexte_commande (Base64 JSON UTF-8).
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
        $civility = isset($civ_map[$civ]) ? $civ_map[$civ] : preg_replace('/[^A-Za-z]/', '', $civ);

        // Adresse client si dispo, sinon siège agence (Monetico refuse CP 00000 / champs fantaisistes)
        $cp    = self::truncate(trim((string) ($devis->cp ?? '')), 10);
        $ville = self::truncate(trim((string) ($devis->ville ?? '')), 50);
        $has_client_address = ($cp !== '' && $ville !== '' && !preg_match('/^0+$/', $cp));

        if ($has_client_address) {
            $address_line1 = self::truncate($cp . ' ' . $ville, 50);
        } else {
            $address_line1 = '6 rue Rene Viviani';
            $ville = 'Nantes';
            $cp = '44200';
        }

        $first = self::sanitize_person_name((string) ($devis->prenom ?? ''), 45);
        $last  = self::sanitize_person_name((string) ($devis->nom ?? ''), 45);
        // Monetico exige +33-612345678 (tiret), pas l'E.164 +33612345678 — sinon rejet « formulaire erroné ».
        $phone_monetico = self::format_phone_monetico((string) ($devis->tel ?? ''));

        $billing = array(
            'addressLine1' => $address_line1,
            'city'         => $ville,
            'postalCode'   => $cp,
            'country'      => 'FR',
        );

        if ($civility !== '') {
            $billing['civility'] = self::truncate($civility, 32);
        }
        if ($first !== '') {
            $billing['firstName'] = $first;
        }
        if ($last !== '') {
            $billing['lastName'] = $last;
        }
        if (!empty($devis->email)) {
            $billing['email'] = self::truncate((string) $devis->email, 100);
        }
        if ($phone_monetico !== '') {
            $billing['phone'] = $phone_monetico;
            $billing['mobilePhone'] = $phone_monetico;
        }

        $item_name = self::truncate(self::resolve_voyage_label($devis), 50);
        $unit_price = (int) round(((float) $devis->montant) * 100);

        $shipping = array(
            'addressLine1'        => $address_line1,
            'city'                => $ville,
            'postalCode'          => $cp,
            'country'             => 'FR',
            'shipIndicator'       => 'travel_and_event',
            'deliveryTimeframe'   => 'other',
            'matchBillingAddress' => true,
        );
        if ($first !== '') {
            $shipping['firstName'] = $first;
        }
        if ($last !== '') {
            $shipping['lastName'] = $last;
        }
        if (!empty($billing['email'])) {
            $shipping['email'] = $billing['email'];
        }
        if ($phone_monetico !== '') {
            $shipping['phone'] = $phone_monetico;
        }

        $client = array(
            'authenticationMethod' => 'guest',
        );
        foreach (array('civility', 'firstName', 'lastName', 'email', 'phone') as $key) {
            if (!empty($billing[$key])) {
                $client[$key] = $billing[$key];
            }
        }

        $context = array(
            'billing' => $billing,
            'shipping' => $shipping,
            'shoppingCart' => array(
                'shoppingCartItems' => array(
                    array(
                        'name'        => $item_name,
                        'productCode' => 'service',
                        'productRisk' => 'low',
                        'unitPrice'   => $unit_price,
                        'quantity'    => 1,
                        'productSKU'  => 'RDVASIE-' . (int) $devis->id,
                    ),
                ),
            ),
            'client' => $client,
        );

        $json = wp_json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return base64_encode($json !== false ? $json : '{}');
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
     * Champs du formulaire de paiement (avec MAC).
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
            'TPE'               => (string) ($settings['monetico_tpe'] ?? ''),
            'contexte_commande' => self::build_contexte_commande($devis),
            'date'              => $date,
            'lgue'              => 'FR',
            'mail'              => (string) $devis->email,
            'montant'           => $montant,
            'reference'         => $reference,
            'societe'           => (string) ($settings['monetico_societe'] ?? ''),
            'texte-libre'       => "Rendez-vous avec l'Asie",
            'url_retour_err'    => home_url('/paiement-annule/'),
            'url_retour_ok'     => home_url('/paiement-accepte/'),
            'version'           => self::VERSION,
        );

        $mac_fields = array();
        foreach (self::REQUEST_MAC_KEYS as $key) {
            $mac_fields[$key] = isset($fields[$key]) ? (string) $fields[$key] : '';
        }

        $mac_fields['MAC'] = self::compute_seal($mac_fields, $usable_key);
        $mac_fields['payment_url'] = self::PAYMENT_URL;

        // Alias pour la vue (compat)
        $mac_fields['tpe'] = $mac_fields['TPE'];
        $mac_fields['mac'] = $mac_fields['MAC'];
        $mac_fields['email'] = $mac_fields['mail'];
        $mac_fields['texte_libre'] = $mac_fields['texte-libre'];

        return $mac_fields;
    }

    /**
     * Valide le MAC IPN (alphabétique, tous champs reçus sauf MAC).
     *
     * @param array $post
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
        $post = self::normalize_ipn_post($post);

        $fields = $post;
        unset($fields['MAC'], $fields['action']);
        foreach ($fields as $k => $v) {
            if (is_array($v)) {
                unset($fields[$k]);
                continue;
            }
            $fields[$k] = (string) $v;
        }

        // 1) Nom=Valeur bruts (kit officiel)
        $computed_raw = self::compute_seal($fields, $usable_key);
        if (hash_equals($computed_raw, $received)) {
            return true;
        }

        // 2) Variante http_build_query + urldecode (kits PHP récents)
        $computed_query = self::compute_seal_http_query($fields, $usable_key);
        if (hash_equals($computed_query, $received)) {
            return true;
        }

        // 3) Ancien format positionnel
        $computed_legacy = self::compute_legacy_ipn_seal($post, $usable_key);
        if ($computed_legacy && hash_equals($computed_legacy, $received)) {
            error_log('[Devis Pro Monetico] IPN validée via MAC legacy');
            return true;
        }

        error_log(
            '[Devis Pro Monetico] IPN MAC invalid. keys=' . implode(',', array_keys($fields))
            . ' raw=' . $computed_raw
            . ' query=' . $computed_query
            . ' received=' . $received
        );
        return false;
    }

    /**
     * Restaure les '+' des champs base64 (PHP transforme + → espace en x-www-form-urlencoded).
     */
    public static function normalize_ipn_post(array $post) {
        foreach (array('authentification', 'contexte_commande') as $key) {
            if (!empty($post[$key]) && is_string($post[$key]) && strpos($post[$key], ' ') !== false) {
                $post[$key] = str_replace(' ', '+', $post[$key]);
            }
        }
        return $post;
    }

    /**
     * Parse le corps brut IPN en préservant les '+' (base64 authentification).
     *
     * @return array
     */
    public static function parse_ipn_request() {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $parsed = array();
            // Évite que parse_str convertisse + en espace dans les base64
            parse_str(str_replace('+', '%2B', $raw), $parsed);
            if (!empty($parsed)) {
                return self::normalize_ipn_post($parsed);
            }
        }
        return self::normalize_ipn_post($_POST);
    }

    /**
     * Sceau via http_build_query (compat kits Monetico PHP récents).
     */
    public static function compute_seal_http_query(array $fields, $usable_key) {
        unset($fields['MAC'], $fields['action'], $fields['payment_url']);
        ksort($fields, SORT_STRING);
        $query = http_build_query($fields, '', '*', PHP_QUERY_RFC1738);
        $query = urldecode($query);
        return strtoupper(hash_hmac('sha1', $query, $usable_key));
    }

    /**
     * @param array  $post
     * @param string $usable_key
     * @return string
     */
    public static function compute_legacy_ipn_seal(array $post, $usable_key) {
        $post = self::normalize_ipn_post($post);
        $get = static function ($key) use ($post) {
            return isset($post[$key]) ? (string) $post[$key] : '';
        };

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
     * @param array $post
     * @return array|null
     */
    public static function decode_authentification(array $post) {
        if (empty($post['authentification'])) {
            return null;
        }

        $raw = base64_decode((string) $post['authentification'], true);
        if ($raw === false || trim($raw) === 'null') {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * @param string $code_retour
     * @return bool
     */
    public static function is_payment_accepted($code_retour) {
        $code = (string) $code_retour;
        if ($code === 'paiement' || $code === 'payetest') {
            return true;
        }
        return (bool) preg_match('/^paiement_pf[2-4]$/', $code);
    }

    private static function truncate($value, $max) {
        $value = trim(wp_strip_all_tags((string) $value));
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }
        return substr($value, 0, $max);
    }

    /**
     * Prénom/nom Monetico : un seul libellé, sans "et …".
     */
    private static function sanitize_person_name($value, $max) {
        $value = trim(wp_strip_all_tags((string) $value));
        if ($value === '') {
            return '';
        }
        // "Nicolas et Aurélie" → "Nicolas"
        if (preg_match('/^(.+?)\s+et\s+/iu', $value, $m)) {
            $value = $m[1];
        }
        if (strpos($value, '/') !== false) {
            $value = trim(explode('/', $value)[0]);
        }
        if (strpos($value, ',') !== false) {
            $value = trim(explode(',', $value)[0]);
        }
        return self::truncate($value, $max);
    }

    /**
     * Téléphone Monetico : +33-612345678 (indicatif + tiret + national).
     * Omet si non fiable (évite +60… etc.). L'E.164 sans tiret est rejeté par la plateforme.
     */
    private static function format_phone_monetico($tel) {
        $digits = preg_replace('/\D+/', '', (string) $tel);
        if ($digits === '') {
            return '';
        }

        $national = '';
        // 0033XXXXXXXXX / 33XXXXXXXXX
        if (preg_match('/^(?:00)?33(\d{9})$/', $digits, $m)) {
            $national = $m[1];
        } elseif (preg_match('/^0(\d{9})$/', $digits, $m)) {
            // 0XXXXXXXXX (10 chiffres nationaux)
            $national = $m[1];
        } elseif (strpos($digits, '00') === 0 && strlen($digits) > 4) {
            // 00… puis retenter (ex: 0060122171707 mal saisi)
            $rest = substr($digits, 2);
            if (preg_match('/^33(\d{9})$/', $rest, $m)) {
                $national = $m[1];
            } elseif (preg_match('/^0*(\d{9})$/', $rest, $m) && in_array($m[1][0], array('6', '7', '1', '2', '3', '4', '5', '9'), true)) {
                $national = $m[1];
            }
        } elseif (preg_match('/^[1-9]\d{8}$/', $digits)) {
            // Déjà en 9 chiffres nationaux (sans 0)
            $national = $digits;
        }

        if ($national === '') {
            return '';
        }

        return '+33-' . $national;
    }
}
