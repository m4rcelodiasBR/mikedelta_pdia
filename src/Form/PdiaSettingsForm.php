<?php

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

    $form['explicacao_feriados'] = [
      '#type' => 'markup',
      '#markup' => '
        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-bottom: 15px; color: #856404;">
          <h4 style="margin-top: 0;">Feriados Adicionais e Formatação JSON</h4>
          <p>Esta função serve para cadastrar datas que não estão no calendário nacional padrão, como feriados municipais, datas comemorativas da OM ou pontos facultativos específicos.</p>
          <p><strong>Regras de preenchimento:</strong></p>
          <ul>
            <li>O conteúdo deve ser um JSON válido (começa com <code>[</code> e termina com <code>]</code>).</li>
            <li>Cada evento deve ter os campos <code>"date"</code> (no formato AAAA-MM-DD) e <code>"name"</code> (o nome que aparecerá no calendário).</li>
            <li>Use aspas duplas em todos os nomes de campos e valores.</li>
          </ul>
        </div>
      ',
    ];

    $form['feriados_custom'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Adicione o Formato JSON aqui'),
      '#description' => $this->t('Cole aqui o conteúdo de um arquivo JSON caso precise sobrescrever ou adicionar feriados. Exemplo: [{"date": "2026-09-07", "name": "Independência do Brasil", "type": "national"}]'),
      '#default_value' => $config->get('feriados_custom'),
      '#rows' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mikedelta_pdia.settings')
      ->set('feriados_custom', $form_state->getValue('feriados_custom'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}