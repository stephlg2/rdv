<?php
/*

Plugin Name: Gestion de devis

*/

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Devis_List_Table extends WP_List_Table {


    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;

        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'devis',     //singular name of the listed records
            'plural'    => 'devis',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );

    }


    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title()
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as
     * possible.
     *
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     *
     * For more detailed insight into how columns are handled, take a look at
     * WP_List_Table::single_row_columns()
     *
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
        switch($column_name){
            case 'demande':
            case 'nom':
            case 'prenom':
            case 'destination':
            case 'status':
                return $item->$column_name;
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }


    function column_status($item){

        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&devis=%s">Voir</a>', 'devis-detail',$item->id),
            'delete'    => sprintf('<a href="?page=%s&devis=%s" onclick="return confirm(\'Etes-vous sûr de vouloir supprimer cette demande ?\')">Supprimer</a>', 'devis-delete',$item->id),
        );

        switch($item->status){
            case 0: $status = "En cours de traitement";
                break;
            case 1: $status = "Réponse envoyée";
                break;
            case 2: $status = "Devis accepté";
                break;
            case 3: $status = "Devis refusé";
                break;
        }


        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ $status,
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_destination($item){

        $ids = explode("-;-", $item->destination);
        $output = "";

        foreach( $ids as $id) :
            if( $id != 0 && $id != "" && $id != NULL ) :
                $output .= get_the_title( $id ) . "<br/>";
            endif;

        endforeach;

        if($output == ""){
            $output = $item->voyage;
        }

        //Return the title contents
        return sprintf('%1$s', $output);
    }




    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     *
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }


    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     *
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     *
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'demande'     => 'Date de la demande',
            'nom'     => 'Nom',
            'prenom'    => 'Prénom',
            'destination'  => 'Destination',
            'status'  => 'Status de la demande'
        );
        return $columns;
    }


    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
     * you will need to register it here. This should return an array where the
     * key is the column that needs to be sortable, and the value is db column to
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     *
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     *
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            'demande'     => array('demande',false),     //true means it's already sorted
            'nom'     => array('nom',false),
            'prenom'    => array('prenom',false),
            'destination'  => array('destination',false),
            'status'    => array('status',false)
        );
        return $sortable_columns;
    }


    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     *
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     *
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     *
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array();
        return $actions;
    }


    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     *
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }

    }


    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     *
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 10;


        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();


        /**
         * REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);


        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();


        $table_name = $wpdb->prefix . 'devis';

        $data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY demande DESC");

        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently
         * looking at. We'll need this later, so you should always include it in
         * your own package classes.
         */
        $current_page = $this->get_pagenum();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);

        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;


        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }


}

/* Activation/Desactivation */

register_activation_hook( __FILE__, 'devis_create_db' );
function devis_create_db() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'devis';

    $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		destination varchar(200) NOT NULL,
		voyage text NOT NULL,
		depart varchar(20) NOT NULL,
		retour varchar(20) NOT NULL,
		duree varchar(50) NOT NULL,
		budget mediumint(6) NOT NULL,
		adulte smallint(2) NOT NULL,
		enfant smallint(2) NOT NULL,
		bebe smallint(2) NOT NULL,
		vol varchar(10) NOT NULL,
		message text NOT NULL,
		civ varchar(5) NOT NULL,
		nom varchar(200) NOT NULL,
		prenom varchar(200) NOT NULL,
		email varchar(300) NOT NULL,
		cp varchar(10) NOT NULL,
		ville varchar(200) NOT NULL,
		tel varchar(50) NOT NULL,
		status tinyint(1) NOT NULL,
		montant mediumint(6) NOT NULL,
		demande varchar(20) NOT NULL,
		langue varchar(10) NOT NULL,
		token varchar(300) NOT NULL,
		mac varchar(300) NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'devis_destroy_db' );
function devis_destroy_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'devis';

    $sql = "DROP TABLE $table_name";

    $wpdb->query($sql);

    flush_rewrite_rules();
}

/* Route */

/* Admin */

add_action('admin_menu', 'devis_setup_menu');

function devis_setup_menu(){
    add_menu_page( 'Gestion des demandes de devis', 'Gestion des devis', 'manage_options', 'devis', 'devis_init' );
    add_submenu_page( 'options.php','Détail de la demande', "Voir la demande",'manage_options', 'devis-detail', 'devis_detail' );
    add_submenu_page( 'options.php','Supprimer la demande', "Supprimer la demande",'manage_options', 'devis-submit', 'devis_submit' );
    add_submenu_page( 'options.php','Supprimer la demande', "Supprimer la demande",'manage_options', 'devis-delete', 'devis_delete' );
}

function devis_init(){
    //Create an instance of our package class...
    $testListTable = new Devis_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();

    include_once( 'admin/views/settings.php' );


}

function devis_detail(){
    global $wpdb;

    $devis = $_GET['devis'];

    $table_name = $wpdb->prefix . 'devis';

    $demande = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $devis");

    $ids = explode("-;-", $demande->destination);
    $output = "";

    foreach( $ids as $id) :
        if( $id != 0 && $id != "" && $id != NULL ) :
            $output .= get_the_title( $id ) . "<br/>";
        endif;

    endforeach;

    if($output == ""){
        $output = $demande->voyage;
    }

    include_once( 'admin/views/detail.php' );


}

function devis_submit(){
    global $wpdb;

    $devis = $_POST['devis'];
    $montant = $_POST['montant'];
    $langue = $_POST['langue'];

    $table_name = $wpdb->prefix . 'devis';

    $demande = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $devis");

    $token = sha1($demande->id . $demande->nom . $demande->prenom . $demande->tel . $demande->demande);

    $status = $montant ? 1 : 0;

    $sql = $wpdb->get_row("UPDATE $table_name SET montant = $montant, langue = '$langue', token = '$token', status = $status WHERE id = $devis");

    $wpdb->query($sql);

    wp_redirect( "?page=devis-detail&devis=$devis" );

}

function devis_delete(){
    global $wpdb;

    $devis = $_GET['devis'];

    $table_name = $wpdb->prefix . 'devis';

    $sql = "DELETE FROM $table_name WHERE id =  $devis";

    $wpdb->query($sql);

    wp_redirect( "?page=devis" );
    exit;


}

/* Front */

function devis_creation(){

    global $wpdb; //This is used only if making any database queries
    $table_name = $wpdb->prefix . 'devis';

    $values = $_POST;
    if($values['send'] && $values['email'] != "" && $values['tel']){
        $wpdb->insert(
            $table_name,
            array(
                "destination" => $values['products'] ? $values['products'] : "0",
                "voyage" => $values['destinations'] ? $values['destinations'] : "",
                "depart" => $values['date-sejour-depart'],
                "retour" => $values['date-sejour-retour'],
                "duree" => $values['duree-sejour'],
                "budget" => $values['budget-sejour'],
                "adulte" => $values['nbre-adulte'],
                "enfant" => $values['nbre-enfants'],
                "bebe" => $values['nbre-bebes'],
                "vol" => $values['vols-inclus'],
                "message" => $values['message'],
                "civ" => $values['civilite'],
                "nom" => $values['nom'],
                "prenom" => $values['prenom'],
                "email" => $values['email'],
                "cp" => $values['cp'],
                "ville" => $values['ville'],
                "tel" => $values['tel'],
                "status" => 0,
                "demande" => date("Y-m-d H:i"),
                "montant" => 0,
                "langue" => "fr",
                "token" => ""
            )
        );
        $ids = explode("-;-", $values['products'] ? $values['products'] : "0");
        $output = "";

        foreach( $ids as $id) :
            if( $id != 0 && $id != "" && $id != NULL ) :
                $output .= get_the_title( $id ) . "<br/>";
            endif;

        endforeach;

        if($output == ""){
            $output = $values['destinations'];
        }

        $newsletter = $values['newsletter'] ? "Oui" : "Non";
        $message = "Bonjour,<br/>
        [:fr]Vous avez reçu une nouvelle demande de devis de la part de [:en]Vous avez reçu une nouvelle demande de devis (Anglais) de la part de : <strong>{$values['civilite']} {$values['prenom']} {$values['nom']}</strong>.<br/>
        <br/>
        ---<br/>
        <h2>Récapitulatif de la demande </h2>
        <h3>Le(s) voyage(s)</h3>
        <em>Voyage(s) souhaité(s) :</em>
        <br/>
                {$output}
        <br/>
        <em>Date du départ :</em> {$values['date-sejour-depart']}<br/>
        <em>Date de retour :</em> {$values['date-sejour-retour']}<br/>
        <em>Durée du séjour :</em> {$values['duree-sejour']}<br/>
        <em>Nombre de participants :</em> {$values['nbre-adulte']} Adultes, {$values['nbre-enfants']} Enfants et {$values['nbre-bebes']} Bébés<br/>
                <em>Vols inclus :</em> {$values['vols-inclus']}<br/>
        <em>Description du voyage :</em><br/>
                {$values['message']}<br/>
        -----------------------------<br/>
        <h3>Coordonnées du client</h3>
        <em>Civilité :</em> {$values['civilite']}<br/>
        <em>Nom :</em> {$values['nom']}<br/>
        <em>Prénom :</em> {$values['prenom']}<br/>
        <em>Lieu de résidence :</em> {$values['cp']} / {$values['ville']}<br/>
        <em>Email :</em> {$values['email']}<br/>
        <em>Téléphone :</em> {$values['tel']}<br/>
        <br/>
        Inscription à la newsletter : {$newsletter}";
        $headers[] = "From: {$values['email']} <contact@rdvasie.com>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Reply-To: {$values['email']}";

        wp_mail( "contact@rdvasie.com", "[:fr]Rendez-vous avec l'Asie - Demande de Devis[:en]Rendez-vous avec l'Asie - Quotation", $message, $headers);
        wp_mail( "devis@rdvasie.com", "[:fr]Rendez-vous avec l'Asie - Demande de Devis[:en]Rendez-vous avec l'Asie - Quotation", $message, $headers);


        $message = "" . __('[:fr]Bonjour,<br/>
        Vous avez effectué une demande de devis sur rdvasie.com et nous vous en remercions !<br/>
        Nous allons vous répondre très prochainement.<br/>[:en]Hello, <br/>
        You have requested a quote on rdvasie.com and we thank you! <br/>
        We will respond to you very soon. ') . "
        </p>
        <h2>" . __('[:fr]Voici le récapitulatif de votre demande[:en]Here is the summary of your request') . "</h2>
        <h3>" . __('[:fr]Votre voyage[:en]Your journey') . "</h3>
        <em>" . __('[:fr]Voyage(s) souhaité(s) :[:en]Your Travel(s):') . "</em>
        <br/>
                {$output}
        <br/>
        <em>" . __('[:fr]Date de départ[:en]Departure date') . " :</em> {$values['date-sejour-depart']}<br/>
        <em>" . __('[:fr]Date de retour[:en]Return date') . " :</em> {$values['date-sejour-retour']}<br/>
        <em>" . __('[:fr]Durée de votre séjour [:en]Duration of your stay') . " :</em> {$values['duree-sejour']}<br/>
        <em>" . __('[:fr]Nombre de participants[:en]Number of participants') . " :</em> {$values['nbre-adulte']} " . __('[:fr]Adultes [:en]Adults') . ", {$values['nbre-enfants']} " . __('[:fr]Enfants[:en]Children ') . " " . __('[:fr]et[:en]and') . " {$values['nbre-bebes']} " . __('[:fr]Bébés[:en]Babies') . "<br/>
                <em>" . __('[:fr]Vols Inclus [:en]Flights Included') . " :</em> {$values['vols-inclus']}<br/>
        <em>" . __('[:fr]Votre projet de voyage[:en]Your travel plan') . " :</em><br/>
                {$values['message']}<br/>
                -----------------------------<br/>
        <h3>" . __('[:fr]Vos coordonnées [:en]Your contact details') . "</h3>
        <em>" . __('[:fr]Nom[:en]Last name') . " :</em> {$values['nom']}<br/>
        <em>" . __('[:fr]Prénom[:en]First Name') . " :</em> {$values['prenom']}<br/>
        <em>" . __('[:fr]Code postal / Ville[:en]Postal Code / City') . " :</em> {$values['cp']} / {$values['ville']}<br/>
        <em>Email :</em> {$values['email']}<br/>
        <em>" . __('[:fr]Téléphone[:en]Phone') . " :</em> {$values['tel']}<br/>
        <br/>
        ---------------------<br/>
        <a href=\"https://www.rdvasie.com\"><img src=\"https://www.rdvasie.com/wp-content/uploads/2018/11/rdv-asie.png\"></a><br/>
        " . __('[:fr]À très bientôt sur rdvasie.com ![:en]See you soon on rdvasie.com') . "<br/> 
        contact@rdvasie.com / www.rdvasie.com
        ";
        $headers = array();
        $headers[] = "From: <devis@rdvasie.com>";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Reply-To: devis@rdvasie.com";

        wp_mail( $values['email'], "" . __('[:fr]Votre demande de devis sur rdvasie.com[:en]Your Quote on rdvasie.com'), $message, $headers);


        wp_redirect("demande-devis-envoye");
        exit;
    }else{
        $error = "";
        if($values['send']){
            $error = "Merci d'indiquer votre email et votre numéro de téléphone";
        }
        include_once( 'views/devis.php' );
    }
}
add_shortcode('demande-devis', 'devis_creation');
