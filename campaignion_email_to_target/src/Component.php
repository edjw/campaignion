<?php

namespace Drupal\campaignion_email_to_target;

use \Drupal\little_helpers\Webform\Webform;
use \Drupal\campaignion_action\Loader;

use \Drupal\campaignion_email_to_target\Api\Client;

/**
 * Implement behavior for the email to target message webform component.
 */
class Component {

  protected $component;
  protected $webform;
  protected $action;
  protected $options;

  /**
   * Static constructor to inject dependencies based on a component array.
   */
  public static function fromComponent(array $component) {
    $node = node_load($component['nid']);
    $webform = new Webform($node);
    $action = Loader::instance()->actionFromNode($node);
    return new static($component, $webform, $action);
  }

  public function __construct(array $component, Webform $webform, Action $action) {
    $this->component = $component;
    $this->webform = $webform;
    $this->action = $action;
  }

  /**
   * Get a list of parent form keys for this component.
   *
   * @return array
   *   List of parent form keys - just like $element['#parents'].
   */
  public function parents($webform) {
    $parents = [$this->component['form_key']];
    $parent = $this->component;
    while ($parent['pid'] != 0) {
      $parent = $webform->component($parent['pid']);
      array_unshift($parents, $parent['form_key']);
    }
    return $parents;
  }

  /**
   * Disable submit-buttons for this form.
   */
  protected function disableSubmits(&$form) {
    $form['actions']['#access'] = FALSE;
  }

  /**
   * Render the webform component.
   */
  public function render(&$element, &$form, &$form_state) {
    // Get list of targets for this node.
    $submission_o = $this->webform->formStateToSubmission($form_state);
    $options = $this->action->getOptions();

    $test_mode = !empty($form_state['test_mode']);
    $email = $submission_o->valueByKey('email');

    $element = [
      '#type' => 'fieldset',
      '#theme' => 'campaignion_email_to_target_selector_component',
    ] + $element + [
      '#theme_wrappers' => ['fieldset', 'webform_element'],
      '#title' => $this->component['name'],
      '#description' => $this->component['extra']['description'],
      '#tree' => TRUE,
      '#element_validate' => array('campaignion_email_to_target_selector_validate'),
      '#cid' => $this->component['cid'],
    ];

    if ($test_mode) {
      $element['test_mode'] = [
        '#prefix' => '<p class="test-mode-info">',
        '#markup' => t('Test-mode is active: All emails will be sent to %email.', ['%email' => $email]),
        '#suffix' => '</p>',
      ];
    }

    $element['#attributes']['class'][] = 'email-to-target-selector-wrapper';
    $element['#attributes']['class'][] = 'webform-prefill-exclude';

    try {
      list($pairs, $no_target_element) = $this->action->targetMessagePairs($submission_o, $test_mode);
    }
    catch (\Exception $e) {
      watchdog_exception('campaignion_email_to_target', $e);
      $element['#title'] = t('Service temporarily unavailable');
      $element['error'] = [
        '#markup' => t('We are sorry! The service is temporarily unavailable. The administrators have been informed. Please try again in a few minutes …'),
      ];
      $element['#attributes']['class'][] = 'email-to-target-error';
      $this->disableSubmits($form);
      return;
    }

    if (empty($pairs)) {
      $element['no_target'] = $no_target_element;
      $element['#attributes']['class'][] = 'email-to-target-no-targets';
      $this->disableSubmits($form);
      return;
    }

    $last_id = NULL;
    foreach ($pairs as $p) {
      list($target, $message) = $p;
      $t = [
        '#type' => 'container',
        '#attributes' => ['class' => ['email-to-target-target']],
        '#target' => $target,
        '#message' => $message->toArray(),
      ];
      $t['send'] = [
        '#type' => 'checkbox',
        '#title' => $message->display,
        '#default_value' => TRUE,
      ];
      $t['subject'] = [
        '#type' => 'textfield',
        '#title' => t('Subject'),
        '#default_value' => $message->subject,
        '#disabled' => empty($options['users_may_edit']),
      ];
      $t['header'] = [
        '#prefix' => '<pre class="email-to-target-header">',
        '#markup' => check_plain($message->header),
        '#suffix' => '</pre>',
      ];
      $t['message'] = [
        '#type' => 'textarea',
        '#title' => t('Message'),
        '#default_value' => $message->message,
        '#disabled' => empty($options['users_may_edit']),
      ];
      $t['footer'] = [
        '#prefix' => '<pre class="email-to-target-footer">',
        '#markup' => check_plain($message->footer),
        '#suffix' => '</pre>',
      ];
      $element[$target['id']] = $t;
      $last_id = $target['id'];
    }

    $form_state['send_all'] = FALSE;
    if (count($pairs) == 1 || $options['selection_mode'] == 'all') {
      $form_state['send_all'] = TRUE;
      foreach (element_children($element) as $k) {
        $c = &$element[$k];
        $c['#attributes']['class'][] = 'email-to-target-single';
        $c['send']['#type'] = 'markup';
        $c['send']['#markup'] = "<p class=\"target\">{$c['send']['#title']} </p>";
      }
    }
    if (count($pairs) > 1) {
      $element['#attached']['js'] = [drupal_get_path('module', 'campaignion_email_to_target') . '/js/target_selector.js'];
    }
  }

  /**
   * Validate the user input to the form component.
   */
  public function validate(array $element, array &$form_state) {
    $values = &drupal_array_get_nested_value($form_state['values'], $element['#parents']);

    $original_values = $values;
    $values = [];
    foreach ($original_values as $id => $edited_message) {
      if (!empty($edited_message['send']) || $form_state['send_all']) {
        $e = &$element[$id];
        $values[] = serialize([
          'message' => $edited_message + $e['#message'],
          'target' => $e['#target'],
        ]);
      }
    }
    if (empty($values)) {
      form_error($element, t('Please select at least one target'));
    }
  }

  /**
   * Send emails to all selected targets.
   */
  public function sendEmails($data, $submission) {
    $nid = $submission->nid;
    $node = $submission->webform->node;
    $root_node = $node->tnid ? node_load($node->tnid) : $node;
    $send_count = 0;

    foreach ($data as $serialized) {
      $m = unserialize($serialized);
      $message = new Message($m['message']);
      $message->replaceTokens(NULL, $submission->unwrap());
      unset($m);

      // Set the HTML property based on availablity of MIME Mail.
      $email['html'] = FALSE;
      // Pass through the theme layer.
      $t = 'campaignion_email_to_target_mail';
      $theme_d = ['message' => $message, 'submission' => $submission];
      $email['message'] = theme([$t, $t . '_' . $nid], $theme_d);

      $email['from'] = $message->from;
      $email['subject'] = $message->subject;

      $email['headers'] = [
        'X-Mail-Domain' => variable_get('site_mail_domain', 'supporter.campaignion.org'),
        'X-Action-UUID' => $root_node->uuid,
      ];

      // Verify that this submission is not attempting to send any spam hacks.
      if (_webform_submission_spam_check($message->to, $email['subject'], $email['from'], $email['headers'])) {
        watchdog('campaignion_email_to_target', 'Possible spam attempt from @remote !message',
                array('@remote' => ip_address(), '!message' => "<br />\n" . nl2br(htmlentities($email['message']))));
        drupal_set_message(t('Illegal information. Data not submitted.'), 'error');
        return FALSE;
      }

      $language = $GLOBALS['language'];
      $mail_params = array(
        'message' => $email['message'],
        'subject' => $email['subject'],
        'headers' => $email['headers'],
        'submission' => $submission,
        'email' => $email,
      );

      // Mail the submission.
      $m = drupal_mail('campaignion_email_to_target', 'email_to_target', $message->to, $language, $mail_params, $email['from']);
      if ($m['result']) {
        $send_count += 1;
      }
    }
  }

}
