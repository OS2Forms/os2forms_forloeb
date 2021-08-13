<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

use Drupal\os2forms_forloeb\get_gir_url;
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

        $mo_url = get_gir_url();

        // Now get all the right data from MO.
        $auth_token = get_openid_auth_token();
        $org_unit_path = '/service/ou/' . $uuid . '/';
        $ou_url = $mo_url . $org_unit_path;
        // Authenticate
        $headers = [ 'Authorization' => 'Bearer ' . $auth_token, 'Accept' => 'application/json', ];

	try {
            $response = \Drupal::httpClient()->request(
                'GET', $ou_url, [
                'headers' => $headers
                ]
            );
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        $status_code = $response->getStatusCode();

        if ($status_code == 200) {
            $ou_json = json_decode($response->getBody(), true);
            // \Drupal::logger('os2forms_forloeb')->notice('OU Body: ' . '<' . json_encode($ou_json) . '>');

            // Fill out the form.
	    $webform_submission->setElementData('name', $ou_json['name']);
	    $webform_submission->setElementData('parent_unit', $ou_json['parent']['name']);
	    $webform_submission->setElementData('location', $ou_json['location']);
	    $webform_submission->setElementData('end_date', $ou_json['validity']['to']);
        }
    }
}
