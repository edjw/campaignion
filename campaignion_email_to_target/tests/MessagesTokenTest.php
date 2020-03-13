<?php

namespace Drupal\campaignion_email_to_target;

use Upal\DrupalUnitTestCase;

require_once drupal_get_path('module', 'webform') . '/includes/webform.components.inc';

/**
 * Test output of the messages token.
 */
class MessagesTokenTest extends DrupalUnitTestCase {

  /**
   * Create a node-stub suitable for testing.
   */
  protected function nodeStub(array $components) {
    foreach ($components as $cid => &$component) {
      webform_component_defaults($component);
      $component += [
        'cid' => $cid,
        'pid' => 0,
      ];
    }
    $node_d = [
      'nid' => NULL,
      'type' => 'email_to_target',
      'action' => FALSE,
    ];
    $node_d['webform']['components'] = $components;
    $node = (object) $node_d;
    node_object_prepare($node);
    return $node;
  }

  /**
   * Create a submission-stub suitable for testing.
   */
  protected function submissionStub($data) {
    return (object) [
      'nid' => NULL,
      'sid' => 'stub-sid',
      'data' => $data,
      'completed' => TRUE,
    ];
  }

  /**
   * Test rendering the message token for a single message.
   */
  public function testOneMessage() {
    $components[1] = [
      'type' => 'e2t_selector',
      'form_key' => 'e2t_messages',
    ];
    $message = new Message([
      'to' => 'to@example.com',
      'from' => 'from@example.com',
      'subject' => 'Subject line',
      'header' => 'Header',
      'message' => 'Content',
      'footer' => 'Footer',
    ]);
    $data[1][] = serialize(['message' => $message->toArray()]);
    $token_data['node'] = $this->nodeStub($components);
    $token_data['webform-submission'] = $this->submissionStub($data);

    $actual = token_replace('[submission:email-to-target-messages]', $token_data);
    $expected = <<<STR
<p>Email to: to@example.com with subject line “Subject line”</p>

HeaderContentFooter
STR;
    $this->assertEqual($expected, $actual);
  }

}
