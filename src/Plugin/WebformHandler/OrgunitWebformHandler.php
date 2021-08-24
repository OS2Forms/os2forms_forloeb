<?php

namespace Drupal\os2forms_forloeb\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

use Drupal\os2forms_forloeb\get_json_from_api;

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

	if (!$uuid) {
            \Drupal::logger('os2forms_forloeb')->notice(
                'No UUID found for org unit: ' . $orgUnit_id
            );

            return;
	}

        // Now get all the right data from MO.
        $ou_path = '/service/ou/' . $uuid . '/';
        $ou_json = get_json_from_api($ou_path);

        // Fill out the form.
	$webform_submission->setElementData('name', $ou_json['name']);
	$webform_submission->setElementData('start_date', $ou_json['validity']['from']);
	$webform_submission->setElementData('end_date', $ou_json['validity']['to']);
    }
}
