<?php

namespace Drupal\mikedelta_pdia\Controller;

class PdiaAdminController extends PdiaCalendarController {

  public function buildAdmin() {
    $build = parent::build();
    $build['#theme'] = 'mikedelta_pdia_admin_calendar';
    $build['#attached']['library'] = ['mikedelta_pdia/pdia_admin_assets'];

    return $build;
  }
}