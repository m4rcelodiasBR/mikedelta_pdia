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

    $build['instrucoes_calendario'] = [
      '#markup' => '
        <div style="background: #e9ecef; padding: 20px; border-left: 5px solid #003366; margin-bottom: 20px; border-radius: 4px;">
          <h3 style="margin-top: 0; color: #003366;">Visão Gerencial e Controle de Licenças</h3>
          <p>Este calendário espelha exatamente a visão pública. Utilize esta interface para auditar a exibição dos PDFs e gerenciar as rotinas exclusivas da OM.</p>
          <p><strong>Como gerenciar as Licenças e Rotinas:</strong></p>
          <ol>
            <li>Clique no botão azul <strong>Controle de Licenças</strong>.</li>
            <li>No painel, consulte a lista de eventos já registrados para o mês em visualização.</li>
            <li>Para inserir uma nova rotina, selecione a data exata e o tipo de evento (ex: Licença Pagamento). Se for algo fora do padrão, selecione "Outros" e digite o nome específico.</li>
            <li>Ao salvar, a rotina será destacada no calendário. <strong>Nota:</strong> As licenças cadastradas aqui possuem prioridade visual máxima e irão se sobrepor aos Feriados Nacionais caso caiam na mesma data.</li>
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