<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\os2forms_forloeb\get_openid_auth_token;

/**
 * Webform submission handler for loading org units.
 *
 * @WebformHandler(
 *   id = "org_unit",
 *   label = @Translation("Load Organization Unit"),
 *   category = @Translation("Load GIR entity"),
 *   description = @Translation("Load GIR data into form fields."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */


class OrgunitWebformHandler extends WebformHandlerBase {

    /**
     * {@inheritdoc}
     */

    // Function to be called after submitting the webform.
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

        $values = $webform_submission->getData();

        $org_unit_id = $values['organizational_unit'];
        $org_unit_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($org_unit_id);

        $uuid = $org_unit_term->get('field_uuid')->value;

        \Drupal::logger('os2forms_forloeb')->notice('Org Unit UUID: ' . '<' . $uuid . '>');

        // TODO: Get this from configuration instead.
        $mo_url = 'http://magenta-girdevelopment.os2mo.magentahosted.dk';

        // TODO: Now get all the right data from MO.
        // $auth_token = get_openid_auth_token();
        $org_unit_path = '/service/ou/' . $uuid . '/';
        $ou_url = $mo_url . $org_unit_path;

        \Drupal::logger('os2forms_forloeb')->notice('URL: ' . '<' . json_encode($ou_url) . '>');
        $response = \Drupal::httpClient()->get($ou_url);

        $status_code = $response->getStatusCode();

        \Drupal::logger('os2forms_forloeb')->notice('HTTP Status: ' . '<' . $status_code . '>');
        $body = $response->getBody();
        \Drupal::logger('os2forms_forloeb')->notice('OU Body: ' . '<' . json_encode($body) . '>');

        // TODO: And fill out the form with it.
        $webform_submission->setElementData('name', "BATMAN'S ROBIN!");
    }

}
