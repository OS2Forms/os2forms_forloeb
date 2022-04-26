<?php

namespace Drupal\os2forms_forloeb;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Entity\MaestroEntityIdentifiers;
use Drupal\maestro\Entity\MaestroProcess;
use Drupal\webform\Entity\WebformSubmission;
use Psr\Log\LoggerInterface;

/**
 * Class ForloebTaskConsole.
 */
class ForloebTaskConsole {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ForloebTaskConsole object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
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

    if (empty($webform_submissions)) {
      $this->logger->warning($this->t('Submission with token @token not found', ['@token' => $token]));
      return NULL;
    }

    $webform_submission = reset($webform_submissions);
    /** @var MaestroEntityIdentifiers $maestro_entity_identifier */
    $maestro_entity_identifiers = $this->entityTypeManager->getStorage('maestro_entity_identifiers')->loadByProperties(['entity_type' => 'webform_submission', 'entity_id' => $webform_submission->id(),]);
    $maestro_entity_identifier = reset($maestro_entity_identifiers);
    $processIDs = $maestro_entity_identifier->process_id->referencedEntities();
    if (empty($processIDs)) {
      $this->logger->warning($this->t('Process with entity type: webform_submission and entity_id: @entity_id not found', ['@entity_id' => $webform_submission->id()]));
      return NULL;
    }

    $processID = reset($processIDs);
    $maestro_queues = $this->entityTypeManager->getStorage('maestro_queue')->loadByProperties(['process_id' => $processID->id(), 'task_class_name' => 'MaestroWebformInherit',]);
    if (empty($maestro_queues)) {
      $this->logger->warning($this->t('Maestro queue with task_class_name: MaestroWebformInherit and process_id: @process_id not found', ['@process_id' => $processID->id()]));
      return NULL;
    }
    $maestro_queue = reset($maestro_queues);
    return $maestro_queue;
  }

}
