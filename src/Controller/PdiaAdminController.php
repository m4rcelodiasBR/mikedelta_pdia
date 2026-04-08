<?php

/**
 * MikeDelta PDia - Módulo para gerenciamento e exibição do Plano do Dia (PDia).
 * Copyright (C) 2026 Todos os direitos reservados.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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