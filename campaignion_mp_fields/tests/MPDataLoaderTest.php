<?php

namespace Drupal\campaignion_mp_fields;

use Drupal\campaignion_email_to_target\Api\Client;
use Drupal\campaignion\Contact;

/**
 * Test the MP data loader.
 */
class MPDataLoaderTest extends \DrupalUnitTestCase {

  /**
   * Create a data loader with a mocked API-Client.
   */
  public function createDataLoader(&$output) {
    $api = $this->createMock(Client::class);
    $test_data[0] = [
      'contacts' => [
        [
          'last_name' => 'Test',
          'political_affiliation' => 'Labour',
          'salutation' => 'Catherine Test MP',
          'title' => 'Ms',
          'id' => '387269',
          'email' => 'mp@parliament.uk',
          'first_name' => 'Catherine',
        ],
      ],
      'country' => [
        'code' => 'E',
        'name' => 'England',
        'id' => 1,
      ],
      'name' => 'Hornsey and Wood Green',
      'type' => 'WMC',
      'id' => 22544,
    ];
    $api->method('getTargets')->willReturn($test_data);
    // The field itself is unused in this test. we can use any existing field.
    $setters['field_address'] = function ($field, $constituency, $target) use (&$output) {
      if (!empty($constituency['name'])) {
        $output = $constituency['name'];
      }
    };
    return [$api, new MPDataLoader($api, $setters)];
  }

  /**
   * Test setting the data.
   */
  public function testSetData() {
    $output = NULL;
    list($api, $mpd) = $this->createDataLoader($output);
    $api->expects($this->once())->method('getTargets')
      ->with($this->equalTo('mp'), $this->equalTo('N103DE'));

    $contact = entity_create('redhen_contact', ['type' => 'contact']);
    $contact->wrap()->field_address->set([
      // Ignored: No postal_code.
      [
        'country' => 'AT',
        'postal_code' => '',
      ],
      // Ignored: Not in GB.
      [
        'country' => 'AT',
        'postal_code' => '1140',
      ],
      // Ignored: Invalid postal_code.
      [
        'country' => 'GB',
        'postal_code' => 'N10Ä3DE',
      ],
      // This one’s taken.
      [
        'country' => 'GB',
        'postal_code' => 'N10 3DE',
      ],
    ]);
    $mpd->setData('redhen_contact', $contact);
    $this->assertEqual($output, 'Hornsey and Wood Green');
  }

}

