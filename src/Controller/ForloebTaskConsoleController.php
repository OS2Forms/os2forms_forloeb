<?php

namespace Drupal\os2forms_forloeb\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\maestro\Utility\TaskHandler;
use Drupal\os2forms_forloeb\ForloebTaskConsole;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ForloebTaskConsoleController.
 */
class ForloebTaskConsoleController extends ControllerBase {

  /**
   * Update manager service.
   *
   * @var \Drupal\os2forms_forloeb\ForloebTaskConsole
   */
  protected $forloebTaskConsole;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\os2forms_forloeb\ForloebTaskConsole $forloebTaskConsole
   *   Forloeb task console Service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity type manager.
   */
  public function __construct(ForloebTaskConsole $forloeb_task_console, EntityTypeManagerInterface $entity_type_manager) {
    $this->forloebTaskConsole = $forloeb_task_console;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2forms_forloeb.task_console'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Redirects to the task execution URL.
   *
   * In case it's not possible to define task, redirects to task console.
   *
   * @return RedirectResponse
   *   Redirect object.
   */
  public function execute() {
    $redirect_to = Url::fromRoute('maestro_taskconsole.taskconsole');

    // Check webform submission token.
    $token = \Drupal::request()->query->get('os2forms-forloeb-ws-token', '');
    if ($token) {
      $queueRecord = $this->forloebTaskConsole->getQueueIdByWebformSubmissionToken($token);
    }
    else {
      // For empty token there is user last task from taskconsole queue.
      $queueIDs = MaestroEngine::getAssignedTaskQueueIds(\Drupal::currentUser()->id());
      $queueRecord = count($queueIDs) ? $this->entityTypeManager->getStorage('maestro_queue')->load(end($queueIDs)) : NULL;

      // In case there are more than 1 task warning message will be shown.
      if (count($queueIDs) > 1) {
        $this->messenger()->addWarning($this->t('You have @amount @tasks available for you. See list of the all tasks on <a href=":tasksonsole">taskconsole</a>', [
          ':tasksonsole' => Url::fromRoute('maestro_taskconsole.taskconsole')->toString(),
          '@amount' => count($queueIDs),
          '@tasks' => new PluralTranslatableMarkup(count($queueIDs), 'task','tasks'),
        ]));
      }
    }

    if (empty($queueRecord)) {
      $this->messenger()->addWarning($this->t('No tasks found to execute.'));
      return new RedirectResponse($redirect_to->toString());
    }

    // Processing QueueRecord to get execution URL to redirect to.
    $handler = $queueRecord->handler->getString();
    $query_options = [
      'queueid' => $queueRecord->id(),
      'modal' => 'notmodal'
    ];

    // As inspiration MaestroTaskConsoleController::getTasks() method was used.
    if ($handler && !empty($handler) && $queueRecord->is_interactive->getString() == '1') {
      global $base_url;
      $handler = str_replace($base_url, '', $handler);
      $handler_type = TaskHandler::getType($handler);

      $handler_url_parts = UrlHelper::parse($handler);
      $query_options += $handler_url_parts['query'];

    }
    elseif ($queueRecord->is_interactive->getString() == '1' && empty($handler)) {
      // Handler is empty. If this is an interactive task and has no handler, we're still OK.  This is an interactive function that uses a default handler then.
      $handler_type = 'function';
    }
    else {
      $this->messenger()->addWarning($this->t('Undefined handler'));
    }

    switch ($handler_type) {
      case 'external':
        $redirect_to =  Url::fromUri($handler, ['query' => $query_options]);
        break;

      case 'internal':
        $redirect_to = Url::fromUserInput($handler, ['query' => $query_options]);
        break;

      case 'function':
        if ($token) {
          $query_options['os2forms-forloeb-ws-token'] = $token;
        }
        $redirect_to = Url::fromRoute('maestro.execute', $query_options);
        break;
    }

    return new RedirectResponse($redirect_to->toString());
  }

}
