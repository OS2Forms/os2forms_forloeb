<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

use Drupal\os2forms_forloeb\get_json_from_api;
use Drupal\os2forms_forloeb\get_term_id_by_name;

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

        // Date for retrieving valid details.
        $today = date("Y-m-d");
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
            $address_path = $details_path . 'address' . '?at=' . $today;
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

        $cost_center_id = "";
        $organizational_unit_id = "";
        $consultant_type_id = "";
        $start_date = "";
        $end_date = "";
        $engagement = [];
        
       // Get org unit for current engagement from engagement details.
        if ($details_json['engagement']) {
            $engagement_path = $details_path . 'engagement'. '?at=' . $today;
            $engagement_json = get_json_from_api($engagement_path);
            // TODO: Later, handle multiple engagements.
            $engagement = reset($engagement_json);
        }

        if ($engagement) {
            $consultancy_name = $engagement['org_unit']['name'];

            $consultancy_id = get_term_id_by_name($consultancy_name);

            $consultant_type_name = $engagement['engagement_type']['name'];
            $consultant_type_id = get_term_id_by_name($consultant_type_name); 

            $start_date = $engagement['validity']['from'];
            $end_date = $engagement['validity']['to'];

            // Now for the engagement associations.
            // This only makes sense if there is an engagement.
            $engagement_uuid = $engagement['uuid'];
            $ea_path = (
                '/api/v1/engagement_association' . '?engagement=' . $engagement_uuid .
                '&at=' . $today
            );
            $ea_json = get_json_from_api($ea_path);

            if ($ea_json) {
                // There might not be any.
                foreach ($ea_json as $ea) {
                    if ($ea['engagement_association_type']['user_key'] == "Legal Company") {
                        // This is the placement in the legal organization.
                    } elseif (
                        $ea['engagement_association_type']['user_key'] == "Cost Center"
                    ) {
                        // This is the cost center.
                        $cost_center_name = $ea['org_unit']['name'];
                        $cost_center_id = get_term_id_by_name($cost_center_name);
                    } elseif (
                        $ea['engagement_association_type']['user_key'] == "External"
                    ) {
                        // This is the org unit where the external is working.
                        // Note, we should really be handling those as an array
                        // as there may be more than one. Similar for legal org.
                        $org_unit_name = $ea['org_unit']['name'];
                        $organizational_unit_id = get_term_id_by_name($org_unit_name);
                    }
                }
            }


        }
        /*
        \Drupal::logger('os2forms_forloeb')->notice(
            'Engagement JSON: ' . json_encode($engagement_json)
        );
         */
        
        // Fill out the form.
        $webform_submission->setElementData('first_name', $employee_json['givenname']);
        $webform_submission->setElementData('last_name', $employee_json['surname']);
        $webform_submission->setElementData('telephone_number', $telephone_number);
        $webform_submission->setElementData('email_address', $email_address);
        if ($engagement) {
            $webform_submission->setElementData('consultancy', $consultancy_id);
        }
        if ($cost_center_id) {
            $webform_submission->setElementData('cost_center', $cost_center_id);
        }
        if ($consultant_type_id) {
            $webform_submission->setElementData('consultant_type', $consultant_type_id);
        }
        if ($start_date) {
            $webform_submission->setElementData('start_date', $start_date);
        }
        if ($end_date) {
            $webform_submission->setElementData('end_date', $end_date);
        }
    }
}
