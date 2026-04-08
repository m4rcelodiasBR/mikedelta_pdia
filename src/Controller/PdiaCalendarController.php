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

    // --- LÓGICA UNIFICADA DE FERIADOS ---
    $feriados_nacionais = []; // Lidos dos JSONs
    
    // 1. Lê TODOS os ficheiros nativos de cache (NACIONAIS)
    $module_path = \Drupal::service('extension.list.module')->getPath('mikedelta_pdia');
    $arquivos_feriados = glob($module_path . '/cache_feriados/feriados_*.json');
    if ($arquivos_feriados) {
        foreach ($arquivos_feriados as $arquivo) {
            $feriados_ano = json_decode(file_get_contents($arquivo), TRUE) ?? [];
            foreach ($feriados_ano as $f) {
                // Padroniza a data para YYYY-MM-DD
                $d = $f['date'] ?? null;
                if (!$d && isset($f['data'])) {
                    $p = explode('/', $f['data']);
                    if (count($p) == 3) $d = $p[2].'-'.$p[1].'-'.$p[0];
                }
                if ($d) $feriados_nacionais[$d] = $f['name'] ?? 'Feriado Nacional';
            }
        }
    }

    // 2. Lê os Adicionais Unificados do Painel
    $texto_adicionais = $config->get('feriados_adicionais');
    $feriados_regionais = []; // DD/MM (Recorrentes)
    $feriados_especificos = []; // DD/MM/YYYY (Ano específico)

    if (!empty($texto_adicionais)) {
        $linhas = explode("\n", str_replace("\r", "", $texto_adicionais));
        foreach ($linhas as $linha) {
            if (strpos($linha, '|') !== false) {
                list($data_texto, $nome) = explode('|', $linha, 2);
                $data_texto = trim($data_texto);
                $nome = trim($nome);
                
                // Verifica se é Específico (tem 2 barras) ou Regional (tem 1 barra)
                if (substr_count($data_texto, '/') == 2) {
                    $partes = explode('/', $data_texto); // DD/MM/YYYY
                    $data_formatada = $partes[2] . '-' . str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
                    $feriados_especificos[$data_formatada] = $nome;
                } elseif (substr_count($data_texto, '/') == 1) {
                    $partes = explode('/', $data_texto); // DD/MM
                    $mes_dia = str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($partes[0], 2, '0', STR_PAD_LEFT);
                    $feriados_regionais[$mes_dia] = $nome;
                }
            }
        }
    }

    $caminho_svg = '/' . \Drupal::service('extension.list.module')->getPath('mikedelta_pdia') . '/assets/svg/file-pdf.svg';

    $dados_frontend = [
      'arquivos' => $arquivos_por_data,
      'licencas' => $licencas,
      'nacionais' => $feriados_nacionais,
      'regionais' => $feriados_regionais,
      'especificos' => $feriados_especificos,
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