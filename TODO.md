### TODO

Plano detalhado e organizado para o desenvolvimento e implementação das funcionalidades faltantes no pacote Groq PHP:

#### Implementar o endpoint de modelos

• Criar a classe Models
• Implementar o método list()
• Fazer a requisição GET para /openai/v1/models
• Processar a resposta e retornar a lista de modelos
• Adicionar testes unitários para a classe Models

#### Implementar o endpoint de transcrições de áudio

• Criar a classe AudioTranscriptions
• Implementar o método create()
• Fazer a requisição POST para /openai/v1/audio/transcriptions
• Lidar com o envio de arquivos de áudio no corpo da requisição
• Processar a resposta e retornar a transcrição
• Adicionar testes unitários para a classe AudioTranscriptions

#### Implementar o endpoint de traduções de áudio

• Criar a classe AudioTranslations
• Implementar o método create()
• Fazer a requisição POST para /openai/v1/audio/translations
• Lidar com o envio de arquivos de áudio no corpo da requisição
• Processar a resposta e retornar a tradução
• Adicionar testes unitários para a classe AudioTranslations

#### Atualizar a documentação

• Atualizar o arquivo README.md
• Adicionar exemplos de uso para os novos endpoints
• Descrever os novos endpoints e suas opções
• Implementar testes unitários

#### Criar novos casos de teste no arquivo tests/GroqTest.php

• Cobrir as novas funcionalidades implementadas
• Implementar mocks de respostas e simulação de erros
• Refatorar e aplicar boas práticas

#### Revisar o código existente

• Aplicar princípios de boas práticas (SOLID, Clean Code)
• Separar responsabilidades em classes e métodos menores
• Implementar padrões de projeto (Injeção de Dependência, Adapter)

#### Realizar análise de performance

• Identificar e otimizar possíveis gargalos
• Implementar técnicas de cache e otimização de requisições

#### Publicar no Packagist

• Atualizar a versão do pacote no arquivo composer.json
• Automatizar o processo de build e publicação

#### Solicitar feedback da comunidade

• Compartilhar o pacote atualizado em fóruns e comunidades
• Coletar feedback e sugestões para melhorias futuras
