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

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Render\Markup;

class PdiaImportExportForm extends FormBase {

  public function getFormId() {
    return 'mikedelta_pdia_import_export_form';
  }

  private function getScheme() {
    $field_storage = FieldStorageConfig::loadByName('node', 'field_arquivo');
    return $field_storage ? $field_storage->getSetting('uri_scheme') : 'public';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $scheme = $this->getScheme();

    // ==========================================
    // SEÇÃO 1: IMPORTAÇÃO DE DADOS (UPLOAD ZIP)
    // ==========================================
    $form['importacao'] = [
      '#type' => 'details',
      '#title' => $this->t('Importação (Upload ZIP)'),
      '#open' => TRUE,
    ];

    $form['importacao']['instrucoes_import'] = [
      '#type' => 'markup',
      '#markup' => '
        <div style="padding: 15px; border-left: 5px solid #003366; margin-bottom: 20px; background: #f8f9fa;">
          <h4 style="margin-top: 0;">Como importar arquivos antigos?</h4>
          <p>Compacte os seus arquivos PDF em um único arquivo <strong>.zip</strong> e faça o upload abaixo. O sistema extrairá os documentos, lerá as datas e criará as publicações no calendário automaticamente.</p>
          <p><strong>Regra de Nomenclatura:</strong> Dentro do ZIP, os PDFs devem estar obrigatoriamente nomeados como <code>PD-DDMMAAAA.pdf</code> (Ex: PD-05042026.pdf). Arquivos com nomes diferentes serão ignorados.</p>
        </div>
      ',
    ];

    $form['importacao']['arquivo_zip'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Arquivo Compactado (.zip)'),
      '#description' => $this->t('Tamanho máximo permitido de acordo com a configuração do seu servidor PHP.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['zip'],
      ],
      '#upload_location' => $scheme . '://importacao_temp',
      '#required' => FALSE,
    ];

    $form['importacao']['submit_import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Processar Importação'),
      '#button_type' => 'primary',
      '#submit' => ['::submitImport'],
    ];

    // ==========================================
    // SEÇÃO 2: EXPORTAÇÃO DE BACKUP
    // ==========================================
    $form['exportacao'] = [
      '#type' => 'details',
      '#title' => $this->t('Exportar Backup (Download ZIP)'),
      '#open' => TRUE,
    ];

    $connection = \Drupal::database();
    $query = $connection->select('node__field_data', 'fd');
    $query->addField('fd', 'field_data_value');
    $query->condition('fd.bundle', 'md_pdia');
    $results = $query->execute()->fetchCol();
    
    $anos_disponiveis = [];
    foreach ($results as $date) {
      $ano = substr($date, 0, 4);
      $anos_disponiveis[$ano] = $ano;
    }
    arsort($anos_disponiveis); 

    if (empty($anos_disponiveis)) {
      $form['exportacao']['vazio'] = [
        '#markup' => '<p>Não há Planos do Dia cadastrados no sistema para exportação.</p>',
      ];
    } else {
      $form['exportacao']['instrucoes_export'] = [
        '#type' => 'markup',
        '#markup' => '
          <div style="padding: 15px; border-left: 5px solid #28a745; margin-bottom: 20px; background: #f8f9fa;">
            <h4 style="margin-top: 0;">Por que exportar ano a ano?</h4>
            <p>O sistema gera backups separados por ano para garantir o <strong>máximo de performance e segurança</strong>. Agrupar todo o histórico da OM em um único arquivo ZIP poderia causar o esgotamento da memória RAM do servidor (Timeout) e corromper o arquivo baixado.</p>
            <p>Selecione o ano desejado abaixo. O sistema utilizará processamento em lotes para agrupar os PDFs de forma otimizada e gerar o link de download.</p>
          </div>
        ',
      ];

      $form['exportacao']['ano_export'] = [
        '#type' => 'select',
        '#title' => $this->t('Selecione o Ano'),
        '#options' => $anos_disponiveis,
      ];

      $form['exportacao']['submit_export'] = [
        '#type' => 'submit',
        '#value' => $this->t('Gerar Arquivo ZIP'),
        '#button_type' => 'primary',
        '#submit' => ['::submitExport'],
      ];
    }

    return $form;
  }

  // ==========================================
  // FUNÇÃO: EXECUTAR EXPORTAÇÃO
  // ==========================================
  public function submitExport(array &$form, FormStateInterface $form_state) {
    $ano = $form_state->getValue('ano_export');

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'md_pdia')
      ->condition('field_data', "$ano-01-01", '>=')
      ->condition('field_data', "$ano-12-31", '<=')
      ->accessCheck(FALSE);
    $nids = $query->execute();

    if (empty($nids)) {
      $this->messenger()->addWarning("Nenhum arquivo encontrado para o ano $ano.");
      return;
    }

    $batch = [
      'title' => $this->t('Gerando Arquivo Compactado (ZIP)...'),
      'init_message' => $this->t('Iniciando a compactação dos arquivos. Por favor, aguarde.'),
      'progress_message' => $this->t('Processado @current de @total lotes.'),
      'error_message' => $this->t('Ocorreu um erro durante a criação do arquivo.'),
      'operations' => [
        ['\Drupal\mikedelta_pdia\Form\PdiaImportExportForm::processBatchExport', [$nids, $ano]],
      ],
      'finished' => '\Drupal\mikedelta_pdia\Form\PdiaImportExportForm::finishedBatchExport',
    ];

    batch_set($batch);
  }

  public static function processBatchExport($nids, $ano, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($nids);
      $context['sandbox']['nids'] = array_values($nids);

      $file_system = \Drupal::service('file_system');
      $field_storage = FieldStorageConfig::loadByName('node', 'field_arquivo');
      $scheme = $field_storage ? $field_storage->getSetting('uri_scheme') : 'public';
      
      $backup_dir = $scheme . '://pdia_backups';
      $file_system->prepareDirectory($backup_dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      
      $zip_path = $file_system->realpath($backup_dir) . "/PDia-Backup-$ano.zip";

      $zip = new \ZipArchive();
      if ($zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
        $zip->addFromString('leia-me.txt', "Backup Oficial do Plano do Dia - Ano $ano\nGerado pelo Sistema MikeDelta PDia.");
        $zip->close();
      }
      
      $context['results']['zip_path'] = $zip_path;
      $context['results']['zip_uri'] = "$backup_dir/PDia-Backup-$ano.zip";
      $context['results']['ano'] = $ano;
    }

    $limit = 20; 
    $current_nids = array_slice($context['sandbox']['nids'], $context['sandbox']['progress'], $limit);

    if (!empty($current_nids)) {
      $nodes = Node::loadMultiple($current_nids);
      $zip = new \ZipArchive();
      
      if ($zip->open($context['results']['zip_path']) === TRUE) {
        foreach ($nodes as $node) {
          if (!$node->get('field_arquivo')->isEmpty()) {
            $file = File::load($node->get('field_arquivo')->target_id);
            if ($file) {
              $file_uri = $file->getFileUri();
              $real_path = \Drupal::service('file_system')->realpath($file_uri);
              if (file_exists($real_path)) {
                $zip->addFile($real_path, $file->getFilename());
              }
            }
          }
          $context['sandbox']['progress']++;
        }
        $zip->close();
      }
    }

    $context['message'] = "Adicionando PDFs ao arquivo: " . $context['sandbox']['progress'] . " de " . $context['sandbox']['max'] . " concluídos.";
    
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  public static function finishedBatchExport($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success && isset($results['zip_uri'])) {
      $url = \Drupal::service('file_url_generator')->generateAbsoluteString($results['zip_uri']);
      // Botão verde com as classes do Drupal e CSS inline para o padrão Bootstrap Success
      $link = "<a href='$url' target='_blank' class='button button--primary' style='padding: 10px 20px; font-weight: bold; background-color: #28a745; border-color: #28a745; color: white; text-decoration: none; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block;'>📥 CLIQUE AQUI PARA BAIXAR O BACKUP ({$results['ano']})</a>";
      $messenger->addStatus(Markup::create("<div style='margin: 10px 0;'><strong>Compactação concluída com sucesso!</strong><br><br>$link</div>"));
    } else {
      $messenger->addError('Ocorreu um erro ao gerar o arquivo ZIP de backup.');
    }
  }

  // ==========================================
  // FUNÇÃO: EXECUTAR IMPORTAÇÃO
  // ==========================================
  public function submitImport(array &$form, FormStateInterface $form_state) {
    $file_id_array = $form_state->getValue('arquivo_zip');
    
    if (empty($file_id_array) || !isset($file_id_array[0])) {
      $this->messenger()->addWarning("Por favor, faça o upload de um arquivo .zip primeiro.");
      return;
    }

    $file = File::load($file_id_array[0]);
    if (!$file) {
      $this->messenger()->addError("Erro ao carregar o arquivo ZIP enviado.");
      return;
    }

    $file_system = \Drupal::service('file_system');
    $scheme = $this->getScheme();

    $zip_uri = $file->getFileUri();
    $zip_path = $file_system->realpath($zip_uri);


    $extract_dir_uri = $scheme . '://importacao_extraida_' . time();
    $file_system->prepareDirectory($extract_dir_uri, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $extract_path = $file_system->realpath($extract_dir_uri);

    $zip = new \ZipArchive();
    if ($zip->open($zip_path) === TRUE) {
      $zip->extractTo($extract_path);
      $zip->close();
    } else {
      $this->messenger()->addError("Não foi possível ler o arquivo ZIP. Ele pode estar corrompido.");
      return;
    }


    $arquivos = $file_system->scanDirectory($extract_dir_uri, '/\.pdf$/i');
    $valid_files = [];

    foreach ($arquivos as $arquivo) {
      if (preg_match('/PD-(\d{2})(\d{2})(\d{4})\.pdf$/i', $arquivo->filename, $matches)) {
        $valid_files[] = [
          'uri' => $arquivo->uri,
          'filename' => $arquivo->filename,
          'dia' => $matches[1],
          'mes' => $matches[2],
          'ano' => $matches[3],
        ];
      }
    }

    if (empty($valid_files)) {
      $file_system->deleteRecursive($extract_dir_uri);
      $file->delete();
      $this->messenger()->addWarning("Não foram encontrados PDFs válidos (no formato PD-DDMMAAAA.pdf) dentro do arquivo ZIP.");
      return;
    }


    $batch = [
      'title' => $this->t('Importando Planos do Dia...'),
      'init_message' => $this->t('Lendo PDFs extraídos. Iniciando processo de cadastro...'),
      'progress_message' => $this->t('Processando arquivos... Lote @current de @total.'),
      'error_message' => $this->t('Ocorreu um erro crítico durante a importação.'),
      'operations' => [
        ['\Drupal\mikedelta_pdia\Form\PdiaImportExportForm::processBatchImport', [$valid_files, $extract_dir_uri, $file->id(), $scheme]],
      ],
      'finished' => '\Drupal\mikedelta_pdia\Form\PdiaImportExportForm::finishedBatchImport',
    ];

    batch_set($batch);
  }


  public static function processBatchImport($valid_files, $extract_dir_uri, $zip_file_id, $scheme, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($valid_files);
      $context['sandbox']['files'] = $valid_files;
      
      $context['results']['extract_dir_uri'] = $extract_dir_uri;
      $context['results']['zip_file_id'] = $zip_file_id;
      $context['results']['importados'] = 0;
      $context['results']['erros'] = 0;
    }

    $limit = 10;
    $current_files = array_slice($context['sandbox']['files'], $context['sandbox']['progress'], $limit);

    if (!empty($current_files)) {
      $file_system = \Drupal::service('file_system');
      $file_repository = \Drupal::service('file.repository');

      foreach ($current_files as $file_info) {
        try {
          $dados_arquivo = file_get_contents($file_info['uri']);
          
          $diretorio_destino = "$scheme://md_pdia/" . $file_info['ano'] . "/" . $file_info['mes'];
          $file_system->prepareDirectory($diretorio_destino, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

          $destino_final = "$diretorio_destino/" . $file_info['filename']; 
          $file_entity = $file_repository->writeData($dados_arquivo, $destino_final, FileSystemInterface::EXISTS_REPLACE);

          $node = Node::create([
            'type' => 'md_pdia',
            'title' => "Plano do Dia - " . $file_info['dia'] . "/" . $file_info['mes'] . "/" . $file_info['ano'],
            'field_data' => $file_info['ano'] . "-" . $file_info['mes'] . "-" . $file_info['dia'],
            'field_arquivo' => ['target_id' => $file_entity->id()],
            'status' => 1,
          ]);
          $node->save();
          
          $context['results']['importados']++;
        } catch (\Exception $e) {
          \Drupal::logger('mikedelta_pdia')->error('Erro na importação: @message', ['@message' => $e->getMessage()]);
          $context['results']['erros']++;
        }
        
        $context['sandbox']['progress']++;
      }
    }

    $context['message'] = "Cadastrando PDFs: " . $context['sandbox']['progress'] . " de " . $context['sandbox']['max'] . " concluídos.";
    
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }


  public static function finishedBatchImport($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    $file_system = \Drupal::service('file_system');

    // Remove a pasta temporária extraída
    if (isset($results['extract_dir_uri'])) {
      $file_system->deleteRecursive($results['extract_dir_uri']);
    }
    
    // Deleta o ficheiro ZIP original enviado pelo usuário
    if (isset($results['zip_file_id'])) {
      $file = File::load($results['zip_file_id']);
      if ($file) {
        $file->delete();
      }
    }

    \Drupal::service('cache.render')->invalidateAll();

    if ($success) {
      $importados = $results['importados'] ?? 0;
      $erros = $results['erros'] ?? 0;
      
      if ($importados > 0) {
        $messenger->addStatus("$importados Planos do Dia foram importados com sucesso para o calendário!");
      }
      if ($erros > 0) {
        $messenger->addWarning("A importação terminou, mas $erros arquivos apresentaram falhas de gravação (Possivelmente PDFs corrompidos).");
      }
    } else {
      $messenger->addError('Ocorreu um erro crítico durante a importação em lote.');
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}
}