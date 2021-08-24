<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

use Drupal\os2forms_forloeb\get_gir_url;
use Drupal\os2forms_forloeb\get_openid_auth_token;
use Drupal\os2forms_forloeb\get_json_from_api;

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

        // Now get all the right data from MO.
        $employee_path = '/service/e/' . $uuid . '/';

        $employee_json = get_json_from_api($employee_path);

        if ($employee_json == "") {
            return;
        }

        // Get details link and extract addresses etc.
        $details_path = $employee_path . 'details/';

        $details_json = get_json_from_api($details_path);

        if ($details_json == "") {
            return;
        }

        if $details_json['address'] {
            $address_path = $details_path . 'address';
            $address_json = get_json_from_api($address_path);
        }

        if $details_json['engagement'] {
            $engagement_path = $details_path . 'engagement';
            $engagement_json = get_json_from_api($engagement_path);
        }

            // Fill out the form.
	    $webform_submission->setElementData('name', $employee_json['name']);
	    $webform_submission->setElementData('given_name', $employee_json['given_name']);
	    $webform_submission->setElementData('sur_name', $employee_json['sur_name']);
	    $webform_submission->setElementData('start_date', $employee_json['validity']['from']);
	    $webform_submission->setElementData('end_date', $employee_json['validity']['to']);
        }
    }
}
