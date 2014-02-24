<?php

/**
 * @file
 * Example implementation of hooks that are invoked by this module.
 * See: https://drupal.org/node/292 for more information about hooks.
 */

/** 
 * React to a newly logged activity.
 */
function hook_campaignion_activity_save(\Drupal\campaignion\Interfaces\Activity $activity) {
  if (!($activity instanceof \Drupal\campaignion\Activity\WebformSubmission)) {
    return;
  }

  if (!empty($activity->confirmed)) {
    watchdog('campaignion_activity', 'WebformSubmission has been confirmed.');
  }
}
