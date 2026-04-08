<?php

namespace Drupal\mikedelta_pdia\Controller;

class PdiaAdminController extends PdiaCalendarController {

  public function buildAdmin() {
    $build = parent::build();
    $build['calendario']['#theme'] = 'mikedelta_pdia_admin_calendar';
    $build['calendario']['#attached']['library'][] = 'mikedelta_pdia/pdia_admin_assets';
    unset($build['titulo_pagina']);
    return $build;
  }
}