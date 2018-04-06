<?php

namespace Drupal\campaignion_newsletters;

use Drupal\campaignion\Contact;
use Drupal\campaignion\CRM\Import\Source\WebformSubmission;
use Drupal\campaignion_newsletters\Subscription;

/**
 * Test the Component functionality.
 */
class ComponentTest extends \DrupalUnitTestCase {

  /**
   * Create a contact and some lists for testing.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Clean up the test contact.
   */
  public function tearDown() {
    if ($c = Contact::byEmail('test@example.com')) {
      entity_delete('redhen_contact', $c->contact_id);
    }
    db_delete('campaignion_newsletters_subscriptions')->execute();
    db_delete('campaignion_newsletters_queue')->execute();
    parent::tearDown();
  }

  /**
   * Test subscribing to a new list.
   */
  public function testSubscribe() {
    $component = ['cid' => 1, 'pid' => 0, 'form_key' => 'newsletter'];
    $component['extra']['lists'][1] = 1;
    $c = new Component($component, FALSE);
    $s = $this->createMock(WebformSubmission::class);
    $s->node = (object) [
      'webform' => [
        'components' => [1 => $component]
      ],
    ];
    $c->subscribe('test@example.com', $s);
    $subscriptions = Subscription::byEmail('test@example.com');
    $this->assertCount(1, $subscriptions);
  }

  /**
   * Test unscribing a contact.
   */
  public function testUnsubscribe() {
    $e = 'test@example.com';
    Subscription::byData(1, $e)->save();
    Subscription::byData(2, $e)->save();
    $this->assertCount(2, Subscription::byEmail($e));

    $component = ['cid' => 1, 'pid' => 0, 'form_key' => 'newsletter'];
    $component['extra']['lists'][1] = 1;
    $component['extra']['optout_all_lists'] = FALSE;
    $c = new Component($component, FALSE);
    $c->unsubscribe($e);
    $this->assertCount(1, Subscription::byEmail($e));
    $this->assertNotEmpty(QueueItem::load(1, $e));

    $component['extra']['optout_all_lists'] = TRUE;
    $c = new Component($component, FALSE);
    $c->unsubscribe($e);
    $this->assertCount(0, Subscription::byEmail($e));
    $this->assertNotEmpty(QueueItem::load(2, $e));

    $c = new Component($component, TRUE);
    $c->setAllListIds([1, 2, 3]);
    $c->unsubscribe($e);
    $this->assertNotEmpty(QueueItem::load(3, $e));
  }

}
