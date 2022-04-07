<?php

namespace Drupal\os2forms_forloeb;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Entity\MaestroEntityIdentifiers;
use Drupal\maestro\Entity\MaestroProcess;
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
    /** @var WebformSubmission $webform_submission */
    $webform_submissions = $this->entityTypeManager->getStorage('webform_submission')->loadByProperties(['token' => $token]);
    $webform_submission = reset($webform_submissions);
    /** @var MaestroEntityIdentifiers $maestro_entity_identifier */
    $maestro_entity_identifiers = $this->entityTypeManager->getStorage('maestro_entity_identifiers')->loadByProperties([
      'entity_type' => 'webform_submission',
      'entity_id' => $webform_submission->id(),
    ]);
    $maestro_entity_identifier = reset($maestro_entity_identifiers);
    $processIDs = $maestro_entity_identifier->process_id->referencedEntities();
    $processID = reset($processIDs);
    $maestro_queues = $this->entityTypeManager->getStorage('maestro_queue')->loadByProperties([
      'process_id' => $processID->id(),
      'task_class_name' => 'MaestroWebformInherit',
    ]);
    $maestro_queue = reset($maestro_queues);
    return $maestro_queue;

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
