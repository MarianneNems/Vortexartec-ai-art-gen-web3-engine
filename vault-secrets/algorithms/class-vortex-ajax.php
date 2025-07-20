<?php
/**
 * AJAX handlers for VortexAIEngine agreements
 *
 * @package VortexAIEngine
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VortexAIEngine_Ajax {
    /** @var self|null */
    private static $instance = null;

    public static function getInstance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /** Register AJAX endpoints */
    private function hooks() {
        add_action( 'wp_ajax_vortex_record_agreement',     [ $this, 'record_agreement' ] );
        add_action( 'wp_ajax_nopriv_vortex_record_agreement', [ $this, 'record_agreement' ] );
        add_action( 'wp_ajax_vortex_validate_agreement',     [ $this, 'validate_agreement' ] );
        add_action( 'wp_ajax_nopriv_vortex_validate_agreement', [ $this, 'validate_agreement' ] );
    }

    /** Record agree/decline */
    public function record_agreement() {
        check_ajax_referer( 'vortex_agreement_nonce', 'nonce' );

        $user_id = get_current_user_id();
        $type    = sanitize_text_field( $_POST['agreement_type'] ?? '' );
        $agreed  = isset( $_POST['agreed'] ) ? (int) $_POST['agreed'] : 0;

        if ( ! $user_id || ! in_array( $type, [ 'seed', 'tola' ], true ) ) {
            wp_send_json_error( 'Invalid parameters', 400 );
        }

        global $wpdb;
        $table = ( 'tola' === $type )
            ? $wpdb->prefix . 'vortex_product_agreements'
            : $wpdb->prefix . 'vortex_user_agreements';

        $data = [
            ( 'tola' === $type ? 'post_id' : 'user_id' ) => ( 'tola' === $type )
                ? absint( $_POST['post_id'] ?? 0 )
                : $user_id,
            'agreement_type' => $type,
            'agreed'         => $agreed,
        ];

        $wpdb->insert( $table, $data );
        wp_send_json_success();
    }

    /** Check existing agreement */
    public function validate_agreement() {
        check_ajax_referer( 'vortex_agreement_nonce', 'nonce' );

        $user_id = get_current_user_id();
        $type    = sanitize_text_field( $_GET['agreement_type'] ?? '' );

        if ( ! $user_id || ! in_array( $type, [ 'seed', 'tola' ], true ) ) {
            wp_send_json_error( 'Invalid parameters', 400 );
        }

        global $wpdb;
        $table = ( 'tola' === $type )
            ? $wpdb->prefix . 'vortex_product_agreements'
            : $wpdb->prefix . 'vortex_user_agreements';

        $where = ( 'tola' === $type )
            ? [ 'post_id' => absint( $_GET['post_id'] ?? 0 ), 'agreement_type' => $type ]
            : [ 'user_id' => $user_id, 'agreement_type' => $type ];

        $agreed = (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT agreed FROM $table WHERE " . implode( ' AND ', array_map( function( $k ){ return "$k = %d"; }, array_keys($where) ) ) . " ORDER BY created_at DESC LIMIT 1",
            array_values( $where )
        ) );

        wp_send_json_success( [ 'agreed' => $agreed ] );
    }
}

// Kick things off
VortexAIEngine_Ajax::getInstance(); 