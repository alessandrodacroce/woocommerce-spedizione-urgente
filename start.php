<?php
/*
Plugin Name: Woocommerce Spedizione Urgente
Plugin URI: http://www.alessandrodacroce.it/progetto/plugin-woocommerce-spedizione-urgente/
Description: Aggiunge un costo extra ed indica che la spedizione deve essere urgente, solitamente nelle h24 successive
Author: Alessandro Dacroce <adacroce [AT] gmail [DOT] com>
Version: 0.0.2
Author URI: http://alessandrodacroce.it/
License: MIT
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

function WOO_spedizione_urgente() {
    if ( ! class_exists( 'WOO_spedizione_urgente_class' ) ) {
        class WOO_spedizione_urgente_class extends WC_Shipping_Method {
            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct() {
                $this->id                   = 'Spedizione Urgente';
                $this->title                = __( 'Spedizione Urgente' );
                $this->method_description   = __( 'Aggiunge un costo se l\'invio della merce è urgente' ); // 
                $this->init();
            }
    
            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init() {
                // Load the settings API
                $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
   
                // Save settings in admin if you have any defined
                add_action('woocommerce_update_options_shipping_' . $this->id  , array( $this, 'woo_su_admin_options' ) ); 
                add_action('woocommerce_update_options_shipping_methods', array(&$this, 'woo_su_admin_options'));
                
                
            }
            
            function init_form_fields() {
                
                $wc_spedizione_urgente = get_option('wc_spedizione_urgente');
                              
                $this->form_fields = array(
                    'stato' => array(
                        'title' => __( 'Attivare la spedizione urgente', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'Scrivere off per disattivare il plugin, altrimenti on per attivarlo', 'woocommerce' ),
                        'default' => ( isset($wc_spedizione_urgente["stato"]) && (strlen($wc_spedizione_urgente["stato"]) > 2 ) ) ? $wc_spedizione_urgente["stato"] :  __( 'on', 'woocommerce' )
                    ),
                    'title' => array(
                        'title' => __( 'Titolo del controllo', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'Questo è il testo che l\'utente vede quando può scegliere il tipo di spedizione', 'woocommerce' ),
                        'default' => ( isset($wc_spedizione_urgente["title"]) && (strlen($wc_spedizione_urgente["title"]) > 3 ) ) ? $wc_spedizione_urgente["title"] :  __( 'Spedizione Urgente', 'woocommerce' )
                    ),
                    'costo' => array(
                        'title' => __( 'Costo Spedizione Urgente', 'woocommerce' ),
                        'type'  => 'text',
                        'description' => __( 'Il costo che verrà applicato se la spedizione è di tipo urgente', 'woocommerce' ),
                        'default' => ( isset($wc_spedizione_urgente["costo"]) && (strlen($wc_spedizione_urgente["title"]) > 1 ) ) ? $wc_spedizione_urgente["costo"] :  __("4", 'woocommerce')
                    ),
                    'tax_costo' => array(
                        'title' => __( 'Attivare la tassazione', 'woocommerce' ),
                        'type' => 'text',
                        'description' => __( 'Se true, al costo sarà applicata la tassa impostata', 'woocommerce' ),
                        'default' => ( isset($wc_spedizione_urgente["tax_costo"]) && (strlen($wc_spedizione_urgente["tax_costo"]) > 3 ) ) ? $wc_spedizione_urgente["tax_costo"] :  __("false", 'woocommerce')
                    )
                );
                
            } // End init_form_fields()
            
            function woo_su_admin_options(){
               
                $args = array(
                    'stato'         => $_POST['woocommerce_Spedizione_Urgente_stato'],
                    'title'         => $_POST['woocommerce_Spedizione_Urgente_title'],
                    'costo'         => $_POST['woocommerce_Spedizione_Urgente_costo'],
                    'tax_costo'     => $_POST['woocommerce_Spedizione_Urgente_tax_costo']
                );
               
                update_option('wc_spedizione_urgente', $args );
                
            }
        }
    }
}
add_action( 'woocommerce_shipping_init', 'WOO_spedizione_urgente' );

function WOO_spedizione_urgente_method( $methods ) {
    $methods[] = 'WOO_spedizione_urgente_class'; 
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'WOO_spedizione_urgente_method' );

function custom_override_checkout_fields( $fields ) {
    
    // print "<pre>"; print_r ( $fields ); print "<pre>";
    
     $fields['billing']['spedizione_urgente'] = array(
        'label'         => __('Spedizione Urgente', 'woocommerce'),
        'placeholder'   => _x('Spedizione Urgente', 'placeholder', 'woocommerce'),
        'required'      => false,
        'type'          => 'checkbox',
        'class'         => array('form-row-wide'),
        'clear'         => true
    );
    
    return $fields;
}
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );


function woo_spedizione_urgente_my_custom_order_meta_keys( $keys ) {
    $spedizione = get_post_meta( $order->id, 'spedizione_urgente', true );
    if ( $spedizione == 1 ) // $keys[] = 'Spedizione Urgente'; // This will look for a custom field called 'Tracking Code' and add it to emails
        print "Spedizione urgente";
    return $keys;
}
add_filter('woocommerce_email_order_meta_keys', 'woo_spedizione_urgente_my_custom_order_meta_keys');

function woo_spedizione_urgente_custom_checkout_field_update_order_meta( $order_id ) {
    if ( ! empty( $_POST['spedizione_urgente'] ) ) {
        update_post_meta( $order_id, 'spedizione_urgente', sanitize_text_field( $_POST['spedizione_urgente'] ) );
    }
}
add_action( 'woocommerce_checkout_update_order_meta', 'woo_spedizione_urgente_custom_checkout_field_update_order_meta' );

function woo_spedizione_urgente_custom_checkout_field_display_admin_order_meta($order){
    $spedizione = get_post_meta( $order->id, 'spedizione_urgente', true );
    if ( $spedizione == 1 )
        echo '<p><strong>'.__('Spedizione Urgente').':</strong> </p>';
}
add_action( 'woocommerce_admin_order_data_after_billing_address', 'woo_spedizione_urgente_custom_checkout_field_display_admin_order_meta', 10, 1 );

function woo_spedizione_urgente_add_cart_fee( ) {
    global $woocommerce;
    
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;
    
    if ( $_COOKIE['spedizione_urgente'] == "1" ) {
        $su = get_option('wc_spedizione_urgente' );
        if ( $su["tax_costo"] == 'false' ) 
            $class_tax = 'NO-IMPONIBILE'; 
        $woocommerce->cart->add_fee(  $su["title"] , $su["costo"], $su["tax_costo"], $class_tax );
        $_COOKIE["spedizione_urgente"] = 0;
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'woo_spedizione_urgente_add_cart_fee' ); 

function woo_spedizione_urgente_aggiungi_js () {
   ?>
   
   <script type="text/javascript">
    jQuery( document ).ready(function( $ ) {
        
        function woo_spedizione_urgente_refresch_cart() {
            $('#spedizione_urgente').click(function(){
                
                if ( typeof wc_checkout_params === 'undefined' )
                    return false;
                
                document.cookie="spedizione_urgente="+jQuery('#spedizione_urgente').val();
                
                $( '.woocommerce-checkout-payment, .woocommerce-checkout-review-order-table' ).block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
    // alert($( 'form.checkout' ).serialize());
                var data = {
                    security:                   wc_checkout_params.update_order_review_nonce,
                    post_data:                  $( 'form.checkout' ).serialize()
                };
                
                $.ajax({
                    type:       'POST',
                    url:        wc_checkout_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'update_order_review' ),
                    data:       data,
                    success:    function( data ) {
                        // Always update the fragments
                        if ( data && data.fragments ) {
                            $.each( data.fragments, function ( key, value ) {
                                $( key ).replaceWith( value );
                                $( key ).unblock();
                            } );
                        }

                        // Check for error
                        if ( 'failure' === data.result ) {
    
                            var $form = $( 'form.checkout' );
    
                            if ( 'true' === data.reload ) {
                                window.location.reload();
                                return;
                            }
    
                            $( '.woocommerce-error, .woocommerce-message' ).remove();
    
                            // Add new errors
                            if ( data.messages ) {
                                $form.prepend( data.messages );
                            } else {
                                $form.prepend( data );
                            }
    
                            // Lose focus for all fields
                            $form.find( '.input-text, select' ).blur();
    
                            // Scroll to top
                            $( 'html, body' ).animate( {
                                scrollTop: ( $( 'form.checkout' ).offset().top - 100 )
                            }, 1000 );
    
                        }

                        document.cookie="spedizione_urgente=0";

                        // Trigger click e on selected payment method
                        if ( $( '.woocommerce-checkout' ).find( 'input[name=payment_method]:checked' ).size() === 0 ) {
                            $( '.woocommerce-checkout' ).find( 'input[name=payment_method]:eq(0)' ).attr( 'checked', 'checked' );
                        }
                        $( '.woocommerce-checkout' ).find( 'input[name=payment_method]:checked' ).eq(0).trigger( 'click' );
    
                        // Fire updated_checkout e
                        $( document.body ).trigger( 'updated_checkout' );
                    }

                });
                
                
            });
        }
        woo_spedizione_urgente_refresch_cart();
    });
    </script>
   
   <?php 
}
add_action('woocommerce_after_order_notes', 'woo_spedizione_urgente_aggiungi_js') ;
