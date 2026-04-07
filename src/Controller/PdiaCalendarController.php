<?php

namespace Drupal\mikedelta_pdia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

class PdiaCalendarController extends ControllerBase {

  public function build() {
    $ano_atual = date('Y');
    
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'md_pdia')
      ->condition('status', 1)
      ->accessCheck(FALSE);
    $nids = $query->execute();
    
    $arquivos_por_data = [];
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $data = $node->get('field_data')->value;
      if (!$node->get('field_arquivo')->isEmpty()) {
        $file = File::load($node->get('field_arquivo')->target_id);
        if ($file) {
          $arquivos_por_data[$data] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
      }
    }

    $config = \Drupal::config('mikedelta_pdia.settings');
    $licencas = $config->get('licencas') ?? [];

    $feriados = [];
    $module_path = \Drupal::service('extension.list.module')->getPath('mikedelta_pdia');
    
    $arquivos_feriados = glob($module_path . '/cache_feriados/feriados_*.json');
    if ($arquivos_feriados) {
        foreach ($arquivos_feriados as $arquivo) {
            $feriados_ano = json_decode(file_get_contents($arquivo), TRUE) ?? [];
            if (is_array($feriados_ano)) {
                $feriados = array_merge($feriados, $feriados_ano);
            }
        }
    }

    $feriados_custom_str = $config->get('feriados_custom');
    if (!empty($feriados_custom_str)) {
        $feriados_custom = json_decode($feriados_custom_str, TRUE) ?? [];
        if (is_array($feriados_custom)) {
            $feriados = array_merge($feriados, $feriados_custom);
        }
    }

    $datas_feriados = [];
    foreach ($feriados as $feriado) {
        $data_str = '';
        if (isset($feriado['date'])) {
            $data_str = $feriado['date'];
        } elseif (isset($feriado['data'])) {
            $partes = explode('/', $feriado['data']);
            if (count($partes) == 3) {
                $data_str = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
            }
        }
        if ($data_str) {
            $datas_feriados[$data_str] = $feriado['name'] ?? 'Feriado Nacional';
        }
    }

    $caminho_svg = '/' . \Drupal::service('extension.list.module')->getPath('mikedelta_pdia') . '/assets/svg/file-pdf.svg';

    $dados_frontend = [
      'arquivos' => $arquivos_por_data,
      'licencas' => $licencas,
      'feriados' => $datas_feriados,
      'ano_base' => $ano_atual,
      'icone_pdf' => $caminho_svg,
    ];

    $build = [];

    $build['titulo_pagina'] = [
      '#markup' => '<h1 class="page-title mb-4">' . $this->t('Plano do Dia') . '</h1>',
    ];

    $build['calendario'] = [
      '#theme' => 'mikedelta_pdia_calendar',
      '#dados_calendario' => $dados_frontend,
      '#attached' => [
        'library' => ['mikedelta_pdia/pdia_calendar_assets'],
        'drupalSettings' => ['mikedeltaPdia' => $dados_frontend],
      ],
      '#cache' => [
        'tags' => ['node_list:md_pdia', 'config:mikedelta_pdia.settings'],
      ],
    ];

    return $build;
  }
}