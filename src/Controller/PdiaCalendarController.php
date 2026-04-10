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

    // --- LÓGICA DE FERIADOS ---
    $feriados_nacionais = [];
    
    // 1. Gera dinamicamente os feriados fixos e móveis para todos os anos (2013 a 2100)
    for ($y = 2013; $y <= 2100; $y++) {
        // --- Feriados Fixos ---
        $feriados_nacionais["$y-01-01"] = 'Confraternização mundial';
        $feriados_nacionais["$y-04-21"] = 'Tiradentes';
        $feriados_nacionais["$y-05-01"] = 'Dia do trabalho';
        $feriados_nacionais["$y-09-07"] = 'Independência do Brasil';
        $feriados_nacionais["$y-10-12"] = 'Nossa Senhora Aparecida';
        $feriados_nacionais["$y-11-02"] = 'Finados';
        $feriados_nacionais["$y-11-15"] = 'Proclamação da República';
        $feriados_nacionais["$y-12-25"] = 'Natal';
        
        // O Dia da Consciência Negra passou a ser feriado nacional a partir de 2024
        if ($y >= 2024) {
            $feriados_nacionais["$y-11-20"] = 'Dia da consciência negra';
        }

        // --- Feriados Móveis (Baseados na Páscoa) ---
        // easter_days() retorna quantos dias depois de 21 de Março cai a Páscoa naquele ano
        $dias_pascoa = easter_days($y);
        $data_pascoa = new \DateTime("$y-03-21");
        $data_pascoa->modify("+$dias_pascoa days");
        
        $feriados_nacionais[$data_pascoa->format('Y-m-d')] = 'Páscoa';
        
        // Carnaval: 47 dias antes da Páscoa
        $carnaval = clone $data_pascoa;
        $feriados_nacionais[$carnaval->modify('-47 days')->format('Y-m-d')] = 'Carnaval';
        
        // Sexta-feira Santa: 2 dias antes da Páscoa
        $sexta_santa = clone $data_pascoa;
        $feriados_nacionais[$sexta_santa->modify('-2 days')->format('Y-m-d')] = 'Sexta-feira Santa';
        
        // Corpus Christi: 60 dias após a Páscoa
        $corpus_christi = clone $data_pascoa;
        $feriados_nacionais[$corpus_christi->modify('+60 days')->format('Y-m-d')] = 'Corpus Christi';
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

    $is_logged_in = \Drupal::currentUser()->isAuthenticated();

    $dados_frontend = [
      'arquivos' => $arquivos_por_data,
      'licencas' => $licencas,
      'nacionais' => $feriados_nacionais,
      'regionais' => $feriados_regionais,
      'especificos' => $feriados_especificos,
      'ano_base' => $ano_atual,
      'icone_pdf' => $caminho_svg,
      'is_logged_in' => $is_logged_in,
      'url_config' => \Drupal\Core\Url::fromRoute('mikedelta_pdia.admin_calendar')->toString(),
    ];

    $build = [];

    $build['calendario'] = [
      '#theme' => 'mikedelta_pdia_calendar',
      '#dados_calendario' => $dados_frontend,
      '#attached' => [
        'library' => ['mikedelta_pdia/pdia_calendar_assets'],
        'drupalSettings' => ['mikedeltaPdia' => $dados_frontend],
      ],
      '#cache' => [
        'tags' => ['node_list:md_pdia', 'config:mikedelta_pdia.settings'],
        'contexts' => ['user.roles:authenticated'],
      ],
    ];

    return $build;
  }
}