<?php 

namespace Drupal\os2forms_forloeb\Plugin\EngineTasks;

use Drupal\maestro_webform\Plugin\EngineTasks\MaestroWebformTask;
use Drupal\maestro\Form\MaestroExecuteInteractive;

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
    \Drupal::logger('os2forms_forloeb')->notice
      ("Called with return value: " . $returnValue);

    return $returnValue;
  }
}
