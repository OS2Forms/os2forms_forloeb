<?php

namespace Drupal\os2forms_forloeb;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Class ForloebTaskConsole.
 */
class ForloebTaskConsole {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ForloebTaskConsole object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets MaestroQueue record by webforms submission token.
   *
   * @param string $token
   *
   * @return \Drupal\maestro\Entity\MaestroQueue|null
   *   Returns MaestroQueue entity or NULL
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getQueueIdByWebformSubmissionToken($token = '') {
    $engine = new MaestroEngine();
    // Fetch the user's queue items.
    $queueIDs = $engine->getAssignedTaskQueueIds(\Drupal::currentUser()->id());

    foreach ($queueIDs as $queueID) {
      $this->entityTypeManager->getStorage('maestro_queue')->resetCache([$queueID]);
      /** @var \Drupal\maestro\Entity\MaestroQueue $queueRecord */
      $queueRecord = $this->entityTypeManager->getStorage('maestro_queue')->load($queueID);
      $processID = $engine->getProcessIdFromQueueId($queueID);
      $templateMachineName = $engine->getTemplateIdFromProcessId($processID);

      // Get user input from 'inherit_webform_unique_id'
      $taskMachineName = $engine->getTaskIdFromQueueId($queueID);
      $task = $engine->getTemplateTaskByID($templateMachineName, $taskMachineName);

      // Load its corresponding webform submission.
      $sid = $engine->getEntityIdentiferByUniqueID($processID, $task['data']['inherit_webform_unique_id'] ?? '');
      $webform_submission = $sid ? WebformSubmission::load($sid) : NULL;

      // Compare webform submission with token from request.
      if ($webform_submission && $webform_submission->getToken() == $token) {
        return $queueRecord;
      }
    }

    return NULL;
  }
}
