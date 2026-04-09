# MikeDelta PDia (Plano do Dia)

O **MikeDelta PDia** é um módulo de alto desempenho criado para o Drupal 10/11, projetado para gerenciar, visualizar e arquivar o Plano do Dia em PDF de Organizações Militares.

Nascido para facilitar a operação do setor responsável, este módulo elimina arquivos estáticos desnecessários, blinda as regras de negócio de Feriados Nacionais via matemática pura e fornece ferramentas robustas de Backup usando processamento em lote.

## 🚀 Funcionalidades Principais

- **Tipo de conteúdo configurado:** Após instalação do módulo é criado um Tipo de Conteúdo exclusivo para o envio dos arquivos PDFs. Tudo controlado e organizado para não interferir em outras seções do seu site Drupal.
- **Blindagem de Conteúdo:** Garantia de que os PDFs gerados programaticamente nunca aparecerão na página inicial (`Promote: 0`).
- **Calendário Público Inteligente:** Grade mensal interativa com visualização imediata dos PDFs disponíveis.
- **Gestão Administrativa:** Painel independente (`/admin/content/md-pdia/gerenciar`) que não interfere nas configurações de cache da página pública.
- **Controle de Licenças com Regras de Negócio:** Modal AJAX nativo. O sistema possui trava inteligente que impede a marcação de licenças sobre Feriados Nacionais ou Regionais já estabelecidos. Esta funcionalidade é exclusiva para licenças de caráter interno da MB, use com responsabilidade.
- **Importação e Exportação (Batch API):**
  - **Importação prática:** Upload de arquivo `.zip` contendo centenas de PDFs. Extração, leitura e criação de conteúdo autônoma sem _Timeout_ do servidor.
  - **Exportação para Backup Seguro:** Exportação de anuários completos de PDFs em um único `.zip` processado em lotes dinâmicos para não estourar a memória RAM.

## 📋 Pré-requisitos de Servidor

- Drupal 10/11
- PHP 8.1 ou superior
- **Extensão PHP ZipArchive (`php-zip`) obrigatória** (A tela de importação bloqueará o uso caso não esteja instalada).

## ⚙️ Instalação e Configuração

1. Copie este repositório para o diretório `modules/` do seu ecossistema Drupal.
2. Acesse `Extensões` (`/admin/modules`) e instale o "MikeDelta PDia".
3. Acesse a tela de Relatório de Status (/admin/reports/status) e confirme se o componente PHP ZipArchive (MikeDelta PDia) está classificado como OK (instalado).
4. Conceda a permissão Administrar configurações do MikeDelta PDia para o papel de administrador desejado.
5. Na seção `Definições Gerais`, adicione os Feriados Regionais ou Pontos Facultativos exclusivos da sua OM em formato de texto simples.
6. Na seção `Ver Calendário`, utilize a opção `Gerenciar Licenças` para adicionar, editar ou remover licenças relacionadas e exclusivas da MB ou sua OM.

\*Nota:\_ Nesta tela ficará exibindo as licenças relacionadas ao mês em que você esta navegando.

## 📦 Importação/Exportação

Na seção `Importar/Exportar` você poderá fazer backups ou restauração dos seus arquivos de Plano do Dia de forma automática e sem necessidade de configurações ou edições especiais no seu site.

- **Para Importar (ou Restore):**

1. Agrupe os documentos PDF em um único arquivo com a extensão `.zip`.
2. Requisito Restrito: Todos os PDFs dentro do .zip devem obedecer a nomenclatura `PD-DDMMAAAA.pdf`.
3. Acesse a aba Importar Dados no menu gerencial e faça o upload. O sistema fará o cadastro automático no banco de dados, validando os conteúdos do PDFs e realocando os arquivos dentro do seu respectivo diretório no Drupal (private:// ou public://).

- **Para Exportar (ou Backup):**

\*Nota:\_ O sistema vai listar os anos em que existem Planos do Dia publicados. O agrupamento por ano é menos oneroso para o servidor e não afetar a performance durante o processo.

1. Selecione na lista o ano que deseja fazer a exportação.
2. Clique em `Gerar Arquivo ZIP`.
3. Aguarde o processo. Ao final será habilitado o botão para download do arquivo. Guarde este arquivo em caso de necessidade de uma possível restauração dos arquivos deste módulo.

## Feedback e evolução

Utilize com responsabilidade este módulo e quaisquer bugs encontrados, melhorias, erros reporte para `dias.marcelo@marinha.mil.br`. Seu feedback é importante para a constante melhoria deste projeto.

## 📄 Licença de Uso

Este projeto é um software livre licenciado sob a Licença Pública Geral GNU (GPLv3) ou posterior. Você pode redistribuí-lo e/ou modificá-lo sob os termos publicados pela Free Software Foundation. Veja o arquivo LICENSE.txt para mais detalhes.

Desenvolvido no contexto de modernização sistêmica para a Marinha do Brasil. Desenvolvido por Marcelo Dias da Silva.
