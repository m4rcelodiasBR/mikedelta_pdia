<?php

namespace Drupal\mikedelta_pdia\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PdiaApiController extends ControllerBase {

  public function salvarLicenca(Request $request) {
    // Decodifica o payload JSON vindo do JS
    $dados = json_decode($request->getContent(), TRUE);
    $data = $dados['date'] ?? NULL;
    $nome = $dados['name'] ?? NULL;

    if (!$data || !$nome) {
      return new JsonResponse(['status' => 'error', 'message' => 'Data ou nome ausentes.'], 400);
    }

    $config = \Drupal::service('config.factory')->getEditable('mikedelta_pdia.settings');
    $licencas = $config->get('licencas') ?? [];
    $licencas[$data] = $nome;
    $config->set('licencas', $licencas)->save();

    return new JsonResponse(['status' => 'success', 'message' => 'Licença salva com sucesso.']);
  }

  public function apagarLicenca(Request $request) {
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
  }
}