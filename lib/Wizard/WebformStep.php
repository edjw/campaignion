<?php

namespace Drupal\campaignion\Wizard;

class WebformStep extends WizardStep {
  protected $step = 'form';
  protected $title = 'Form';
  protected function loadIncludes() {
    module_load_include('inc', 'form_builder', 'includes/form_builder.admin');
    module_load_include('inc', 'form_builder', 'includes/form_builder.api');
    module_load_include('inc', 'form_builder', 'includes/form_builder.cache');
  }

  public function pageCallback() {
    $build = parent::pageCallback();
    $form =& $build[0];
    $form['actions']['#access'] = FALSE;
    $form['#weight'] = -100;

    $path = drupal_get_path('module', 'webform');
    $build['#attached']['css'][] = $path . '/css/webform.css';
    $build['#attached']['css'][] = $path . '/css/webform-admin.css';
    $build['#attached']['js'][] = $path . '/js/webform.js';
    $build['#attached']['js'][] = $path . '/js/webform-admin.js';
    $build['#attached']['js'][] = $path . '/js/select-admin.js';
    $build['#attached']['js'][] = drupal_get_path('module', 'campaignion_wizard') . '/js/form-builder-submit.js';
    $build['#attached']['library'][] = array('system', 'ui.datepicker');

    // build form for webform_template select box
    $build[] = drupal_get_form('campaignion_wizard_webform_template_selector', $this->wizard->node->type, $build[0]['nid']['#value']) +
      array('#weight' => -99);

    // Load all components.
    $components = webform_components();
    foreach ($components as $component_type => $component) {
      webform_component_include($component_type);
    }
    module_load_include('inc', 'form_builder', 'includes/form_builder.admin');
    foreach (form_builder_interface('webform', $form['nid']['#value']) as $k => $f) {
      $build[$k + 2] = $f;
    }
    $build[3]['submit']['#access'] = FALSE;

    return $build;
  }

  public function stepForm($form, &$form_state) {
    $form = parent::stepForm($form, $form_state);
    $form = \form_builder_webform_save_form($form, $form_state, $this->wizard->node->nid);
    webform_custom_buttons_form_form_builder_webform_save_form_alter($form, $form_state, NULL);
    return $form;
  }

  public function checkDependencies() {
    return isset($this->wizard->node->nid);
  }

  public function validateStep($form, &$form_state) {
    // form_builder seems to use it's ajax callbacks for validation.
  }

  public function submitStep($form, &$form_state) {
    form_builder_webform_save_form_submit($form, $form_state);
  }

  public function status() {
    return NULL;
  }
}
