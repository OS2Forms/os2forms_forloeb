<?php

/**
 * @file
 * Utilities for EGIR.
 */

use GuzzleHttp\Exception\BadResponseException;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

/**
 * Get logger.
 */
function forms_log() {
  return \Drupal::logger('os2forms_forloeb');
}

/**
 * Get user data by Drupal ID and field name.
 */
function get_user_data($user_id, $field_name) {
  $user = \Drupal::entityTypeManager()->getStorage('user')->load($user_id);
  return $user->getTypedData()->get($field_name)->value;
}

/**
 * Get taxonomy term data by Drupal ID and field name.
 */
function get_term_data($term_id, $field_name) {
  $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term_id);
  return $term->getTypedData()->get($field_name)->value;
}

/**
 * Get term ID by name.
 */
function get_term_id_by_name($name) {

  $properties = [];
  $properties['name'] = $name;
  $terms = \Drupal::entityTypeManager()->getStorage(
    'taxonomy_term'
  )->loadByProperties($properties);
  $term = reset($terms);
  return $term->id();
}

/**
 * Get JSON from specified GIR API path.
 */
function get_json_from_api($path) {
  $config = new EGIRConfig();
  $mo_url = $config->girUrl;
  $url = $mo_url . $path;
  $auth_token = get_openid_auth_token();

  // Authenticate.
  $headers = [
    'Authorization' => 'Bearer ' . $auth_token,
    'Accept' => 'application/json',
  ];

  try {
    $response = \Drupal::httpClient()->request(
      'GET',
      $url,
      ['headers' => $headers]
    );
  }
  catch (BadResponseException $e) {
    $response = $e->getResponse();
  }

  if ($response->getStatusCode() == 200) {
    return json_decode($response->getBody(), TRUE);
  }
  else {
    forms_log()->notice('Call to URL' . $url . 'failed:' . $response->getBody());
    return "";
  }
}

/**
 * Post data to the relevant path.
 */
function post_json_to_api($path, $data) {
  // Full API path.
  $config = new EGIRConfig();
  $url = $config->girUrl . $path;
  // Authentication headers.
  $access_token = get_openid_auth_token();
  $headers = [
    'Authorization' => 'Bearer ' . $access_token,
    'Accept' => 'application/json',
    'content-type' => 'application/json',
  ];

  try {
    $response = \Drupal::httpClient()->request(
      'POST',
      $url,
      ['body' => $data, 'headers' => $headers]
    );
  }
  catch (BadResponseException $e) {
    $response = $e->getResponse();
  }
  return $response;
}

/**
 * Get OpenID authentication token from Keycloak.
 */
function get_openid_auth_token() {
  $keycloak_configuration = \Drupal::config('openid_connect.settings.keycloak');

  $keycloak_settings = $keycloak_configuration->get('settings');
  $keycloak_base = $keycloak_settings['keycloak_base'];
  $keycloak_realm = $keycloak_settings['keycloak_realm'];
  $client_id = $keycloak_settings['client_id'];
  $client_secret = $keycloak_settings['client_secret'];

  $token_url = $keycloak_base . '/realms/' . $keycloak_realm . '/protocol/openid-connect/token';

  $payload['grant_type'] = 'client_credentials';
  $payload['client_id'] = $client_id;
  $payload['client_secret'] = $client_secret;

  $json = json_encode($payload);
  $response = \Drupal::httpClient()->request(
    'POST',
    $token_url,
    ['form_params' => $payload]
  );
  $status_code = $response->getStatusCode();

  if ($status_code == 200) {
    $body = json_decode($response->getBody(), TRUE);
    $access_token = $body['access_token'];

    return $access_token;
  }
  else {
    return '';
  }
}

/**
 * Get all employments with engagements in the specified organisation unit.
 *
 * NOTE: Do not recurse into children.
 */
function get_employees_for_org_unit($org_unit_uuid) {
  $engagement_path = "/service/ou/{$org_unit_uuid}/details/engagement?validity=present";

  $engagements = get_json_from_api($engagement_path);
  $employees = [];

  foreach ($engagements as $engagement) {
    $employees[$engagement['uuid']] = $engagement["person"];
  }

  return $employees;
}

/**
 * Get all employments with engagements in the specified organisation unit.
 */
function get_externals_for_org_unit($org_unit_uuid) {
  $ea_path = "/api/v1/engagement_association?validity=present&org_unit={$org_unit_uuid}";
  $engagement_associations = get_json_from_api($ea_path);

  $externals = [];

  foreach ($engagement_associations as $ea) {
    if ($ea["engagement_association_type"]["user_key"] == "External") {
      $externals[$ea["engagement"]["user_key"]] = $ea["engagement"]["person"];
    }
  }

  return $externals;
}
