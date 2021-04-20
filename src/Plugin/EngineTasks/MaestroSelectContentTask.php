<?php

namespace Drupal\os2forms_forloeb\Plugin\EngineTasks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginBase;
use Drupal\maestro\MaestroTaskTrait;
use Drupal\maestro\Form\MaestroExecuteInteractive;
use Drupal\maestro\MaestroEngineTaskInterface;
use Drupal\maestro\Engine\MaestroEngine;
use Drupal\Core\Routing\TrustedRedirectResponse;


/**
 * Maestro Select a Content Item.
 *
 * The plugin annotations below should include:
 * id: The task type ID for this task.  For Maestro tasks, this is Maestro[TaskType].
 *     So for example, the start task shipped by Maestro is MaestroStart.
 *     The Maestro End task has an id of MaestroEnd
 *     Those task IDs are what's used in the engine when a task is injected into the queue
 *
 * @Plugin(
 *   id = "MaestroSelectContent",
 *   task_description = @Translation("The Maestro Engine's Task to Select a Content Item."),
 * )
 */
class MaestroSelectContentTask extends PluginBase implements MaestroEngineTaskInterface {

    use MaestroTaskTrait;

    function __construct($configuration = NULL) {
        if(is_array($configuration)) {
            $this->processID = $configuration[0];
            $this->queueID = $configuration[1];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isInteractive() {
        return TRUE;
    }

    /**
     * {@inheritDoc}
     */
    public function shortDescription() {
        return t('Create or Select a Content Item');
    }

    /**
     * {@inheritDoc}
     */
    public function description() {
        return $this->t('Create or Select a Content Item');
    }

    /**
     *
     * {@inheritDoc}
     * @see \Drupal\Component\Plugin\PluginBase::getPluginId()
     */
    public function getPluginId() {
        return 'MaestroSelectContent';
    }

    /**
     * {@inheritDoc}
     */
    public function getTaskColours() {
        return '#e2743c';
    }



    /**
     * {@inheritDoc}
     */
    public function getTaskEditForm(array $task, $templateMachineName) {
        $form = array(
            '#markup' => t('Select a Content Item.'),
        );

        $content_type_options = array(
            'all' => t('All Content Types'),
        );
        $content_types_objs = $types = \Drupal::entityTypeManager()
            ->getStorage('node_type')
            ->loadMultiple();
        foreach($content_types_objs as $content_type_machine_name => $content_type) {
            $content_type_options[$content_type_machine_name] = $content_type_machine_name;
        }

        $form['content_type'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select the Content Type'),
            '#description' => $this->t('Limit the content that can be selected to this content type.'),
            '#required' => TRUE,
            '#options' => $content_type_options,
        );
        if(isset($task['data']['content_type'])) {
            $form['content_type']['#default_value'] = $task['data']['content_type'];
        }

        $form['unique_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Give this piece of content a unique identifier'),
            '#description' => $this->t('This identifier is stored along with its ID to allow you to recall it when filled in.'),
            '#default_value' => isset($task['data']['unique_id']) ? $task['data']['unique_id'] : '',
            '#required' => TRUE,
        ];

        $form['redirect_to'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Return Path'),
            '#description' => $this->t('You can specify where your return path should go upon task completion.'),
            '#default_value' => isset($task['data']['redirect_to']) ? $task['data']['redirect_to'] : '/taskconsole',
            '#required' => TRUE,
        );

        $form['modal'] = array(
            '#type' => 'select',
            '#title' => $this->t('Task presentation'),
            '#description' => $this->t('Should this task be shown as a modal or full screen task.'),
            '#default_value' => isset($task['data']['modal']) ? $task['data']['modal'] : 'notmodal',
            '#options' => array(
                'modal' => $this->t('Modal'),
                'notmodal' => $this->t('Full Page'),
            ),
            '#required' => TRUE,
        );
        return $form;
    }


    /**
     * {@inheritDoc}
     */
    public function validateTaskEditForm(array &$form, FormStateInterface $form_state) {

    }

    /**
     * {@inheritDoc}
     */
    public function prepareTaskForSave(array &$form, FormStateInterface $form_state, array &$task) {

        $task['data']['modal'] = $form_state->getValue('modal');
        $redirect = $form_state->getValue('redirect_to');
        if(isset($redirect)) {
            $task['data']['redirect_to'] = $redirect;
        }
        else {
            $task['data']['redirect_to'] = '';
        }

        $content_type = $form_state->getValue('content_type');
        if(isset($content_type)) {
            $task['data']['content_type'] = $content_type;
        }
        else {
            $task['data']['content_type'] = '';
        }

        $task['data']['unique_id'] = $form_state->getValue('unique_id');

    }

    /*
     * Part of the ExecutableInterface
     * Execution of the interactive task does nothing except for setting the run_once flag
     * {@inheritdoc}
     */
    public function execute() {
        //need to set the run_once flag here
        //as interactive tasks are executed and completed by the user using the Maestro API
        $queueRecord = \Drupal::entityTypeManager()->getStorage('maestro_queue')->load($this->queueID);
        $queueRecord->set('run_once', 1);
        $queueRecord->save();

        return TRUE;
    }

    /**
     * {@inheritDoc}
     */
    public function getExecutableForm($modal, MaestroExecuteInteractive $parent) {
        $form['new_article'] = array(
            '#id' => 'new_article',
            '#type' => 'radios',
            '#options' => array (
                0 => t('Choose an Existing Article'),
                1 => t('Create a New Article'),
            ),
            '#default_value' => 0,
            '#title' =>  t('Create or Select an Article'),
            '#required' => true,
        );

        //query for all articles.
        $articles = array();
        $nids = \Drupal::entityQuery('node')
            ->condition('type','article')
            ->execute();
        $article_nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
        foreach($article_nodes as $nid => $article_node) {
            $articles[$nid] = $article_node->getTitle();
        }

        $form['existing_article'] = array(
            '#id' => 'existing_article',
            '#type' => 'select',
            '#options' => $articles,
            '#title' =>  t('Choose an Existing Article'),
            '#validated' => TRUE,
            '#prefix' => '<div id="existing-article-wrapper">',
            '#suffix' => '</div>',
            '#states' => array(
                'invisible' => array(
                    ':input[name="new_article"]' => array('value' => 1),
                ),
                'required' => array(
                    ':input[name="new_article"]' => array('value' => 0),
                ),
            ),
        );

        $form['queueID'] = array(
            '#type' => 'hidden',
            '#title' => 'the queue ID in the event we need it in later processing',
            '#default_value' => $this->queueID,
            '#description' => ('queueID'),
        );

        //add all of the entity form's fields into a fieldset
        //create a fieldset to hold all the entity form fields
        #ToDo Rather than simply hiding the entity_form it should be loaded and
        # unloaded via JS. Currently required fields in the entity form, can cause
        # this form to not validate, even when the new entity option is not selected
        # and the entity_form is hidden.
        $form['entity_form'] = array(
            '#type'=>'fieldset',
            '#title'=>'Create a Article',
            '#states' => array(
                'invisible' => array(
                    ':input[name="new_article"]' => array('value' => 0),
                ),
                'required' => array(
                    ':input[name="new_article"]' => array('value' => 1),
                ),
            ),
        );

        //create a form_state for this step.
        $form_state = new FormState();

        //specify a #parents key on our form definition because that is something the widgets themselves expect.
        $form['#parents'] = [];

        //load an entity and store on the form state.
        $new_article = \Drupal::entityTypeManager()->getStorage('node')->create([
            'type' => 'article'
        ]);
        $form_state->set('entity', $new_article);

        //Load the form display
        $article_form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.article.default');
        $form_state->set('form_display', $article_form_display);
        //Loop over the form display and add fields to the maestro form
        foreach ($article_form_display->getComponents() as $name => $component) {
            //Load the component's configured widget
            $widget = $article_form_display->getRenderer($name);
            $items = $new_article->get($name);
            $form['entity_form'][$name] = $widget->form($items, $form, $form_state);
            $form['entity_form'][$name]['#weight'] = $component['weight'];
            //make the title field required.
            if($name == "title"){
                $form['entity_form'][$name]['#required'] = false;
                $form['entity_form'][$name]['widget']['#required'] = false;
                $form['entity_form'][$name]['widget'][0]['#required'] = false;
                $form['entity_form'][$name]['widget'][0]['value']['#required'] = false;
                $form['entity_form'][$name]['#states'] = array(
                    'required' => array(
                        ':input[name="new_lineup"]' => array('value' => 1),
                    ),
                );
            }
        }

        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Go'),
            '#weight' => 1000
        );

        if($modal == 'modal') {  //if this is a modal task, we use the ajax completion routines and tell the buttons to use our built in completeForm modal closer
            $form['actions']['submit']['#ajax'] = array(
                'callback' => [$parent, 'completeForm'],
                'wrapper' => '',
            );

        }
        return $form;
    }

    /**
     * {@inheritDoc}
     */
    public function handleExecuteSubmit(array &$form, FormStateInterface $form_state) {


        $queueID = intval($form_state->getValue('maestro_queue_id'));
        $triggeringElement = $form_state->getTriggeringElement();
        $processID = MaestroEngine::getProcessIdFromQueueId($queueID);

        if(strstr($triggeringElement['#id'], 'edit-submit') !== FALSE && $queueID > 0) {
            /**
             * If existing article is chosen simply assign it to this process
             *
             * If a new artivle is chosen create that article and assign it to this process.
             */
            if($form_state->getValue('new_article')) {
                //Create the new article entity.
                $new_article = \Drupal::entityTypeManager()->getStorage('node')->create([
                    'type' => 'article'
                ]);
                //Load te form display
                $article_form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.article.default');
                //extract all of the form display's fields that are in the form_state values and assign to the new article.
                $article_form_display->extractFormValues($new_article, $form, $form_state);
                //Save the article
                $new_article->save();
                //Assign this article to the maestro variable.
                MaestroEngine::setProcessVariable("new_article", $new_article->id(), $processID);
                //Bound this entity to this maestro process
                $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
                MaestroEngine::createEntityIdentifier($processID, 'node', 'article', $templateTask['data']['unique_id'], $new_article->id());
                //Complete this task.
                MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
            }
            else {
                $article_nid = $form_state->getValue('existing_article');
                MaestroEngine::setProcessVariable("new_article", $article_nid, $processID);
                //Bound this entity to this maestro process
                $templateTask = MaestroEngine::getTemplateTaskByQueueID($queueID);
                MaestroEngine::createEntityIdentifier($processID, 'node', 'article', $templateTask['data']['unique_id'], $article_nid);

                //Complete this task.
                MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
            }
        }
        else {
            //we'll complete the task, but we'll also flag it as TASK_STATUS_CANCEL
            MaestroEngine::completeTask($queueID, \Drupal::currentUser()->id());
            MaestroEngine::setTaskStatus($queueID, TASK_STATUS_CANCEL);
        }

        $task = MaestroEngine::getTemplateTaskByQueueID($queueID);
        if(isset($task['data']['redirect_to'])) {
            $response = new TrustedRedirectResponse($task['data']['redirect_to']);
            $form_state->setResponse($response);
        }

    }

    /**
     * {@inheritDoc}
     */
    public function performValidityCheck(array &$validation_failure_tasks, array &$validation_information_tasks, array $task) {
        if( (array_key_exists('modal', $task['data']) && $task['data']['modal'] == '')  || !array_key_exists('modal', $task['data'])) {
            $validation_failure_tasks[] = array(
                'taskID' => $task['id'],
                'taskLabel' => $task['label'],
                'reason' => t('The Interactive Task has not been set up properly.  The "Task Presentation" option is missing and thus the engine will be unable to execute this task.'),
            );
        }

        //This task should have assigned users
        //$task['assigned'] should have data
        if( (array_key_exists('assigned', $task) && $task['assigned'] == '')  || !array_key_exists('assigned', $task)) {
            $validation_failure_tasks[] = array(
                'taskID' => $task['id'],
                'taskLabel' => $task['label'],
                'reason' => t('The Interactive Task has not been set up properly.  The Interactive Task requires assignments to actors, roles or other assignment options.'),
            );
        }

        if ((array_key_exists('unique_id', $task['data']) && $task['data']['unique_id'] == '')  || !array_key_exists('unique_id', $task['data'])) {
            $validation_failure_tasks[] = [
                'taskID' => $task['id'],
                'taskLabel' => $task['label'],
                'reason' => t('The Content Type Task has not been set up properly.  The "unique identifier" option is missing and thus the engine will be unable to execute this task.'),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplateBuilderCapabilities() {

        return array('edit', 'drawlineto', 'removelines', 'remove');

    }

}
