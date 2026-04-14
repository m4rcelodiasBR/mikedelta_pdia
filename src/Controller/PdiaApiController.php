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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

class PdiaApiController extends ControllerBase {

  public function salvarLicenca(Request $request) {
    try {
      $dados = json_decode($request->getContent(), TRUE);
      $data = $dados['date'] ?? NULL;
      $nome = isset($dados['name']) ? Xss::filter($dados['name'], []) : NULL;

      if (!$data || !$nome) {
        return new JsonResponse(['status' => 'error', 'message' => 'Data ou nome ausentes ou inválidos.'], 400);
      }

      $config = \Drupal::service('config.factory')->getEditable('mikedelta_pdia.settings');
      $licencas = $config->get('licencas') ?? [];
      $licencas[$data] = $nome;
      $config->set('licencas', $licencas)->save();

      return new JsonResponse(['status' => 'success', 'message' => 'Licença salva com sucesso.']);
      
    } catch (\Exception $e) {
      \Drupal::logger('mikedelta_pdia')->error('Erro ao salvar licença: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['status' => 'error', 'message' => 'Erro interno no servidor ao processar a requisição.'], 500);
    }
  }

  public function apagarLicenca(Request $request) {
    try {
      $dados = json_decode($request->getContent(), TRUE);
      $data = $dados['date'] ?? NULL;

      if (!$data) {
        return new JsonResponse(['status' => 'error', 'message' => 'Data ausente.'], 400);
      }

      $config = \Drupal::service('config.factory')->getEditable('mikedelta_pdia.settings');
      $licencas = $config->get('licencas') ?? [];

      if (isset($licencas[$data])) {
        unset($licencas[$data]);
        $config->set('licencas', $licencas)->save();
      }

      return new JsonResponse(['status' => 'success', 'message' => 'Licença removida com sucesso.']);
      
    } catch (\Exception $e) {
      \Drupal::logger('mikedelta_pdia')->error('Erro ao apagar licença: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['status' => 'error', 'message' => 'Erro interno no servidor ao processar a requisição.'], 500);
    }
  }
}