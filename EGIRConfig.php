<?php

/**
 * @file
 * EGIR configuration.
 */

/**
 * EGIR configuration class.
 */
class EGIRConfig {
  /**
   * GIR base url.
   *
   * @var string|null
   */
  public $girUrl;

  /**
   * External organisation unit parent UUID.
   *
   * @var string|null
   */
  public $extOUParent;

  /**
   * External organisation unit type facet UUID.
   *
   * @var string|null
   */
  public $extOUType;

  /**
   * External organisation unit level facet UUID.
   *
   * @var string|null
   */
  public $extOULevel;

  /**
   * External job function facet UUID.
   *
   * @var string|null
   */
  public $extJobFunction;

  /**
   * External engagement association facet UUID.
   *
   * @var string|null
   */
  public $externalEA;

  /**
   * Cost center engagement association facet UUID.
   *
   * @var string|null
   */
  public $costcenterEA;

  /**
   * Primary type facet UUID.
   *
   * @var string|null
   */
  public $primaryType;

  /**
   * External phone type facet UUID.
   *
   * @var string|null
   */
  public $extPhoneType;

  /**
   * External email type facet UUID.
   *
   * @var string|null
   */
  public $extEmailType;

  /**
   * External location type facet UUID.
   *
   * @var string|null
   */
  public $extLocationType;

  /**
   * Constructor.
   */
  public function __construct() {
    // Load configs.
    $this->girUrl = $this->getConfVar('GIR_URL', 'gir_url');
    $this->extOUParent = $this->getConfVar('GIR_EXTERNAL_OU_ROOT', 'external_ou_root');
    $this->extOUType = $this->getConfVar('GIR_EXTERNAL_OU_TYPE', 'external_ou_type');
    $this->extOULevel = $this->getConfVar('GIR_EXTERNAL_OU_LEVEL', 'external_ou_level');
    $this->extJobFunction = $this->getConfVar(
      'GIR_EXTERNAL_JOB_FUNCTION', 'external_job_function'
    );
    $this->externalEA = $this->getConfVar('GIR_EA_EXTERNAL', 'ea_external');
    $this->costcenterEA = $this->getConfVar('GIR_EA_COST_CENTER', 'ea_cost_center');
    $this->primaryType = $this->getConfVar('GIR_PRIMARY_TYPE', 'primary_type');
    $this->extPhoneType = $this->getConfVar(
      'GIR_EXTERNAL_PHONE_TYPE', 'external_phone_type'
    );
    $this->extEmailType = $this->getConfVar(
      'GIR_EXTERNAL_EMAIL_TYPE', 'external_email_type'
    );
    $this->extLocationType = $this->getConfVar(
      'GIR_EXTERNAL_LOCATION_TYPE', 'external_location_type'
    );
  }

  /**
   * Get configured variable from either environment or config.
   */
  private function getConfVar(string $env_key, string $config_key): ?string {
    $gir_config = \Drupal::config('os2forms_forloeb.settings');
    return $_ENV[$env_key] ?? $gir_config->get($config_key);
  }

}
