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
   * @var string
   */
  public $girUrl;

  /**
   * External organisation unit parent UUID.
   *
   * @var string
   */
  public $extOUParent;

  /**
   * External organisation unit type facet UUID.
   *
   * @var string
   */
  public $extOUType;

  /**
   * External organisation unit level facet UUID.
   *
   * @var string
   */
  public $extOULevel;

  /**
   * External job function facet UUID.
   *
   * @var string
   */
  public $extJobFunction;

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

  }

  /**
   * Get configured variable from either environment or config.
   */
  private function getConfVar(string $env_key, string $config_key) {
    $gir_config = \Drupal::config('os2forms_forloeb.settings');
    return $_ENV[$env_key] ?? $gir_config->get($config_key);
  }

}
