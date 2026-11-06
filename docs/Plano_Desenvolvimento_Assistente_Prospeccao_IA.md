# Plano de Desenvolvimento --- Assistente de Prospeção Imobiliária com IA

**Projeto:** CRM imobiliário baseado em Krayin\
**Módulo:** `packages/Webkul/Prospeccao`\
**Data:** 05-11-2026

## 1. Objetivo

Evoluir o módulo Prospeção existente para um assistente de prospeção
imobiliária com IA. O consultor controla todas as comunicações: a IA
analisa, prepara e sugere; o consultor revê, decide e envia manualmente.

### Fluxo pretendido

1.  O consultor encontra manualmente um anúncio particular.
2.  Introduz manualmente o anúncio, imóvel e dados do proprietário no
    CRM.
3.  O sistema organiza os dados e procura possíveis duplicados.
4.  A IA analisa o contexto e prepara uma primeira mensagem.
5.  O consultor revê, altera e decide se envia.
6.  O consultor envia manualmente pelo WhatsApp.
7.  Copia a resposta recebida para o CRM.
8.  A IA analisa a resposta e o histórico e propõe uma resposta
    seguinte.
9.  O consultor revê e envia manualmente.
10. O processo termina com reunião, oportunidade, recusa ou
    encerramento.

### Fora do âmbito da primeira versão

-   Pesquisa automática de anúncios.
-   Recolha automática ou em massa de números.
-   Envio automático de mensagens.
-   Leitura automática do WhatsApp.
-   API WhatsApp Business Cloud.
-   Respostas autónomas da IA.
-   Chamadas telefónicas automáticas.
-   Criação de um segundo CRM.

O sistema pode preparar texto e abrir `wa.me`, mas abrir o WhatsApp não
significa que a mensagem tenha sido enviada. O consultor confirma
manualmente o envio.

**Limite operacional:** máximo de cinco novos primeiros contactos por
dia, de segunda a sexta-feira. É uma regra de organização, não um
mecanismo de envio automático nem uma garantia de conformidade legal.

## 2. Princípios

1.  Preservar a arquitetura e convenções do Krayin.
2.  Evoluir o pacote Prospeção existente.
3.  Reutilizar Pessoas e Leads do Krayin.
4.  Evitar duplicação de Pessoas, Leads e imóveis.
5.  Exigir aprovação humana para todas as comunicações.
6.  Manter histórico auditável.
7.  Minimizar dados pessoais enviados ao fornecedor de IA.
8.  Implementar uma etapa de cada vez.
9.  Não assumir versões ou comportamentos sem confirmar no código.
10. Não misturar este projeto com a SO Platform ou outros projetos.

## 3. Stack tecnológica

### Confirmado pela análise inicial

  Camada          Tecnologia
  --------------- -----------------------------------
  CRM             Krayin CRM personalizado
  Backend         Laravel
  Linguagem       PHP
  Base de dados   MySQL
  Pacote          `packages/Webkul/Prospeccao`
  Entidades CRM   Pessoas e Leads do Krayin
  Interface       Administração existente do Krayin
  Versionamento   Git

### Confirmar na Etapa 0

Consultar `composer.json`, `composer.lock`, configurações e código para
confirmar versões exatas de PHP, Laravel, Krayin, MySQL, bibliotecas
HTTP, testes, filas, cache, autenticação, permissões, frontend e
timezone. Não atualizar versões sem necessidade demonstrada.

### Integração IA

Criar serviço backend desacoplado do fornecedor. As credenciais ficam em
variáveis de ambiente, nunca no código ou Git. O serviço envia apenas
contexto necessário, valida respostas, trata timeouts e erros e nunca
envia mensagens ao WhatsApp. Escolher fornecedor/modelo antes da
implementação, considerando qualidade em português europeu, custo,
privacidade, retenção e condições de tratamento.

## 4. Estado atual conhecido

A análise inicial do ZIP identificou:

-   Pacote próprio `packages/Webkul/Prospeccao`.
-   Tabela `prospeccao_properties`.
-   Imóveis associados a Pessoa e Lead.
-   Reutilização da entidade `persons`.
-   `ProspeccaoLeadService` para procurar/reutilizar contactos.
-   Modelos de comunicação por fase/canal.
-   `prospeccao_contact_logs` para histórico.
-   `WhatsAppService` que prepara link `wa.me`, sem API oficial.
-   Interface administrativa de prospeção.

### Pontos a rever e reproduzir com testes

1.  Reutilizar uma Pessoa pode não impedir Leads/imóveis duplicados.
2.  Atualizar telefones/emails pode substituir valores existentes.
3.  O histórico pode confundir mensagem preparada com mensagem enviada.
4.  Validações de `property_type` e `locality` podem não coincidir com
    as migrations.
5.  A criação de Leads pode depender de IDs implícitos de
    pipeline/etapa.
6.  O exemplo de ambiente aparenta usar `QUEUE_CONNECTION=sync`.

Não assumir que estes problemas permanecem na versão atual sem os
reproduzir.

## 5. Estados funcionais propostos

### Oportunidade

-   `new`: registada, ainda não analisada.
-   `ready_for_review`: análise e mensagem preparadas.
-   `awaiting_first_contact`: aguarda decisão do consultor.
-   `first_contact_made`: consultor confirmou contacto inicial.
-   `conversation_active`: conversa em curso.
-   `meeting_proposed`: reunião proposta.
-   `meeting_scheduled`: reunião marcada.
-   `qualified`: oportunidade qualificada.
-   `not_interested`: sem interesse.
-   `do_not_contact`: oposição a novo contacto.
-   `closed`: encerrada.

Alinhar os nomes finais com as convenções existentes no pacote.

### Mensagem

-   `draft`: rascunho.
-   `suggested`: sugestão da IA.
-   `approved`: revista e aprovada.
-   `sent_manually`: consultor confirmou envio fora do CRM.
-   `received_manual`: resposta introduzida manualmente.
-   `discarded`: descartada.
-   `failed`: falha de processamento/geração.

`sent_manually` é uma declaração do consultor, não uma confirmação
técnica do WhatsApp.

### Consentimento, origem e oposição

Registar origem dos dados, data de recolha, canal/finalidade, estado de
autorização quando aplicável, evidência, método/data de obtenção e
eventual oposição/revogação. Estados possíveis: `unknown`, `consented`,
`refused`, `withdrawn`, `pending_legal_review`.

Não presumir que um número publicado num anúncio equivale a
consentimento para marketing. O modelo e os bloqueios de contacto devem
ser validados juridicamente. Não usar mensagens não solicitadas como
atalho para obter consentimento.

## 6. Etapas de implementação

### Etapa 0 --- Diagnóstico final

**Tarefas** 1. Confirmar branch e estado Git. 2. Confirmar backup da
base de dados de desenvolvimento. 3. Inspecionar `composer.json` e
`composer.lock`. 4. Confirmar versões PHP, Laravel, Krayin e
dependências. 5. Inspecionar providers e estrutura do pacote Prospeção.
6. Inspecionar migrations, Models, Controllers, Services, Requests,
rotas, views e permissões. 7. Identificar testes e comandos para os
executar. 8. Confirmar convenções de tradução e interface. 9. Confirmar
timezone e configuração de filas. 10. Registar baseline e resultados.

**Testes/verificações:** aplicação arranca; comandos Laravel funcionam;
testes existentes executam e baseline fica registada; working tree sem
alterações não relacionadas.

**Commit:**
`docs(prospeccao): documenta baseline e plano de implementação`

**Conclusão:** diagnóstico documentado, testes executados e commit
criado.

### Etapa 1 --- Estabilizar módulo existente

**Tarefas** 1. Reproduzir os problemas identificados. 2. Corrigir
atualização de telefones/emails sem perda de dados. 3. Definir deteção
de Pessoas duplicadas. 4. Definir deteção de anúncios/imóveis
duplicados. 5. Alinhar validações e constraints. 6. Resolver
pipeline/etapa explicitamente, sem IDs implícitos. 7. Distinguir
comunicação preparada de envio confirmado. 8. Rever permissões e
validação server-side. 9. Criar testes de regressão.

**Testes:** contacto novo; Pessoa existente; múltiplos telefones/emails;
anúncio repetido; vários imóveis da mesma Pessoa; campos obrigatórios;
pipeline correto; permissões.

**Commit:**
`fix(prospeccao): estabiliza contactos e registos de imóveis`

**Conclusão:** todos os testes relevantes passam e commit criado.

### Etapa 2 --- Conversações e mensagens

**Tarefas** 1. Definir relações entre prospeção, Pessoa, Lead e
conversação. 2. Criar migrations/Models se não existir estrutura
reutilizável. 3. Guardar mensagens individuais. 4. Guardar direção,
canal, conteúdo, autor, data e estado. 5. Preservar histórico
cronológico. 6. Identificar mensagens escritas pelo consultor ou
sugeridas pela IA. 7. Evitar dados redundantes. 8. Definir retenção após
análise jurídica. 9. Respeitar permissões do CRM.

**Testes:** criar conversa; guardar mensagem de saída e resposta;
ordenar cronologicamente; validar relações; permissões; migrations
up/down.

**Commit:** `feat(prospeccao): adiciona histórico de conversações`

### Etapa 3 --- Interface manual

**Tarefas** 1. Adicionar painel/separador de conversa na prospeção. 2.
Mostrar histórico cronológico. 3. Permitir introduzir respostas
recebidas. 4. Permitir escrever e editar mensagens de saída. 5. Ação
para copiar texto. 6. Ação para abrir `wa.me` com número/texto
codificados. 7. Exigir confirmação humana para marcar como enviado. 8.
Distinguir rascunho, sugestão, envio declarado e resposta. 9. Permitir
descartar sugestões. 10. Não implementar API nem leitura automática.

**Testes:** introdução e visualização; editar/copiar/descartar; link
correto; abrir link não marca enviado; confirmação explícita altera
estado; números inválidos; permissões.

**Commit:** `feat(prospeccao): adiciona interface de conversação manual`

### Etapa 4 --- IA para primeira abordagem

**Tarefas** 1. Escolher fornecedor/modelo. 2. Configurar credenciais em
ambiente. 3. Criar contrato/serviço desacoplado do fornecedor. 4.
Analisar anúncio e contexto. 5. Definir prompt e formato estruturado. 6.
Gerar mensagem curta, natural e em português europeu. 7. Não inventar
características nem dados. 8. Sinalizar informação em falta e
pressupostos. 9. Permitir regenerar, editar, aprovar e descartar. 10.
Guardar metadados mínimos de modelo/prompt. 11. Minimizar dados
enviados.

**Testes:** anúncio completo/incompleto; não inventar factos; timeout;
resposta malformada; formato validado; nenhuma ação de envio; aprovação
exclusivamente humana; testes com mocks e integração controlada.

**Commit:**
`feat(prospeccao): adiciona assistente IA para primeira abordagem`

### Etapa 5 --- IA para análise de respostas

**Tarefas** 1. Enviar anúncio, dados necessários e histórico relevante.
2. Analisar intenção. 3. Identificar objeções, dúvidas e interesse. 4.
Classificar resposta. 5. Gerar proposta contextualizada. 6. Recomendar
próximo passo. 7. Sinalizar incerteza. 8. Evitar pressão, manipulação e
promessas infundadas. 9. Permitir editar/aprovar/descartar. 10. Guardar
sugestão e decisão.

**Categorias iniciais:** interessado; pede informação; indeciso; já tem
consultor; sem interesse; não contactar novamente; imóvel
vendido/reservado; ambíguo; outro.

**Testes:** respostas positivas/negativas; objeção de comissão; já
representado; pedido de informação; ambígua; oposição; contexto
contraditório; nenhuma resposta automática; classificação e sugestão
guardadas.

**Commit:** `feat(prospeccao): adiciona análise IA das respostas`

### Etapa 6 --- Qualificação e funil

**Tarefas** 1. Definir critérios transparentes. 2. Separar sugestão IA
de classificação confirmada. 3. Mostrar resumo da oportunidade. 4.
Sugerir tarefas/próximos passos. 5. Integrar com pipeline/Lead do
Krayin. 6. Registar reunião e resultado. 7. Guardar motivo de
perda/recusa. 8. Fazer prevalecer oposição a qualquer sugestão da IA.

**Testes:** classificação editável; Lead coerente; tarefa/reunião;
encerramento com motivo; bloqueio após oposição; ausência de duplicados.

**Commit:**
`feat(prospeccao): integra qualificação e acompanhamento no CRM`

### Etapa 7 --- Quota diária

**Objetivo:** máximo de cinco novos primeiros contactos confirmados por
dia útil, sem envio automático.

**Tarefas** 1. Confirmar timezone `Europe/Lisbon` com padrão da
aplicação. 2. Contar primeiros contactos confirmados pelo consultor. 3.
Limitar a cinco de segunda a sexta. 4. Não contar rascunhos, sugestões
descartadas ou mensagens não confirmadas. 5. Não contar seguimentos. 6.
Mostrar quota utilizada/disponível. 7. Permitir registar oportunidades
sem contactar. 8. Nunca enviar automaticamente no dia seguinte. 9.
Exceções administrativas apenas se decididas e auditadas.

**Testes:** cinco contactos; sexto bloqueado como novo primeiro
contacto; reinício no dia útil seguinte; fim de semana; timezone/mudança
de hora; seguimentos excluídos; rascunhos excluídos. Decidir se feriados
também são excluídos.

**Commit:**
`feat(prospeccao): aplica limite operacional diário de contactos`

### Etapa 8 --- Segurança e privacidade

**Tarefas** 1. Rever permissões. 2. Validar inputs no servidor. 3.
Proteger credenciais. 4. Excluir segredos e dados desnecessários dos
logs. 5. Definir conservação/eliminação. 6. Registar origem e estado de
autorização. 7. Implementar oposição e bloqueio. 8. Disponibilizar
informação de privacidade. 9. Rever termos e tratamento de dados do
fornecedor IA. 10. Validar juridicamente o processo antes da utilização
real.

**Testes:** acesso negado sem permissão; inputs inválidos; segredos
ausentes dos logs; oposição bloqueia contacto; eliminação conforme
política; regressão e segurança básica.

**Commit:**
`security(prospeccao): reforça privacidade e controlo de acesso`

### Etapa 9 --- Integração e produção

**Tarefas** 1. Executar suite completa. 2. Testar manualmente com dados
fictícios. 3. Validar ciclo completo. 4. Testar falhas IA. 5. Rever
custos. 6. Confirmar backups/recuperação. 7. Preparar produção sem
segredos no Git. 8. Documentar instalação/configuração/operação. 9.
Fazer deploy controlado. 10. Monitorizar utilização inicial.

**Testes:** ponta a ponta; regressão CRM; permissões; falhas IA;
duplicação; quota; logs sem segredos; backup/recuperação.

**Commit:**
`release(prospeccao): prepara primeira versão do assistente IA`

## 7. Regra obrigatória de conclusão

Uma etapa só fica concluída quando: 1. Código implementado e revisto. 2.
Migrations verificadas, se aplicável. 3. Testes novos criados. 4. Testes
existentes relevantes executados. 5. Todos os testes passam. 6.
Verificação manual realizada quando aplicável. 7. Diff revisto e sem
alterações alheias. 8. Sem segredos ou dados pessoais indevidos no Git.
9. Documentação atualizada. 10. Commit criado. 11. Hash do commit
registado.

Se algum teste falhar, a etapa permanece em curso. Não avançar até
corrigir ou documentar formalmente um bloqueio. Não declarar concluído
apenas porque o código foi escrito ou a página parece funcionar.

### Registo de progresso

  Etapa                           Estado        Testes   Commit   Hash
  ------------------------------- ------------- -------- -------- ------
  0 --- Diagnóstico               Por iniciar   ---      ---      ---
  1 --- Estabilização             Por iniciar   ---      ---      ---
  2 --- Conversações              Por iniciar   ---      ---      ---
  3 --- Interface manual          Por iniciar   ---      ---      ---
  4 --- Primeira abordagem IA     Por iniciar   ---      ---      ---
  5 --- Análise de respostas IA   Por iniciar   ---      ---      ---
  6 --- Qualificação              Por iniciar   ---      ---      ---
  7 --- Quota diária              Por iniciar   ---      ---      ---
  8 --- Segurança/privacidade     Por iniciar   ---      ---      ---
  9 --- Produção                  Por iniciar   ---      ---      ---

## 8. Convenções Git

-   Usar branch própria, respeitando convenções existentes.
-   Excluir `.env`, logs, backups, `vendor`, `node_modules` e
    temporários.
-   Não misturar refatorações fora do âmbito.
-   Um commit por etapa concluída; commits intermédios são permitidos.
-   Não reescrever histórico partilhado sem autorização.
-   Antes do commit, verificar `git status`, diff e staging.
-   Depois do commit, confirmar working tree limpo.

## 9. Regras para a IA

-   Nunca envia mensagens nem telefona.
-   Nunca altera dados críticos sem confirmação.
-   Não inventa dados do imóvel ou proprietário.
-   Não promete resultados ou condições não confirmadas.
-   Não pressiona nem manipula.
-   Respeita pedidos de não voltar a contactar.
-   Distingue factos, inferências e desconhecido.
-   Usa português europeu, linguagem natural, profissional e não
    agressiva.
-   Sugere respostas concisas e adequadas ao contexto.
-   Quando houver ambiguidade, declara incerteza.
-   A decisão final pertence sempre ao consultor.

## 10. Decisões pendentes

Resolver antes da etapa correspondente: 1. Fornecedor/modelo IA e
custos. 2. Dados enviados ao fornecedor e retenção. 3. Critérios de
duplicação de anúncios/imóveis. 4. Exclusão ou não de feriados na quota.
5. Timezone efetiva. 6. Validação jurídica do método de contacto e
tratamento de dados. 7. Alojamento, backups e serviços necessários. 8.
Limites de consumo IA.

## 11. Método de trabalho nesta conversa

Para cada etapa: 1. Explicar objetivo e ficheiros a analisar/alterar. 2.
Confirmar abordagem antes de mudanças estruturais. 3. Implementar apenas
a etapa atual. 4. Fornecer comandos concretos para o ambiente local. 5.
Analisar resultados e falhas. 6. Rever diff e preparar commit. 7. Só
avançar após testes aprovados e commit existente.

Não presumir que o código está correto sem testes executados.

## 12. Ponto de situação

**Etapa 0 — Diagnóstico final do repositório:**
Validação técnica concluída em 06/11/2026.

Baseline: 213 testes funcionais aprovados, sem falhas.

Pendente: revisão final da documentação e criação do commit da Etapa 0.

**Próxima etapa: Etapa 1 — Estabilizar o módulo existente.**

Não iniciar a Etapa 1 antes de existir commit da Etapa 0.

## 13. Baseline técnica do repositório

**Data da validação:** 06/11/2026

### 13.1. Ambiente de desenvolvimento

| Componente | Versão / Configuração |
|---|---|
| Sistema de desenvolvimento | Windows / Laragon |
| PHP CLI | 8.4.26 |
| Composer | 2.10.3 |
| Laravel | 12.66.0 |
| Pest | 3.8.6 |
| PHPUnit | 11.5.50 |
| Base de dados | MySQL |
| Nome da base de desenvolvimento | fsbo_crm |
| Extensão PHP disponível | pdo_mysql |
| SQLite | Não instalado/ativo |

### 13.2. Repositório

- Branch atual: `2.2`
- Branch sincronizada com: `origin/2.2`
- Último commit identificado: `7e267852`
- Módulo de Prospeção: `packages/Webkul/Prospeccao`
- Estrutura modular baseada em pacotes Krayin.
- Laravel migrations distribuídas pelos pacotes e pela aplicação.

### 13.3. Estado do módulo Prospeção

Estrutura identificada:

- Controllers: `ProspeccaoController` e `CommunicationTemplateController`.
- Models: `Owner`, `Property`, `CommunicationTemplate` e `ContactLog`.
- Services: `ContactLogService`, `EmailService`, `ProspeccaoLeadService`, `TemplateParserService` e `WhatsAppService`.
- Migrations próprias no pacote.
- Vistas para gestão de imóveis, comunicação e modelos de mensagens.

Já existe um mecanismo de registo de contactos e histórico, mas ainda não existe uma estrutura explícita de conversações e mensagens individuais.

### 13.4. Baseline dos testes

Foi executada a suite de testes existente, sem alterações ao código durante esta validação.

| Execução | Resultado |
|---|---:|
| Testes unitários | 1 aprovado |
| Testes funcionais completos | 213 aprovados |
| Asserções na suite Feature | 435 |
| Duração da suite Feature | 58,18 segundos |
| Falhas | 0 |

Foram também executados separadamente os testes de Prospeção e Contactos, todos aprovados.

Os testes específicos da Prospeção confirmaram:
- Não sobrescrever dados de uma Pessoa existente quando existe correspondência por telefone.
- Não sobrescrever dados de uma Pessoa existente quando existe correspondência por email.
- Criar uma nova Pessoa quando não existe correspondência.

Os testes de Contactos confirmaram:
- Proteção contra eliminação de Pessoas associadas a imóveis.
- Proteção contra eliminação em massa.
- Arquivo e recuperação de Pessoas.

**Nota sobre o ambiente de testes:** a configuração PHPUnit não força uma base de dados isolada. Os testes foram executados no ambiente local de desenvolvimento, após criação de uma cópia de segurança da base de dados. Esta configuração deverá ser revista numa etapa posterior, para garantir isolamento automático dos testes.

### 13.5. Backup

Foi criada uma cópia de segurança da base de desenvolvimento antes da execução da suite completa.

Ficheiro: `backup_fsbo_crm_baseline_2026-11-06.sql`

Localização: fora do repositório Git.

### 13.6. Resultado do diagnóstico

A baseline técnica foi validada:

- Ambiente de desenvolvimento identificado.
- Dependências e versões confirmadas.
- Estrutura do módulo Prospeção identificada.
- Suite de testes executada com sucesso.
- 213 testes funcionais aprovados.
- Nenhuma falha identificada na baseline.

### 13.7. Estado da Etapa 0

**Estado: validação técnica concluída.**

Falta:

1. Rever o diff.
2. Confirmar que apenas os ficheiros pretendidos serão incluídos.
3. Criar o commit da Etapa 0.
4. Registar o hash do commit.

A Etapa 1 só pode começar depois da conclusão destes pontos.
