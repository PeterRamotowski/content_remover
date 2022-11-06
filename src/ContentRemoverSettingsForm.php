<?php

namespace Drupal\content_remover;

use Drupal\content_remover\ContentManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ContentRemoverSettingsForm extends FormBase
{

  /**
   * @var ContentManager
   */
  protected $contentManager;

  public function __construct()
  {
    $this->contentManager = \Drupal::classResolver(ContentManager::class);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'content_remover';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $contentEntityTypes = $this->contentManager->getNonEmptyEntityTypes();

    $form['group'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Remove content from selected entity type'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

    $form['group']['entityType'] = [
      '#type' => 'select',
      '#title' => $this->t('Select entity type'),
      '#options' => $contentEntityTypes,
    ];

    $form['group']['actions'] = [
      '#type' => 'actions',
    ];

    $form['group']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Remove'),
      '#button_type' => 'danger',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $selectedEntity = $form_state->getValue('entityType');
    $selectedEntityLabel = $this->contentManager->getContentEntityTypes()[$selectedEntity];
    
    $this->contentManager->deleteEntityContent($selectedEntity);

    $this->messenger()->addStatus($this->t('@entityLabel entity content has been removed.', array('@entityLabel' => $selectedEntityLabel)));
  }
}
