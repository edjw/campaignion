<?php

namespace Drupal\campaignion\Wizard;

class PetitionWebformStep extends WebformStep {
  public function status() {
    return array(
      'caption' => t('Your form is ready to go'),
      'message' => t('You have the following fields on your form: TODO.'),
    );
  }
}
