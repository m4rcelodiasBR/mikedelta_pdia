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

use Drupal\Core\Url;

class PdiaAdminController extends PdiaCalendarController {

  public function buildAdmin() {
    $build = parent::build();

    $build['admin_actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;',
      ],
      '#weight' => -20,
      'ir_calendario' => [
        '#title' => $this->t('Ir para o Plano do Dia'),
        '#type' => 'link',
        '#url' => Url::fromRoute('mikedelta_pdia.calendar'),
        '#attributes' => ['class' => ['button', 'button--primary', 'btn', 'btn-primary']],
      ],
      'ajuda' => [
        '#title' => $this->t('Ajuda do Módulo'),
        '#type' => 'link',
        '#url' => Url::fromRoute('help.page', ['name' => 'mikedelta_pdia']),
        '#attributes' => ['class' => ['button', 'btn', 'btn-secondary']],
      ],
    ];

    $build['instrucoes_calendario'] = [
      '#markup' => '
        <div>
          <h4>Visão Gerencial e Controle de Licenças</h4>
          <p>Este calendário espelha exatamente a visão pública. Utilize esta interface para auditar a exibição dos PDFs e gerenciar as rotinas exclusivas da OM.</p>
          <p><strong>Como gerenciar as Licenças e Rotinas:</strong></p>
          <ol>
            <li>Clique no botão azul <strong>Controle de Licenças</strong>.</li>
            <li>No painel, consulte a lista de eventos já registrados para o mês em visualização.</li>
            <li>Para inserir uma nova rotina, selecione a data exata e o tipo de evento (ex: Licença Pagamento). Se for algo fora do padrão, selecione "Outros" e digite o nome específico.</li>
            <li><strong>Atenção:</strong> Não é possível adicionar essas licenças em dias que já possuem feriados e fins de semana.</li>
          </ol>
        </div>
      ',
      '#weight' => -10,
    ];

    $build['calendario']['#theme'] = 'mikedelta_pdia_admin_calendar';
    $build['calendario']['#attached']['library'][] = 'mikedelta_pdia/pdia_admin_assets';
    unset($build['titulo_pagina']);
    return $build;
  }
}