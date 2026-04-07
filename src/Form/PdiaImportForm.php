<?php

namespace Drupal\mikedelta_pdia\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldStorageConfig;

class PdiaImportForm extends FormBase {

  public function getFormId() {
    return 'mikedelta_pdia_import_form';
  }

  private function getScheme() {
    $field_storage = FieldStorageConfig::loadByName('node', 'field_arquivo');
    return $field_storage ? $field_storage->getSetting('uri_scheme') : 'public';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $scheme = $this->getScheme();
    $diretorio_exemplo = $scheme . '://importacao_pdia';

    $form['instrucoes'] = [
      '#type' => 'markup',
      '#markup' => '
        <div style="padding: 20px; border-left: 5px solid #003366; margin-bottom: 20px;">
          <h3>Como funciona a Importação em Massa?</h3>
          <p>Esta ferramenta automatiza o cadastro de arquivos PDF antigos (legado) no sistema, criando os boletins nas datas corretas. Pode ser utilizado também em casos de restauração de dados. Os passos a seguir devem ser minuciosamente respeitados.</p>
          <ol>
            <li>Acesse o servidor e crie uma pasta chamada <strong><code>importacao_pdia</code></strong> dentro do seu diretório de arquivos atual (<strong>' . strtoupper($scheme) . '</strong>). O caminho lido pelo sistema será: <strong><code>' . $diretorio_exemplo . '</code></strong>.</li>
            <li>Copie os PDFs para dentro dessa pasta (Sugerimos fazer isso ano a ano para não sobrecarregar o servidor). O nome do arquivo deve estar neste formato: <strong><code>PD-DDMMAAAA.pdf</code></strong></li>
            <li>Clique no botão abaixo. O sistema lerá cada PDF, criará o conteúdo no calendário, moverá o arquivo para a pasta definitiva (dentro de <code>' . $scheme . '://md_pdia/</code>) e o apagará da pasta temporária.</li>
          </ol>
          <p style="color: #d32f2f; font-weight: bold;">Atenção: Faça um backup da base de dados antes de processar milhares de arquivos de uma só vez.</p>
        </div>
      ',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Executar Importação'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_system = \Drupal::service('file_system');
    $file_repository = \Drupal::service('file.repository');
    
    $scheme = $this->getScheme();
    $diretorio_origem = $scheme . '://importacao_pdia';

    if (!is_dir($file_system->realpath($diretorio_origem))) {
      $this->messenger()->addError("A pasta $diretorio_origem não existe. Crie a pasta no servidor e coloque os PDFs lá antes de iniciar.");
      return;
    }

    $arquivos = $file_system->scanDirectory($diretorio_origem, '/\.pdf$/i');
    $importados = 0;
    $erros = 0;

    foreach ($arquivos as $arquivo) {
      $nome_ficheiro = $arquivo->filename;

      if (preg_match('/PD-(\d{2})(\d{2})(\d{4})\.pdf$/i', $nome_ficheiro, $matches)) {
        $dia = $matches[1];
        $mes = $matches[2];
        $ano = $matches[3];
        $data_formatada = "$ano-$mes-$dia";

        try {
          $dados_arquivo = file_get_contents($arquivo->uri);
          
          $diretorio_destino = "$scheme://md_pdia/$ano/$mes";
          $file_system->prepareDirectory($diretorio_destino, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

          $destino_final = "$diretorio_destino/$nome_ficheiro"; 
          $file_entity = $file_repository->writeData($dados_arquivo, $destino_final, FileSystemInterface::EXISTS_REPLACE);

          $node = Node::create([
            'type' => 'md_pdia',
            'title' => "Boletim - $dia/$mes/$ano",
            'field_data' => $data_formatada,
            'field_arquivo' => ['target_id' => $file_entity->id()],
            'status' => 1,
          ]);
          $node->save();

          unlink($arquivo->uri);
          $importados++;

        } catch (\Exception $e) {
          $this->messenger()->addError("Erro ao importar $nome_ficheiro: " . $e->getMessage());
          $erros++;
        }
      } else {
        $this->messenger()->addWarning("Ficheiro ignorado (Padrão de nome inválido): $nome_ficheiro");
        $erros++;
      }
    }

    \Drupal::service('cache.render')->invalidateAll();

    if ($importados > 0) {
      $this->messenger()->addStatus("$importados arquivos foram importados com sucesso para o sistema ($scheme)!");
    } elseif ($erros == 0) {
      $this->messenger()->addWarning("A pasta $diretorio_origem está vazia. Nenhum arquivo para importar.");
    }
  }
}