<?php

namespace Drupal\campaignion\Wizard;

use \Drupal\campaignion\Forms\EmbeddedNodeForm;

class ContentStep extends WizardStep {
  protected $step = 'content';
  protected $title = 'Content';
  protected $nodeForm;

  public function stepForm($form, &$form_state) {
    $form = parent::stepForm($form, $form_state);

    // load original node form
    $form_state['embedded']['#wizard'] = TRUE;
    $this->nodeForm = new EmbeddedNodeForm($this->wizard->node, $form_state);
    $form += $this->nodeForm->formArray();

    // we don't want the webform_template selector to show up here.
    unset($form['webform_template']);

    $form[$this->wizard->parameters['thank_you_page']['reference']]['#access'] = FALSE;

    $form['actions']['#access'] = FALSE;
    $form['options']['#access'] = TRUE;
    unset($form['#metatags']);

    // don't publish per default
    if (!isset($this->wizard->node->nid)) {
      $form['options']['status']['#default_value'] = 0;
      $form['options']['promote']['#default_value'] = 0;
    }

    // secondary container
    $form['wizard_secondary'] = array(
      '#type' => 'container',
      '#weight' => 3001,
    );

    $wizard_secondary_used = false;
    // move specific items to secondary container
    foreach (array('field_main_image') as $field_name) {
      if (isset($form[$field_name])) {
        $form['wizard_secondary'][$field_name] = $form[$field_name];
        unset($form[$field_name]);
        $wizard_secondary_used = true;
      }
    }
    if ($wizard_secondary_used) {
      $form['#attributes']['class'][] = 'wizard-secondary-container';
    }
    $form['field_reference_to_campaign']['#weight'] = 1;

    return $form;
  }

  public function validateStep($form, &$form_state) {
    $this->nodeForm->validate($form, $form_state);
  }

  public function submitStep($form, &$form_state) {
    $this->nodeForm->submit($form, $form_state);
    $form_state['form_info']['path'] = 'node/' . $form_state['embedded']['node']->nid . '/wizard/%step';
  }

  public function status() {
    return array(
      'caption' => t('Your copy is great'),
      'message' => t('You have added content, a nice picture and a convincing title.'),
    );
  }
}
