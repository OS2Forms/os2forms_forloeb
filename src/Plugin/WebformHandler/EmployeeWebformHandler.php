<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

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

        $employee_id = $values['external_employee'];
        $employee_term = \Drupal::entityTypeManager()->getStorage('user')->load($employee_id);

        $uuid = $employee_term->get('field_uuid')->value;
        
        if (!$uuid) {
            return;
	}

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

        // Get email and phone from address details. 
        $email_address = "";
        $mobile_number = "";
        $telephone_number = "";
        if ($details_json['address']) {
            $address_path = $details_path . 'address';
            $address_json = get_json_from_api($address_path);

            foreach ($address_json as $address) {

                if ($address['address_type']['name'] == 'Mobile') {
                    $mobile_number = $address['value'];
                } elseif ($address['address_type']['name'] == 'Phone') {
                    $telephone_number = $address['value'];
                } elseif ($address['address_type']['scope'] == 'EMAIL') {
                    $email_address = $address['value'];
                }

            }
        }

        // Get org unit for current engagement from engagement details.
        if ($details_json['engagement']) {
            $engagement_path = $details_path . 'engagement';
            $engagement_json = get_json_from_api($engagement_path);
            // TODO: Later, handle multiple engagements.
            $engagement = reset($engagement_json);

            $consultancy_name = $engagement['org_unit']['name'];
            $properties = [];
            $properties['name'] = $consultancy_name;
            $terms = \Drupal::entityManager()->getStorage(
                'taxonomy_term'
            )->loadByProperties($properties);
            $term = reset($terms);
            $consultancy_id = $term->id();
        }


        \Drupal::logger('os2forms_forloeb')->notice(
            'Engagement JSON: ' . json_encode($engagement_json)
        );
        
        \Drupal::logger('os2forms_forloeb')->notice(
            'Address JSON: ' . json_encode($address_json)
        );

        // Fill out the form.
        $webform_submission->setElementData('first_name', $employee_json['givenname']);
        $webform_submission->setElementData('last_name', $employee_json['surname']);
        if ($details_json['engagement']) {
            $webform_submission->setElementData('consultancy', $consultancy_id);
        }
        $webform_submission->setElementData('telephone_number', $telephone_number);
        $webform_submission->setElementData('email_address', $email_address);
    }
}
