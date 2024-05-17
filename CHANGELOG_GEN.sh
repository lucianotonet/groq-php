#!/bin/bash

# Configurações
FORMATO_DATA="%d/%m/%Y"
ARQUIVO_CHANGELOG="CHANGELOG.md"
ARQUIVO_TEMP="temp_changelog.md"
ARQUIVO_JSON="temp_changelog.json"

# Verifica se o Git está instalado
if ! command -v git &> /dev/null; then
    echo "Erro: O Git não está instalado."
    exit 1
fi

# Verifica se há permissão de escrita
if [ ! -w "$ARQUIVO_CHANGELOG" ]; then
    echo "Erro: Permissão negada para escrever em $ARQUIVO_CHANGELOG."
    exit 1
fi

# Inicializa o arquivo temporário de Changelog
echo "# Changelog" > "$ARQUIVO_TEMP"

# Obtém a lista de tags ordenadas por data
tags=($(git tag --sort=-creatordate))

# Loop através das tags do Git
for (( i=0; i<${#tags[@]}; i++ )); do
    current_tag="${tags[i]}"
    previous_tag="${tags[i+1]}"

    # Se não houver próxima tag, significa que estamos na versão mais recente
    if [ -z "$previous_tag" ]; then
        echo -e "\n\n## Versão $current_tag" >> "$ARQUIVO_TEMP"
        git log --pretty=format:'* %ad - %s' --date=format:"%d/%m/%Y" "$current_tag" >> "$ARQUIVO_TEMP"
    else
        echo -e "\n\n## Versão $current_tag" >> "$ARQUIVO_TEMP"
        git log --pretty=format:'* %ad - %s' --date=format:"%d/%m/%Y" "$previous_tag..$current_tag" >> "$ARQUIVO_TEMP"
    fi
done

# Substitui o arquivo de Changelog pelo temporário
mv "$ARQUIVO_TEMP" "$ARQUIVO_CHANGELOG"

echo "Changelog atualizado com sucesso!"
