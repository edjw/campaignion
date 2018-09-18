<?php

namespace Drupal\campaignion_form_builder;

use \FormBuilderLoader;

/**
 * Test for additional fields we’ve put into the form builder palette.
 */
class PaletteTest extends \DrupalUnitTestCase {

  /**
   * Test whether opt-in fields are added.
   */
  public function testOptInFields() {
    $form_type = 'webform';
    $loader = FormBuilderLoader::instance();
    $fields = $loader->getElementTypeInfo('webform', 0);

    $this->assertArrayHasKey('post_opt_in', $fields);
    $this->assertArrayHasKey('phone_opt_in', $fields);
  }

}
