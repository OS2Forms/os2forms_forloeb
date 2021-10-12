<?php 

namespace Drupal\os2forms_forloeb\Plugin\EngineTasks;

use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Drupal\maestro_webform\Plugin\EngineTasks\MaestroWebformTask;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\Engine\MaestroEngine;

/**
 * Maestro Webform Task Plugin for Multiple Submissions.
 *
 * @Plugin(
 *   id = "MaestroWebformMultiple",
 *   task_description = @Translation("Maestro Webform task for multiple submissions."),
 * )
 */
class MaestroWebformMultipleTask extends MaestroWebformTask {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The incoming configuration information from the engine execution.
   *   [0] - is the process ID
   *   [1] - is the queue ID
   *   The processID and queueID properties are defined in the MaestroTaskTrait.
   */
  public function __construct(array $configuration = NULL) {
    if (is_array($configuration)) {
      $this->processID = $configuration[0];
      $this->queueID = $configuration[1];
    }
  }


  /**
   * {@inheritDoc}
   */
  public function shortDescription() {
    return t('Webform task with Multiple Submissions');
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    return $this->t('Webform task with Multiple Submissions');
  }

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
   */
  public function getPluginId() {
    return 'MaestroWebformMultiple';
  }

  /**
   * {@inheritDoc}
   */
  public function getExecutableForm($modal, MaestroExecuteInteractive $parent)
  {
    $returnValue = parent::getExecutableForm($modal, $parent);

    // Get task, submission ID and web form ID.
    $templateTask = MaestroEngine::getTemplateTaskByQueueID($this->queueID);
    $taskUniqueSubmissionId = $templateTask['data']['unique_id'];
    $webformMachineName = $templateTask['data']['webform_machine_name'];

    \Drupal::logger('os2forms_forloeb')->notice(
      "Task: " . json_encode($templateTask)
    );

    // If this is done properly, there's no submission associated with
    // taskUniqueSubmissionId. We need to create a submission as described here:
    // https://www.drupal.org/docs/8/modules/webform/webform-cookbook/how-to-programmatically-create-and-update-a-submission
    
    // First, get hold of the interesting previous tasks.
    $templateMachineName = MaestroEngine::getTemplateIdFromProcessId($this->processID);
    $taskMachineName = MaestroEngine::getTaskIdFromQueueId($this->queueID);

    $pointers = MaestroEngine::getTaskPointersFromTemplate(
      $templateMachineName, $taskMachineName
    );
    // Now, there can only be one task preceding this, the AND 
    // task collecting the submissions.
    $pointers = MaestroEngine::getTaskPointersFromTemplate(
      $templateMachineName, $pointers[0]
    );
    \Drupal::logger('os2forms_forloeb')->notice(
      "Pointers: " . json_encode($pointers)
    );
    // Now, we query the queue to find the actual tasks pointing to the AND
    // task.
    $query = \Drupal::entityQuery('maestro_queue');
    $andMainConditions = $query->andConditionGroup()
                               ->condition('process_id', $this->processID);
    $orConditionGroup = $query->orConditionGroup();
    foreach ($pointers as $taskID) {
      $orConditionGroup->condition('task_id', $taskID);
    }
    $andMainConditions->condition($orConditionGroup);
    $query->condition($andMainConditions);
    $entityIDs = $query->execute();


    foreach ($entityIDs as $entityID) {
      // Load the Maestro task with ID $pid.
      $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($entityID);
      \Drupal::logger('os2forms_forloeb')->notice(
        "Queue Record: " . json_encode($queueRecord)
      );
      // Load its corresponding webform submission.
      // Copy the fields of the webform submission to the values array.
    }
    return $returnValue;
  }
}
