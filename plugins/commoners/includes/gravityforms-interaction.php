<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

////////////////////////////////////////////////////////////////////////////////
// Constants and functions for interacting with GravityForms.
// We handle forms, fields and entries here.
// Note that we don't handle multiple applications by the same user yet,
// but code that sorts by date before returning the zeroth item is boilerplate
// to handle that feature.
////////////////////////////////////////////////////////////////////////////////

// Applicant initial application fields

define( 'COMMONERS_GF_AGREE_TO_TERMS', 'Agree To Terms' );
define( 'COMMONERS_GF_APPLICANT_DETAILS', 'Applicant Details' );
define( 'COMMONERS_GF_CHOOSE_VOUCHERS', 'Choose Vouchers' );

// Member vouching for applicant

define( 'COMMONERS_GF_VOUCH', 'Vouch For Applicant' );

// Admin approval of applicant

define( 'COMMONERS_GF_PRE_APPROVAL', 'Pre Approval' );
define( 'COMMONERS_GF_FINAL_APPROVAL', 'Final Approval' );

// Individual fields in forms

define( 'COMMONERS_GF_DETAILS_NAME', '1' );
define( 'COMMONERS_GF_DETAILS_BIO', '2' );
define( 'COMMONERS_GF_DETAILS_AREAS_OF_INTEREST', '5' );
define( 'COMMONERS_GF_DETAILS_LANGUAGES', '6' );
define( 'COMMONERS_GF_DETAILS_LOCATION', '7' );
define( 'COMMONERS_GF_DETAILS_NATIONALITY', '8' );
define( 'COMMONERS_GF_DETAILS_SOCIAL_MEDIA_URLS', '9' );
define( 'COMMONERS_GF_DETAILS_AVATAR_FILE', '10' );

define( 'COMMONERS_GF_VOUCH_APPLICANT_ID', 'applicant_id' );
define( 'COMMONERS_GF_VOUCH_APPLICANT_ID_FIELD', '7' );
define( 'COMMONERS_GF_VOUCH_DO_YOU_VOUCH', '3' );
define( 'COMMONERS_GF_VOUCH_REASON', '4' );

define( 'COMMONERS_GF_PRE_APPROVAL_APPROVE_MEMBERSHIP_APPLICATION', '1' );
define( 'COMMONERS_GF_PRE_APPROVAL_APPLICANT_ID_PARAMETER', 'applicant_id' );
define( 'COMMONERS_GF_PRE_APPROVAL_APPLICANT_ID', '4' );

define( 'COMMONERS_GF_FINAL_APPROVAL_APPROVE_MEMBERSHIP_APPLICATION', '2' );
define( 'COMMONERS_GF_FINAL_APPROVAL_APPLICANT_ID_PARAMETER', 'applicant_id' );
define( 'COMMONERS_GF_FINAL_APPROVAL_APPLICANT_ID', '4' );

// Field values that we need to check

define( 'COMMONERS_GF_VOUCH_DO_YOU_VOUCH_YES', 'Yes' );
define( 'COMMONERS_GF_PRE_APPROVAL_APPROVED_YES', 'Yes' );
define( 'COMMONERS_GF_FINAL_APPROVAL_APPROVED_YES', 'Yes' );

// The field IDs containing user names in the "Choose Vouchers" form

define(
    'COMMONERS_GF_VOUCH_VOUCHER_FIELDS',
    [ '1', '2', '3', '4', '5' ]
);


////////////////////////////////////////////////////////////////////////////////
// Vouching form
////////////////////////////////////////////////////////////////////////////////

// Find the applicant's choices of vouchers in Gravity Forms

function commoners_vouching_request_entry ( $applicant_id ) {
    $form_id = RGFormsModel::get_form_id( COMMONERS_GF_CHOOSE_VOUCHERS );
    $search_criteria = array();
    $search_criteria['field_filters'][]
        = array(
            'key' =>  'created_by',
            'value' => $applicant_id
        );
    $entries = GFAPI::get_entries(
        $form_id,
        $search_criteria,
        array(
            array(
                'key' => 'date',
                'direction' => 'DESC',
                'is_numeric' => false
            )
        )
    );

    return $entries[ 0 ];
}

// Has the voucher already responded to applicant's vouching request?

function commoners_vouching_request_open ( $applicant_id, $voucher_id ) {
    $form_id = RGFormsModel::get_form_id( COMMONERS_GF_VOUCH );
    $search_criteria = array();
    $search_criteria['field_filters'][]
        = array(
            'key' =>  'created_by',
            'value' => $voucher_id
        );
    $search_criteria['field_filters'][]
        = array(
            'key' =>  COMMONERS_GF_VOUCH_APPLICANT_ID_FIELD,
            'value' => $applicant_id
        );
    $entries = GFAPI::get_entries(
        $form_id,
        $search_criteria
    );
    return $entries == [];
}

////////////////////////////////////////////////////////////////////////////////
// Getting an applicant's various details
////////////////////////////////////////////////////////////////////////////////

// Get the applicant details for the id

function commoners_application_details ( $applicant_id ) {
    $form_id = RGFormsModel::get_form_id( COMMONERS_GF_APPLICANT_DETAILS );
    $search_criteria = array();
    $search_criteria['field_filters'][]
        = array(
            'key' =>  'created_by',
            'value' => $applicant_id
        );
    $entries = GFAPI::get_entries(
        $form_id,
        $search_criteria,
        array(
            array(
                'key' => 'date',
                'direction' => 'DESC',
                'is_numeric' => false
            )
        )
    );

    return $entries[0];
}

// Get the list of vouchers for the id.
// This is the applicant's voucher choices from the form.

function commoners_application_vouchers ( $applicant_id ) {
    $form_id = RGFormsModel::get_form_id( COMMONERS_GF_CHOOSE_VOUCHERS );
    $search_criteria = array();
    $search_criteria['field_filters'][]
        = array(
            'key' =>  'created_by',
            'value' => $applicant_id
        );
    $entries = GFAPI::get_entries(
        $form_id,
        $search_criteria,
        array(
            array(
                'key' => 'date',
                'direction' => 'DESC',
                'is_numeric' => false
            )
        )
    );

    return $entries[0];
}

function commoners_application_vouchers_users_ids ( $applicant_id ) {
    $vouchers_entry = commoners_application_vouchers ( $applicant_id );
    $users = array();
    foreach ( COMMONERS_GF_VOUCH_VOUCHER_FIELDS as $field ) {
        $voucher_id = trim( $vouchers_entry[ $field ] );
        if ( $voucher_id ) {
            $users[] = $voucher_id;
        }
    }
    return $users;
}

function commoners_application_vouchers_users ( $applicant_id ) {
    $voucher_ids = commoners_application_vouchers_users_ids ( $applicant_id );
    $users = array();
    foreach ( $voucher_ids as $voucher_id) {
        $users[] = get_user_by('ID', $voucher_id);
    }
    return $users;
}

// Get the list of submitted vouches for the user

function commoners_application_vouches ( $applicant_id ) {
    $form_id = RGFormsModel::get_form_id( COMMONERS_GF_VOUCH );
    $search_criteria = array();
    $search_criteria['field_filters'][]
        = array(
            'key' =>  COMMONERS_GF_VOUCH_APPLICANT_ID_FIELD,
            'value' => $applicant_id
        );
    $entries = GFAPI::get_entries(
        $form_id,
        $search_criteria,
        array(
            array(
                'key' => 'date',
                'direction' => 'ASC',
                'is_numeric' => false
            )
        )
    );
    return $entries;
}

// Count the number of vouches received

function commoners_application_vouches_counts ( $applicant_id ) {
    $yes = 0;
    $no = 0;
    $vouches = commoners_application_vouches( $applicant_id );
    foreach ($vouches as $vouch) {
        $did_they = $vouch[ COMMONERS_GF_VOUCH_DO_YOU_VOUCH ];
        if ( $did_they == COMMONERS_GF_VOUCH_DO_YOU_VOUCH_YES ) {
            $yes += 1;
        } else  {
            $no += 1;
        }
    }
    return array(
        'yes' => $yes,
        'no' => $no
    );
}

////////////////////////////////////////////////////////////////////////////////
// User profile creation based on GravityForms information
////////////////////////////////////////////////////////////////////////////////

function commoners_create_profile( $applicant_id ) {
    if ( $applicant_id == 0 ) {
        echo 'Could not get user to create BuddyPress profile for.';
    }
    $details = commoners_application_details ( $applicant_id );
    xprofile_set_field_data(
        'Short Bio',
        $applicant_id,
        $details[ COMMONERS_GF_DETAILS_BIO ]
    );
    xprofile_set_field_data(
        'Location',
        $applicant_id,
        $details[ COMMONERS_GF_DETAILS_LOCATION ]
    );
    xprofile_set_field_data(
        'Short Bio',
        $applicant_id,
        $details[ COMMONERS_GF_DETAILS_LANGUAGES ]
    );
    // FINISH ME
}