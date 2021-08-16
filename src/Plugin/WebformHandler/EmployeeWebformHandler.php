<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

use Drupal\os2forms_forloeb\get_gir_url;
use Drupal\os2forms_forloeb\get_openid_auth_token;

/**
 * Webform submission handler for loading employees.
 *
 * @WebformHandler(
 *   id = "employee",
 *   label = @Translation("Load Employee"),
 *   category = @Translation("Load GIR entity"),
 *   description = @Translation("Load GIR data into form fields."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */


class EmployeeWebformHandler extends WebformHandlerBase {

    /**
     * {@inheritdoc}
     */

    // Function to be called after submitting the webform.
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

        $values = $webform_submission->getData();

        $employee_id = $values['employee'];
        $employee_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($employee_id);

        $uuid = $employee_term->get('field_uuid')->value;

        $mo_url = get_gir_url();

        // Now get all the right data from MO.
        $auth_token = get_openid_auth_token();
        $employee_path = '/service/e/' . $uuid . '/';
        $employee_url = $mo_url . $employee_path;
        // Authenticate
        $headers = [ 'Authorization' => 'Bearer ' . $auth_token, 'Accept' => 'application/json', ];

	try {
            $response = \Drupal::httpClient()->request(
                'GET', $employee_url, [
                'headers' => $headers
                ]
            );
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        $status_code = $response->getStatusCode();

        if ($status_code == 200) {
            $employee_json = json_decode($response->getBody(), true);
            \Drupal::logger('os2forms_forloeb')->notice('Employee Body: ' . '<' . json_encode($employee_json) . '>');

            // Fill out the form.
	    $webform_submission->setElementData('name', $employee_json['name']);
	    $webform_submission->setElementData('start_date', $employee_json['validity']['from']);
	    $webform_submission->setElementData('end_date', $employee_json['validity']['to']);
        }
    }
}
