# MikeDelta PDia (Plano do Dia)

O **MikeDelta PDia** é um módulo nativo para Drupal 10/11 desenvolvido para centralizar, organizar e facilitar o acesso aos Planos do Dia (boletins internos em PDF) de Organizações Militares (OM). 

Ele substitui sistemas legados em PHP puro, integrando-se perfeitamente à arquitetura do Drupal (Nodes, Form API, Render API) e garantindo segurança, responsividade e gestão simplificada.

## 🚀 Funcionalidades

* **Calendário Interativo Público:** Grade visual mensal mostrando os dias com PDFs disponíveis, feriados e finais de semana.
* **Painel Administrativo Independente:** Gerenciamento centralizado do calendário sem interferir na visão do público.
* **Controle de Licenças da OM:** Modal nativo com requisições assíncronas (AJAX) para cadastrar licenças de pagamento e rotinas administrativas que refletem visualmente no calendário.
* **Motor Inteligente de Feriados:** * Cache estático com feriados nacionais.
* Aceita inserção de feriados regionais recorrentes (Ex: Aniversário da cidade todos os anos) ou específicos.
* **Ferramenta de Importação em Massa:** Automatiza a migração de milhares de PDFs antigos, recriando os conteúdos e distribuindo nos diretórios corretos de forma automática.
* **Segurança de Arquivos:** Suporte total a diretórios privados (`private://`), garantindo que apenas usuários com a permissão correta façam o download dos documentos, ideal para ambientes de Intranet.

## 📋 Pré-requisitos

* Drupal 10 ou 11
* PHP 8.1 ou superior
* (Opcional, mas recomendado) Diretório de arquivos configurado como `private://` no seu `settings.php`.

## ⚙️ Instalação

1. Faça o download ou clone este repositório para a pasta `modules/custom/mikedelta_pdia` do seu projeto Drupal.
2. Acesse o painel administrativo do Drupal em **Extensões** (`/admin/modules`).
3. Procure por "MikeDelta PDia" e clique em **Instalar**.

## 🛠️ Configuração e Uso

* Permissões: Vá em Pessoas > Permissões (/admin/people/permissions) e defina quais papéis de usuário (Roles) terão a permissão Administrar configurações do MikeDelta PDia e quem poderá Ver conteúdo publicado (para visualizar os PDFs).
* Definições Gerais: Acesse Conteúdo > Gerenciar Calendário PDia > Definições Gerais para inserir Feriados Regionais e Adicionais através de texto simples, sem necessidade de JSON.
* Adicionar Planos do Dia: Vá em Conteúdo > Adicionar conteúdo > Plano do Dia (MikeDelta PDia). Escolha a data e faça o upload do PDF. O sistema renomeará o arquivo automaticamente para o padrão PD-DDMMAAAA.pdf.

## 📦 Importação em Massa (Legado)
* Caso você possua PDFs de anos anteriores:
1. Crie uma pasta importacao_pdia no seu diretório de arquivos (public:// ou private://).
2. Adicione os PDFs antigos com o formato PD-DDMMAAAA.pdf.
3. Acesse Conteúdo > Gerenciar Calendário PDia > Importar/Atualizar e clique em "Executar Importação".

**Aviso: É altamente recomendável realizar um backup do banco de dados antes de executar uma importação em massa.**

## 📄 Licença
* Este projeto é um software livre; você pode redistribuí-lo e/ou modificá-lo sob os termos da Licença Pública Geral GNU (GPLv3) conforme publicada pela Free Software Foundation.
* Este programa é distribuído na esperança de que seja útil, mas SEM NENHUMA GARANTIA; sem mesmo a garantia implícita de COMERCIALIZAÇÃO ou ADEQUAÇÃO A UM DETERMINADO FIM.
* Veja o arquivo LICENSE.txt para mais detalhes.

## Downloads