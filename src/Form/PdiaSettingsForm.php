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


namespace Drupal\mikedelta_pdia\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PdiaSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['mikedelta_pdia.settings'];
  }

  public function getFormId() {
    return 'mikedelta_pdia_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mikedelta_pdia.settings');

    // Container para alinhar os botões à direita
    $form['admin_actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;',
      ],
    ];

    // Botão: Ir para o Calendário Público
    $url_calendario = Url::fromRoute('mikedelta_pdia.calendar');
    $form['admin_actions']['ir_calendario'] = [
      '#title' => $this->t('Ir para o Plano do Dia'),
      '#type' => 'link',
      '#url' => $url_calendario,
      '#attributes' => [
        'class' => ['button', 'button--primary', 'btn', 'btn-primary'],
        'target' => '_blank',
      ],
    ];

    // Botão: Ajuda do Módulo (Usando o padrão do Core)
    $url_ajuda = Url::fromRoute('help.page', ['name' => 'mikedelta_pdia']);
    $form['admin_actions']['ajuda'] = [
      '#title' => $this->t('Ajuda do Módulo'),
      '#type' => 'link',
      '#url' => $url_ajuda,
      '#attributes' => [
        'class' => ['button', 'btn', 'btn-secondary'],
      ],
    ];

    // --- DENTRO DA FUNÇÃO buildForm() ---

    // Novo Texto Explicativo Unificado
    $form['explicacao_adicionais'] = [
      '#type' => 'markup',
      '#markup' => '
        <div style="background: #e2e3e5; padding: 15px; border-left: 4px solid #0056b3; margin-bottom: 15px; color: #383d41;">
          <h4 style="margin-top: 0;">Feriados Regionais e Específicos</h4>
          <p>O sistema já possui os Feriados Nacionais na memória. Use este campo para adicionar Feriados do Estado/Município ou Pontos Facultativos da sua OM.</p>
          <p><strong>Regras de preenchimento:</strong> Um evento por linha, separado por uma barra vertical ( <strong>|</strong> ).</p>
          <ul>
            <li>Para repetir <strong>todos os anos</strong>, digite apenas Dia e Mês: <code>DD/MM | Nome</code></li>
            <li>Para <strong>um ano específico</strong>, digite a data completa: <code>DD/MM/AAAA | Nome</code></li>
          </ul>
          <pre style="background: #212529; color: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>20/01 | Dia de São Sebastião (Feriado Regional)
          15/08/2026 | Ponto Facultativo Excepcional</code></pre>
        </div>
      ',
    ];

    $form['feriados_adicionais'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Lista de Feriados Adicionais'),
      '#default_value' => $config->get('feriados_adicionais'),
      '#rows' => 6,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  $this->config('mikedelta_pdia.settings')
    ->set('feriados_adicionais', $form_state->getValue('feriados_adicionais'))
    ->save();
  parent::submitForm($form, $form_state);
}
}