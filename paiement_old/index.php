<?php
define( 'SHORTINIT', true );
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
require_once( 'trads.php' );

session_start();

function _get_key($cle){
    $hexStrKey  = substr($cle, 0, 38);
    $hexFinal   = "" . substr($cle, 38, 2) . "00";

    $cca0=ord($hexFinal);

    if ($cca0>70 && $cca0<97)
        $hexStrKey .= chr($cca0-23) . substr($hexFinal, 1, 1);
    else {
        if (substr($hexFinal, 1, 1)=="M")
            $hexStrKey .= substr($hexFinal, 0, 1) . "0";
        else
            $hexStrKey .= substr($hexFinal, 0, 2);
    }


    return pack("H*", $hexStrKey);
}

global $wpdb;

$token = $_GET['q'];

$table_name = $wpdb->prefix . 'devis';
$table_post = $wpdb->prefix . 'posts';

$demande = $wpdb->get_row("SELECT * FROM $table_name WHERE token = '$token'");
$ids = explode("-;-", $demande->destination);
$output = "";

$langue = $demande->langue;

$_SESSION['langue'] = $langue;

foreach( $ids as $id) :
    if( $id != 0 && $id != "" && $id != NULL ) :
        $post = $wpdb->get_row("SELECT * FROM $table_post WHERE ID = '$id'");
        if($post->post_title) {
            $output .= "<br/>" . $post->post_title;
        }
    endif;

endforeach;

if($output == ""){
    $output = "<br>" . $demande->voyage;
}

if($demande->status == 1) {



    $cle = _get_key("ED12A476000FDE6358BFF3B398B3998673F15495");

    $date = new DateTime('now', new \DateTimeZone('Europe/Paris'));
    $date = $date->format("d/m/Y:H:i:s");
    $reference = "RDVASIE-" . str_pad($demande->id, 3, "0", STR_PAD_LEFT);

    $key = hash_hmac("sha1", "7466577*{$date}*{$demande->montant}EUR*{$reference}*Rendez-vous avec l'Asie*3.0*FR*agencedevo*{$demande->email}**********", $cle);

    $sql = $wpdb->get_row("UPDATE $table_name SET mac = '$reference' WHERE id = {$demande->id}");

    $wpdb->query($sql);
}

?>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title[$langue]; ?></title>
    <style type="text/css">.paiement {
    text-align: center;
    font-family: arial;
    margin: 50px auto;
}
.top {
    border-bottom: 1px solid #ddd;
}
.coordonnees {
    display: block;
    background: #efefef;
    padding: 15px 0;
    width: 50%;
    margin: 20px auto;
}

</style>

    <!-- This site is optimized with the Yoast SEO plugin v9.3 - https://yoast.com/wordpress/plugins/seo/ -->
    <!-- Avis aux administrateurs : cette page n’affiche pas de méta description car elle n’en a pas. Vous pouvez donc soit l’ajouter spécifiquement pour cette page soit vous rendre dans vos réglages (SEO - Réglages SEO) pour configurer un modèle. -->
    <link rel="canonical" href="https://www.rdvasie.com/demande-de-devis/">

    <!-- / Yoast SEO plugin. -->
</head>

<body class="paiement">
<div>
  
<div class="top">   <img src="https://www.rdvasie.com/wp-content/uploads/2018/11/rdv-asie.png" alt="Rendez-vous avec l'Asie Logo"> <p></p></div>
  


    <div>
    </div>


    <div>
        <div>
            <div>
                <div>

                    <h1><?php echo $titre[$langue]; ?></h1>



                </div>

              

            </div>
        </div>
    </div>

    <div id="main" role="main" class="clearfix " style="">
        <div class="fusion-row" style="">
            <section id="content" style="width: 100%;">
                <div id="post-47" class="post-47 page type-page status-publish hentry">
                   
                    <div class="fusion-row">
                        <div class="col-lg-4" style="border: 1px dashed #ddd; padding: 15px 15px 35px 15px; margin-top: 25px;">
                        <div role="form" dir="ltr" lang="fr-FR">
                            <div class="screen-reader-response"></div>
                            <h2><?php echo $voyage[$langue]; ?></h2>
                          
                            <strong><?php echo $dest[$langue]; ?> :</strong> <?php echo $output; ?><br/><br/>
                            <?php echo $devis[$langue]; ?> : <?php echo $demande->demande; ?><br/>
                            <?php echo $depart[$langue]; ?>  : <?php echo $demande->depart; ?><br/>
                            <?php echo $retour[$langue]; ?>  : <?php echo $demande->retour; ?><br/>
                            <!-- Durée du séjour : <?php echo $demande->duree; ?>-->
                           <!-- Budget : <?php echo $demande->budget; ?><br/>-->
                           <strong><?php echo $montant[$langue]; ?> :</strong> <?php echo $demande->montant; ?> €
                           <!-- Nombre d'adulte(s) : <?php echo $demande->adulte; ?>-->
                            <!--Nombre d'enfant(s) : <?php echo $demande->enfant; ?>-->
                             <!--  <?php echo $demande->vol; ?>-->
                           <div class="coordonnees">
                            <h2 style="margin-top: 0"><?php echo $coord[$langue]; ?></h2>
                            <strong><?php echo $demande->civ; ?> <?php echo $demande->nom; ?> <?php echo $demande->prenom; ?></strong><br/>
                           <!--  Adresse : <?php echo $demande->cp; ?> <?php echo $demande->ville; ?>-->
                               <?php echo $tel[$langue]; ?> : <?php echo $demande->tel; ?><br/>

</div>
                            <!--Commentaire : <?php echo $demande->message; ?>--></div></div>
                        
                            <div class="col-lg-8" style="text-align: center">
                                <?php if($demande->status == 1) : ?>
                                <p style="font-size: 20px"><?php echo $montant[$langue]; ?> : <span style="color: #de5a09;
    display: block;
    font-size: 33px;
    margin: 10px 0 0 0;"><?php echo $demande->montant; ?>  €</span></p>

                                  <form method="post" name="MoneticoFormulaire" target="_top" action="https://p.monetico-services.com/paiement.cgi">
                                <input type="hidden" name="version" value="3.0">
                                <input type="hidden" name="TPE" value="7466577">
                                <input type="hidden" name="date" value="<?php echo $date; ?>">
                                <input type="hidden" name="montant" value="<?php echo $demande->montant; ?>EUR">
                                <input type="hidden" name="reference" value="<?php echo $reference; ?>">
                                <input type="hidden" name="MAC" value="<?php echo $key; ?>">
                                <input type="hidden" name="url_retour" value="https://www.rdvasie.com">
                                <input type="hidden" name="url_retour_ok" value="https://www.rdvasie.com/paiement/success.php">
                                <input type="hidden" name="url_retour_err" value="https://www.rdvasie.com/paiement/cancel.php">
                                <input type="hidden" name="lgue" value="FR">
                                <input type="hidden" name="societe" value="agencedevo">
                                <input type="hidden" name="texte-libre" value="Rendez-vous avec l'Asie">
                                <input type="hidden" name="mail" value="<?php echo $demande->email; ?>">
                                <div class="col-xs-12 col-sm-offset-3 col-sm-6">
                                    <div class="triangle-down"></div>
                                </div>
                                <div class="text-center">
                                    <input class="btn" style="border:none; background: #e05a00;padding: 10px 15px; margin: 0 auto;font-weight: normal; color: #fff;font-size: 22px; cursor: pointer;" type="submit" name="bouton" value="<?php echo $acces[$langue]; ?>">
                                </div>

                            </form>


                            <h3><?php echo $cic[$langue]; ?></h3>
                            <img src="https://www.rdvasie.com/wp-content/uploads/2019/01/paiement-securise.png" style="width: 200px; margin:0 auto">
                                <?php else : ?>
                                    <h2><?php echo $traite[$langue]; ?>.</h2>
                                    <p><?php echo $erreur[$langue]; ?></p>
                                <?php endif; ?>
                        </div>

                          
                        </div><div class="fusion-fullwidth fullwidth-box nonhundred-percent-fullwidth non-hundred-percent-height-scrolling" style="background-color: rgba(255,255,255,0);background-position: center center;background-repeat: no-repeat;padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;"><div class="fusion-builder-row fusion-row "><div class="fusion-layout-column fusion_builder_column fusion_builder_column_1_1  fusion-one-full fusion-column-first fusion-column-last 1_1" style="margin-top:0px;margin-bottom:20px;">
                                    <div class="fusion-column-wrapper" style="padding: 0px 0px 0px 0px;background-position:left top;background-repeat:no-repeat;-webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover;" data-bg-url="">
                                        <div class="fusion-text">
                                        </div><div class="fusion-clearfix"></div>

                                    </div>
                                </div></div></div>
                    </div>
                </div>
            </section>

        </div>  <!-- fusion-row -->
    </div>  <!-- #main -->





</div> <!-- wrapper -->






<script type="text/javascript">
    jQuery(document).ready(function($) {

        $("body").on("click","a.continue-btn",function(event){
            event.preventDefault();
            $(this).parent("p.enq-response").fadeOut('slow');
        });

        $("body").on("click",".product-enquiry-click.click-link-btn", function(event){

            event.preventDefault();

            var currentClickedBtn = $(this);

            currentClickedBtn.after('<div class="gts-processing"></div>');

            var prodcutTitle = $(this).attr("data-title");
            var prodcutID = $(this).attr("data-id");
            var data = {
                'action': 'enqbtn_click_action',
                'clicked_product': prodcutTitle,
                'clicked_product_id': prodcutID
            };

            var ajaxurl = "https://www.rdvasie.com/wp-admin/admin-ajax.php";

            $.post(ajaxurl, data, function(response) {

                var obj = jQuery.parseJSON( response );

                //If Response is Positive: 1
                if( obj.result === 1 ){
                    if( obj.alert_type === 'inline'){

                        currentClickedBtn.addClass('added');

                        var currentProductClass = 'product-btn-' + obj.product_id;
                        var msgToDisplay = '<strong>\"' + obj.product + ' (' + obj.product_id + ')\"</strong> added to quotation request form.';
                        var continueButton = '<a href="#" class="continue-btn">'+obj.continue_text+'</a>';
                        var formButton = '<a href="'+obj.btn_url+'" class="form-btn">'+obj.btn_text+'</a>';

                        var formattedMag = continueButton +'<br>'+ formButton;

                        $('body .gts-processing').remove();
                        ;
                        $( "."+currentProductClass+"" ).after( "<p class='enq-response'>"+formattedMag+"</p>" );

                        $('.enq-cart-block').html(obj.output);
                    }
                    else{

                        $('.enq-cart-block').html(obj.output);

                        $('body .gts-processing').remove();

                        alert('"' + obj.product + ' (' + obj.product_id + ')" added to quotation request form.');
                    }
                }
                if( obj.result === 0 ){
                    alert('Opps! Something wrong happened!');
                }
                //If Items Already Exists In List: 3
                if( obj.result === 3 ){
                    if( obj.alert_type === 'inline'){
                        var currentProductClass = 'product-btn-' + obj.product_id;
                        var msgToDisplay = 'Vous avez déjà sélectionné ce produit';
                        var continueButton = '<a href="#" class="continue-btn">'+obj.continue_text+'</a>';
                        var formattedMag = msgToDisplay +'<br>'+ continueButton +'<br>'+ formButton;

                        $('body .gts-processing').remove();

                        $( "."+currentProductClass+"" ).after( "<p class='enq-response'>"+msgToDisplay+"</p>" );
                        $("."+currentProductClass+"").siblings('.enq-response').delay(3000).fadeOut();
                    }
                    else{

                        $('body .gts-processing').remove();

                        alert('Vous avez déjà sélectionné ce produit.');
                    }
                }
            });
        });

    });
</script>
<script type="text/javascript">
    jQuery( document ).ready( function() {
        var ajaxurl = 'https://www.rdvasie.com/wp-admin/admin-ajax.php';
        if ( 0 < jQuery( '.fusion-login-nonce' ).length ) {
            jQuery.get( ajaxurl, { 'action': 'fusion_login_nonce' }, function( response ) {
                jQuery( '.fusion-login-nonce' ).html( response );
            });
        }
    });
</script>
<script type="text/javascript">
    var c = document.body.className;
    c = c.replace(/woocommerce-no-js/, 'woocommerce-js');
    document.body.className = c;
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/simple-tooltips/zebra_tooltips.js?ver=5.0.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-includes/js/admin-bar.min.js?ver=5.0.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var wpcf7 = {"apiSettings":{"root":"https:\/\/www.rdvasie.com\/wp-json\/contact-form-7\/v1","namespace":"contact-form-7\/v1"}};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/contact-form-7/includes/js/scripts.js?ver=5.1.1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/woocommerce/assets/js/jquery-blockui/jquery.blockUI.min.js?ver=2.70"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var wc_add_to_cart_params = {"ajax_url":"\/wp-admin\/admin-ajax.php","wc_ajax_url":"\/?wc-ajax=%%endpoint%%","i18n_view_cart":"Voir le panier","cart_url":"https:\/\/www.rdvasie.com\/panier\/","is_cart":"","cart_redirect_after_add":"no"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/woocommerce/assets/js/frontend/add-to-cart.min.js?ver=3.5.3"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/woocommerce/assets/js/js-cookie/js.cookie.min.js?ver=2.1.4"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var woocommerce_params = {"ajax_url":"\/wp-admin\/admin-ajax.php","wc_ajax_url":"\/?wc-ajax=%%endpoint%%"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/woocommerce/assets/js/frontend/woocommerce.min.js?ver=3.5.3"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var wc_cart_fragments_params = {"ajax_url":"\/wp-admin\/admin-ajax.php","wc_ajax_url":"\/?wc-ajax=%%endpoint%%","cart_hash_key":"wc_cart_hash_ae6dfcfd2bc12a7db05ff2126e76a58b","fragment_name":"wc_fragments_ae6dfcfd2bc12a7db05ff2126e76a58b"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/woocommerce/assets/js/frontend/cart-fragments.min.js?ver=3.5.3"></script>
<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?render=6LdBkIYUAAAAAIUUywJI7v-MbB005GFDKkSRaoyW&amp;ver=3.0"></script>
<!--[if IE 9]>
<script type='text/javascript' src='https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-ie9.js?ver=1'></script>
<![endif]-->
<script type="text/javascript">
    /* <![CDATA[ */
    var ajaxsearchlite = {"ajaxurl":"https:\/\/www.rdvasie.com\/wp-admin\/admin-ajax.php","backend_ajaxurl":"https:\/\/www.rdvasie.com\/wp-admin\/admin-ajax.php","js_scope":"jQuery"};
    var ASL = {"ajaxurl":"https:\/\/www.rdvasie.com\/wp-admin\/admin-ajax.php","backend_ajaxurl":"https:\/\/www.rdvasie.com\/wp-admin\/admin-ajax.php","js_scope":"jQuery","detect_ajax":"0","scrollbar":"1","js_retain_popstate":"0","version":"4730"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/ajax-search-lite/js/min/jquery.ajaxsearchlite.min.js?ver=4.7.20"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/modernizr.js?ver=3.3.1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.fitvids.js?ver=1.1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionVideoGeneralVars = {"status_vimeo":"1","status_yt":"1"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/fusion-video-general.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionLightboxVideoVars = {"lightbox_video_width":"1280","lightbox_video_height":"720"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.ilightbox.js?ver=2.2.3"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.mousewheel.js?ver=3.0.6"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionLightboxVars = {"status_lightbox":"1","lightbox_gallery":"1","lightbox_skin":"metro-white","lightbox_title":"1","lightbox_arrows":"1","lightbox_slideshow_speed":"5000","lightbox_autoplay":"","lightbox_opacity":"0.9","lightbox_desc":"1","lightbox_social":"1","lightbox_deeplinking":"1","lightbox_path":"vertical","lightbox_post_images":"1","lightbox_animation_speed":"Normal"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-lightbox.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/imagesLoaded.js?ver=3.1.8"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/isotope.js?ver=3.0.4"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/packery.js?ver=2.0.0"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaPortfolioVars = {"lightbox_behavior":"all","infinite_finished_msg":"<em>All items displayed.<\/em>","infinite_blog_text":"<em>Loading the next set of posts...<\/em>","content_break_point":"800"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-core/js/min/avada-portfolio.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.infinitescroll.js?ver=2.1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-core/js/min/avada-faqs.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/cssua.js?ver=2.1.28"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.waypoints.js?ver=2.0.3"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-waypoints.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionAnimationsVars = {"disable_mobile_animate_css":"0"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-animations.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/library/jquery.countTo.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.easyPieChart.js?ver=2.1.7"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.appear.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-counters-circle.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/library/jquery.countdown.js?ver=1.0"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-countdown.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.cycle.js?ver=3.0.3"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionTestimonialVars = {"testimonials_speed":"4000"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-testimonials.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-syntax-highlighter.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionEqualHeightVars = {"content_break_point":"800"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-equal-heights.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-content-boxes.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-events.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionMapsVars = {"admin_ajax":"https:\/\/www.rdvasie.com\/wp-admin\/admin-ajax.php"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.fusion_maps.js?ver=2.2.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-google-map.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-progress.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/bootstrap.modal.js?ver=3.1.1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-modal.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionBgImageVars = {"content_break_point":"800"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-column-bg-image.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-column.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/bootstrap.collapse.js?ver=3.1.1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-toggles.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/bootstrap.transition.js?ver=3.3.6"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/bootstrap.tab.js?ver=3.1.1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionTabVars = {"content_break_point":"800"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-tabs.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-flip-boxes.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionRecentPostsVars = {"infinite_loading_text":"<em>Loading the next set of posts...<\/em>","infinite_finished_msg":"<em>All items displayed.<\/em>","slideshow_autoplay":"1","slideshow_speed":"7000","pagination_video_slide":"","status_yt":"1"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-recent-posts.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionCountersBox = {"counter_box_speed":"1000"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-counters-box.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/library/jquery.event.move.js?ver=2.0"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-image-before-after.js?ver=1.0"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.fade.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.requestAnimationFrame.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/fusion-parallax.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionVideoBgVars = {"status_vimeo":"1","status_yt":"1"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/fusion-video-bg.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionContainerVars = {"content_break_point":"800","container_hundred_percent_height_mobile":"0","is_sticky_header_transparent":"0"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-container.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-gallery.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/library/Chart.js?ver=2.7.1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-chart.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-title.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/vimeoPlayer.js?ver=2.2.1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionVideoVars = {"status_vimeo":"1"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-builder/assets/js/min/general/fusion-video.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.hoverintent.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-vertical-menu-widget.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/bootstrap.tooltip.js?ver=3.3.5"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/bootstrap.popover.js?ver=3.3.5"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.carouFredSel.js?ver=6.2.1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.easing.js?ver=1.3"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.flexslider.js?ver=2.2.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.hoverflow.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.placeholder.js?ver=2.0.7"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/library/jquery.touchSwipe.js?ver=1.6.6"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-alert.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionCarouselVars = {"related_posts_speed":"2500","carousel_speed":"2500"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-carousel.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionFlexSliderVars = {"status_vimeo":"1","page_smoothHeight":"false","slideshow_autoplay":"1","slideshow_speed":"7000","pagination_video_slide":"","status_yt":"1","flex_smoothHeight":"false"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-flexslider.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-popover.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-tooltip.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-sharing-box.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionBlogVars = {"infinite_blog_text":"<em>Loading the next set of posts...<\/em>","infinite_finished_msg":"<em>All items displayed.<\/em>","slideshow_autoplay":"1","slideshow_speed":"7000","pagination_video_slide":"","status_yt":"1","lightbox_behavior":"all","blog_pagination_type":"Pagination","flex_smoothHeight":"false"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-blog.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-button.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-general-global.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionIe1011Vars = {"form_bg_color":"#ffffff"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-ie1011.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaHeaderVars = {"header_position":"top","header_layout":"v2","header_sticky":"1","header_sticky_type2_layout":"menu_only","side_header_break_point":"800","header_sticky_mobile":"0","header_sticky_tablet":"0","mobile_menu_design":"modern","sticky_header_shrinkage":"1","nav_height":"125","nav_highlight_border":"3","nav_highlight_style":"bar","logo_margin_top":"10px","logo_margin_bottom":"10px","layout_mode":"wide","header_padding_top":"0px","header_padding_bottom":"0px","offset_scroll":"framed"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-header.js?ver=5.7.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaMenuVars = {"header_position":"Top","logo_alignment":"Center","header_sticky":"1","side_header_break_point":"800","mobile_menu_design":"modern","dropdown_goto":"Go to...","mobile_nav_cart":"Shopping Cart","mobile_submenu_open":"Open Sub Menu","mobile_submenu_close":"Close Sub Menu","submenu_slideout":"1"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-menu.js?ver=5.7.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionScrollToAnchorVars = {"content_break_point":"800","container_hundred_percent_height_mobile":"0"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-scroll-to-anchor.js?ver=1"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var fusionTypographyVars = {"site_width":"1100px","typography_responsive":"","typography_sensitivity":"0.6","typography_factor":"1.5","elements":"h1, h2, h3, h4, h5, h6"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/includes/lib/assets/min/js/general/fusion-responsive-typography.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/library/bootstrap.scrollspy.js?ver=3.3.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaCommentVars = {"title_style_type":"double solid","title_margin_top":"0px","title_margin_bottom":"31px"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-comments.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-general-footer.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-quantity.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-scrollspy.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-select.js?ver=5.7.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaSidebarsVars = {"header_position":"top","header_layout":"v2","header_sticky":"1","header_sticky_type2_layout":"menu_only","side_header_break_point":"800","header_sticky_tablet":"0","sticky_header_shrinkage":"1","nav_height":"125","content_break_point":"800"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-sidebars.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/library/jquery.sticky-kit.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-tabs-widget.js?ver=5.7.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var toTopscreenReaderText = {"label":"Go to Top"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/library/jquery.toTop.js?ver=1.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaToTopVars = {"status_totop_mobile":"0"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-to-top.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-drop-down.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-contact-form-7.js?ver=5.7.2"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/library/jquery.elasticslider.js?ver=5.7.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaElasticSliderVars = {"tfes_autoplay":"1","tfes_animation":"sides","tfes_interval":"3000","tfes_speed":"800","tfes_width":"150"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-elastic-slider.js?ver=5.7.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaWooCommerceVars = {"order_actions":"Details","title_style_type":"double solid","woocommerce_shop_page_columns":"3","woocommerce_checkout_error":"Not all fields have been filled in correctly.","woocommerce_single_gallery_size":"500","related_products_heading_size":"3"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/themes/Avada/assets/min/js/general/avada-woocommerce.js?ver=5.7.2"></script>
<script type="text/javascript">
    /* <![CDATA[ */
    var avadaFusionSliderVars = {"side_header_break_point":"800","slider_position":"below","header_transparency":"0","mobile_header_transparency":"0","header_position":"Top","content_break_point":"800","status_vimeo":"1"};
    /* ]]> */
</script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-content/plugins/fusion-core/js/min/avada-fusion-slider.js?ver=1"></script>
<script type="text/javascript" src="https://www.rdvasie.com/wp-includes/js/wp-embed.min.js?ver=5.0.2"></script>
<script type="text/javascript">
    ( function( grecaptcha, sitekey ) {

        var wpcf7recaptcha = {
            execute: function() {
                grecaptcha.execute(
                    sitekey,
                    { action: 'homepage' }
                ).then( function( token ) {
                    var forms = document.getElementsByTagName( 'form' );

                    for ( var i = 0; i < forms.length; i++ ) {
                        var fields = forms[ i ].getElementsByTagName( 'input' );

                        for ( var j = 0; j < fields.length; j++ ) {
                            var field = fields[ j ];

                            if ( 'g-recaptcha-response' === field.getAttribute( 'name' ) ) {
                                field.setAttribute( 'value', token );
                                break;
                            }
                        }
                    }
                } );
            }
        };

        grecaptcha.ready( wpcf7recaptcha.execute );

        document.addEventListener( 'wpcf7submit', wpcf7recaptcha.execute, false );

    } )( grecaptcha, '6LdBkIYUAAAAAIUUywJI7v-MbB005GFDKkSRaoyW' );
</script>

<script type="text/javascript">
    jQuery(function() {
        jQuery('.menu-item-70 .tooltips > a').each(function () {
            jQuery(this).addClass('tooltips').closest('li').removeClass('tooltips');
        });

        jQuery(".tooltips img").closest(".tooltips").css("display", "inline-block");

        new jQuery.Zebra_Tooltips(jQuery('.tooltips').not('.custom_m_bubble'), {
            'background_color':     '#000000',
            'color':				'#ffffff',
            'max_width':  300,
            'opacity':    .7,
            'position':    'center'
        });


    });
</script>
<!--[if lte IE 8]>
<script type="text/javascript">
    document.body.className = document.body.className.replace( /(^|\s)(no-)?customize-support(?=\s|$)/, '' ) + ' no-customize-support';
</script>
<![endif]-->
<!--[if gte IE 9]><!-->
<script type="text/javascript">
    (function() {
        var request, b = document.body, c = 'className', cs = 'customize-support', rcs = new RegExp('(^|\\s+)(no-)?'+cs+'(\\s+|$)');

        request = true;

        b[c] = b[c].replace( rcs, ' ' );
        // The customizer requires postMessage and CORS (if the site is cross domain)
        b[c] += ( window.postMessage && request ? ' ' : ' no-' ) + cs;
    }());
</script>
<!--<![endif]-->
<div id="ajaxsearchlitesettings1" class="searchsettings wpdreams_asl_settings asl_w asl_s asl_s_1 asl_an_fadeOutDrop" style="animation-duration: 300ms; visibility: hidden; opacity: 0; display: none;">
    <form name="options" autocomplete="off">


        <fieldset class="asl_sett_scroll">
            <legend style="display: none;">Generic selectors</legend>
            <div class="asl_option_inner hiddend">
                <input type="hidden" name="qtranslate_lang" id="qtranslate_lang" value="0">
            </div>



            <div class="asl_option">
                <div class="asl_option_inner">
                    <input type="checkbox" value="checked" id="set_exactonly1" title="Exact matches only" name="set_exactonly">
                    <label for="set_exactonly1">Exact matches only</label>
                </div>
                <div class="asl_option_label">
                    Exact matches only                </div>
            </div>
            <div class="asl_option">
                <div class="asl_option_inner">
                    <input type="checkbox" value="None" id="set_intitle1" title="Search in title" name="set_intitle" checked="checked">
                    <label for="set_intitle1">Search in title</label>
                </div>
                <div class="asl_option_label">
                    Search in title                </div>
            </div>
            <div class="asl_option">
                <div class="asl_option_inner">
                    <input type="checkbox" value="None" id="set_incontent1" title="Search in content" name="set_incontent">
                    <label for="set_incontent1">Search in content</label>
                </div>
                <div class="asl_option_label">
                    Search in content                </div>
            </div>
            <div class="asl_option_inner hiddend">
                <input type="checkbox" value="None" id="set_inexcerpt1" title="Search in excerpt" name="set_inexcerpt">
                <label for="set_inexcerpt1">Search in excerpt</label>
            </div>

            <div class="asl_option">
                <div class="asl_option_inner">
                    <input type="checkbox" value="None" id="set_inposts1" title="Search in posts" name="set_inposts">
                    <label for="set_inposts1">Search in posts</label>
                </div>
                <div class="asl_option_label">
                    Search in posts                </div>
            </div>
            <div class="asl_option">
                <div class="asl_option_inner">
                    <input type="checkbox" value="None" id="set_inpages1" title="Search in pages" name="set_inpages">
                    <label for="set_inpages1">Search in pages</label>
                </div>
                <div class="asl_option_label">
                    Search in pages                </div>
            </div>
            <div class="asl_option asl-o-last">
                <div class="asl_option_inner">
                    <input type="checkbox" value="product" id="1customset_11" title="product" name="customset[]" checked="checked">
                    <label for="1customset_11">product</label>
                </div>
                <div class="asl_option_label">
                    product                    </div>
            </div>
        </fieldset>
    </form>
</div><div id="ajaxsearchliteres1" class="vertical wpdreams_asl_results asl_w asl_r asl_r_1" style="animation-duration: 300ms; opacity: 0;">


    <div class="results mCustScr _mCSap_1 mCS_no_scrollbar"><div id="mCSBap_1" class="mCustomScrollBox mCS-light mCSBap_vertical mCSBap_inside" style="max-height: 0px;" tabindex="0"><div id="mCSBap_1_container" class="mCSBap_container mCS_y_hidden mCS_no_scrollbar_y" style="position:relative; top:0; left:0;" dir="ltr">


                <div class="resdrg">
                </div>


            </div><div id="mCSBap_1_scrollbar_vertical" class="mCSBap_scrollTools mCSBap_1_scrollbar mCS-light mCSBap_scrollTools_vertical" style="display: none;"><a href="#" class="mCSBap_buttonUp">Scroll up the results</a><div class="mCSBap_draggerContainer"><div id="mCSBap_1_dragger_vertical" class="mCSBap_dragger" style="position: absolute; min-height: 30px; top: 0px;"><div class="mCSBap_dragger_bar" style="line-height: 30px;"></div></div><div class="mCSBap_draggerRail"></div></div><a href="#" class="mCSBap_buttonDown">Scroll down the results</a></div></div></div>



</div><div class="to-top-container"><a href="#" id="toTop" style="display: none;"><span id="toTopHover"></span><span class="screen-reader-text">Go to Top</span></a></div></body></html>

