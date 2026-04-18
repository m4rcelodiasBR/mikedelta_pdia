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
use Drupal\file\Entity\File;

class PdiaSettingsForm extends ConfigFormBase
{

  protected function getEditableConfigNames()
  {
    return ['mikedelta_pdia.settings'];
  }

  public function getFormId()
  {
    return 'mikedelta_pdia_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('mikedelta_pdia.settings');

    $flatten_fids = function($data) {
      if (empty($data)) return [];
      $fids = [];
      if (!is_array($data)) return [(int) $data];
      array_walk_recursive($data, function($val) use (&$fids) {
        if (is_numeric($val)) $fids[] = (int) $val;
      });
      return array_unique($fids);
    };

    $fundo_imagens_limpo = $flatten_fids($config->get('fundo_imagens'));

    $form['admin_actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 20px;',
      ],
    ];

    $url_calendario = Url::fromRoute('mikedelta_pdia.calendar');
    $form['admin_actions']['ir_calendario'] = [
      '#title' => $this->t('Ir para o Plano do Dia'),
      '#type' => 'link',
      '#url' => $url_calendario,
      '#attributes' => [
        'class' => ['button', 'button--primary', 'btn', 'btn-primary'],
      ],
    ];

    $url_ajuda = Url::fromRoute('help.page', ['name' => 'mikedelta_pdia']);
    $form['admin_actions']['ajuda'] = [
      '#title' => $this->t('Ajuda do Módulo'),
      '#type' => 'link',
      '#url' => $url_ajuda,
      '#attributes' => [
        'class' => ['button', 'btn', 'btn-secondary'],
      ],
    ];

    $form['explicacao_adicionais'] = [
      '#type' => 'markup',
      '#markup' => '
        <div>
          <h4>Feriados Regionais e Pontos Facultativos</h4>
          <p>O motor do sistema já calcula matematicamente todos os <strong>Feriados Nacionais</strong> para qualquer ano. Utilize este campo <strong>apenas</strong> para adicionar datas exclusivas da Marinha do Brasil, sua localidade ou OM.</p>
          <p><strong>Regras de preenchimento:</strong> Insira um evento por linha, separando a data do nome por uma barra vertical ( <strong>|</strong> ).</p>
          <ul>
            <li>Para repetir <strong>todos os anos</strong>, digite apenas Dia e Mês: <code>DD/MM | Nome do Feriado</code></li>
            <li>Para <strong>um ano específico</strong>, digite a data completa: <code>DD/MM/AAAA | Nome do Feriado</code></li>
          </ul>
          <p>Exemplos:</p>
          <pre><code>20/01 | Dia de São Sebastião (Feriado Regional)
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

    $form['configuracoes_fundo'] = [
      '#type' => 'details',
      '#title' => $this->t('Personalização: Imagens de Fundo do Calendário'),
      '#open' => TRUE,
    ];

    $form['configuracoes_fundo']['fundo_ativo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar imagens aleatórias no fundo do calendário'),
      '#default_value' => $config->get('fundo_ativo') ?? TRUE,
    ];

    $form['configuracoes_fundo']['fundo_cor'] = [
      '#type' => 'color',
      '#title' => $this->t('Cor de Fundo'),
      '#default_value' => $config->get('fundo_cor') ?? 'transparent',
      '#description' => $this->t('Esta cor será aplicada como fundo sólido ou translúcido (conforme brilho abaixo).'),
      '#states' => [
        'visible' => [
          ':input[name="fundo_ativo"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['configuracoes_fundo']['fundo_opacidade'] = [
      '#type' => 'select',
      '#title' => $this->t('Brilho'),
      '#description' => $this->t('Controla o quanto a imagem ficará esbranquiçada para não atrapalhar a leitura do calendário.'),
      '#options' => [
        '0' => '0%',
        '0.1' => '10%', 
        '0.2' => '20%', 
        '0.3' => '30%', 
        '0.4' => '40%', 
        '0.5' => '50%',
        '0.6' => '60%', 
        '0.7' => '70%', 
        '0.8' => '80% (Recomendado)', 
        '0.9' => '90%', 
        '1' => '100%'
      ],
      '#default_value' => $config->get('fundo_opacidade') ?? '0.8',
    ];

    $form['configuracoes_fundo']['grupo_imagens'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="fundo_ativo"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['configuracoes_fundo']['grupo_imagens']['fundo_imagens'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload de Imagens de Fundo'),
      '#description' => $this->t('Faça o upload de até 8 imagens diferentes (Tamanho máximo: 1MB/arquivo). Formatos aceitos: JPG, JPEG ou PNG.'),
      '#multiple' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png'],
        'file_validate_size' => [1 * 1024 * 1024],
      ],
      '#upload_location' => 'public://md_pdia_walls/',
      '#default_value' => $fundo_imagens_limpo,
      '#element_validate' => ['::validarQuantidadeImagens'],
    ];

    $form['configuracoes_fundo']['grupo_imagens']['observacao_delecao'] = [
      '#type' => 'markup',
      '#markup' => '
      <p>Ao excluir uma imagem, ela será removida do calendário, porém não será apagada do servidor. Utilize o <a href="/admin/content/files">gerenciamento de arquivos do Drupal</a> para apagar permanentemente o arquivo do servidor.</p>
      <p><strong>Dica:</strong> Na lista de arquivos verifique o nome do arquivo e a coluna <em>"Usado em"</em> para identificar em quantos locais ela esta sendo utilizada. Se for 0 (zero), ela pode ser apagada sem problemas.</p>
      ',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validarQuantidadeImagens(array &$element, FormStateInterface $form_state) {
    $data = $form_state->getValue('fundo_imagens');

    $fids = [];
    if (!empty($data)) {
      if (!is_array($data)) {
        $data = [$data];
      }

      array_walk_recursive($data, function($val) use (&$fids) {
        if (is_numeric($val) && $val > 0) { 
          $fids[] = (int) $val;
        }
      });
    }

    $total_imagens = count(array_unique($fids));

    if ($total_imagens > 8) {
      $form_state->setError($element, $this->t('Limite excedido: Você só pode manter um máximo de 8 imagens cadastradas. (Atual: ' . $total_imagens . ')'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mikedelta_pdia.settings');
    $file_system = \Drupal::service('file_system');
    $file_usage = \Drupal::service('file.usage');

    $flatten_fids = function($data) {
      if (empty($data)) return [];
      $fids = [];
      array_walk_recursive($data, function($val) use (&$fids) {
        if (is_numeric($val)) $fids[] = (int) $val;
      });
      return array_unique($fids);
    };

    $old_fids = $flatten_fids($config->get('fundo_imagens'));
    $new_fids = $flatten_fids($form_state->getValue('fundo_imagens'));

    $removed_fids = array_diff($old_fids, $new_fids);
    if (!empty($removed_fids)) {
      $files = \Drupal\file\Entity\File::loadMultiple($removed_fids);
      foreach ($files as $file) {
        $file_usage->delete($file, 'mikedelta_pdia', 'config_form', 1);
      }
    }

    if (!empty($new_fids)) {
      $files = \Drupal\file\Entity\File::loadMultiple($new_fids);
      $count = 1;
      foreach ($files as $file) {
        $extension = pathinfo($file->getFileUri(), PATHINFO_EXTENSION);
        $novo_nome = 'wall-' . $count . '.' . $extension;
        $destino_final = 'public://md_pdia_walls/' . $novo_nome;

        // Só move e renomeia se o nome atual for diferente do padrão wall-N
        if ($file->getFilename() !== $novo_nome) {
          try {
            $file_system->move($file->getFileUri(), $destino_final, $file_system::EXISTS_REPLACE);
            $file->setFileUri($destino_final);
            $file->setFilename($novo_nome);
          } catch (\Exception $e) {
            \Drupal::logger('mikedelta_pdia')->error('Erro ao renomear imagem: ' . $e->getMessage());
          }
        }

        if ($file->isTemporary()) {
          $file->setPermanent();
          $file_usage->add($file, 'mikedelta_pdia', 'config_form', 1);
        }
        $file->save();
        $count++;
      }
    }

    $config->set('feriados_adicionais', $form_state->getValue('feriados_adicionais'))
      ->set('fundo_ativo', $form_state->getValue('fundo_ativo'))
      ->set('fundo_opacidade', $form_state->getValue('fundo_opacidade'))
      ->set('fundo_cor', $form_state->getValue('fundo_cor'))
      ->set('fundo_imagens', $new_fids)
      ->save();
      
    parent::submitForm($form, $form_state);
  }
}