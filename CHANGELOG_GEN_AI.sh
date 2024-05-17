#!/bin/bash

# Carrega as configurações
source .env

# Configurações hardcoded
MODELS=("llama3-8b-8192" "llama3-70b-8192" "mixtral-8x7b-32768" "gemma-7b-it")
PROJECT_README="README.md"
CHANGELOG="CHANGELOG.md"
TEMP_CHANGELOG="temp_changelog.md"
DATE_FORMAT="%d/%m/%Y"
PROMPT_USER=false  # true para solicitar aprovação do usuário, false para aprovar automaticamente

# Funções

send_request_to_groq() {
    local data="$1"
    local response
    response=$(curl -X POST \
                    -H "Content-Type: application/json" \
                    -H "Authorization: Bearer $GROQ_API_KEY" \
                    -d "$data" \
                    "$GROQ_API_BASE_URL/chat/completions" \
                    -s -w "\n%{http_code}")

    local http_code=${response##*$'\n'}
    response=${response%$'\n'*}

    if [ "$http_code" == "429" ]; then
        echo "rate_limit_exceeded"
    else
        echo "$response"
    fi
}

get_changelog_suggestion() {
    local commit_hash="$1"
    local commit_message="$(git log --format=%B -n 1 "$commit_hash")"
    local commit_date="$(git log --format=%cd --date=format:"$DATE_FORMAT" -n 1 "$commit_hash")"
    local commit_diff="$(git show --stat "$commit_hash")"
    local project_context="$(cat "$PROJECT_README")"

    local model_index=0
    local data
    local response

    while true; do
        local model="${MODELS[$model_index]}"
        data=$(jq -n --arg project_context "$project_context" \
                     --arg commit_date "$commit_date" \
                     --arg commit_message "$commit_message" \
                     --arg commit_diff "$commit_diff" \
                     --arg model "$model" \
                     '{messages: [
                        {role: "system", content: ("Não use dados fictícios. Retorne apenas dados reais extraídos do contexto do projeto: " + $project_context)},
                        {role: "user", content: ("Gere o texto apropriado para a entrada deste commit no changelog. Siga o padrão. Retorne apenas a mensagem final completa e nada mais.\n\nMensagem do commit para análise:($commit_message)\n\nGit diff para análise:\n\n\($commit_diff)\n\nData: \($commit_date)")}
                      ], model: $model, temperature: 0.5, max_tokens: 1280, stream: false}')

        response=$(send_request_to_groq "$data")
        if [ "$response" == "rate_limit_exceeded" ]; then
            model_index=$((model_index + 1))
            if [ "$model_index" -ge "${#MODELS[@]}" ]; then
                echo "Erro: Limite de taxa excedido para todos os modelos disponíveis."
                return 1
            fi
        else
            local content=$(echo "$response" | jq -r '.choices[0].message.content')
            echo "$content"
            return 0
        fi
    done
}

prompt_user_for_changelog_entry() {
    local commit_hash="$3"  # Adicionado commit_hash como parâmetro
    local commit_message="$1"
    local changelog_suggestion="$2"
    local commit_date="$(git log --format=%cd --date=format:"$DATE_FORMAT" -n 1 "$commit_hash")"  # Movido para dentro da função para garantir acesso ao commit_date
    local original_entry=" - $commit_date - $commit_message"
    local choice

    while true; do
        echo -e "\n\e[33m------\e[0m" >&2
        echo -e "\n\e[32mMensagem do commit original:\n $commit_message\e[0m" >&2
        echo -e "\n\e[33m------\e[0m" >&2
        echo -e "\n\e[32mSugestão da IA para o changelog:\e[0m" >&2
        echo -e "\n\e[32m - $commit_date - $changelog_suggestion\e[0m" >&2
        echo -e "\n" >&2
        read -p $'\e[33mEscolha uma opção: (A)provar, (R)egerar, (M)anter original, ou (E)ditar? (padrão: Aprovar) \e[0m' choice
        choice=$(echo "$choice" | tr '[:upper:]' '[:lower:]')

        case "$choice" in
            a|"") echo "$commit_date - $changelog_suggestion"; return 0 ;;
            r) changelog_suggestion=$(get_changelog_suggestion "$commit_hash")
               prompt_user_for_changelog_entry "$commit_message" "$changelog_suggestion" "$commit_hash"  # Passa commit_hash como argumento
               ;;
            m) echo "$original_entry"; return 0 ;;
            e) read -p "Insira sua entrada personalizada no changelog: " custom_entry; echo "$custom_entry"; return 0 ;;
            *) echo "Opção inválida. Por favor, tente novamente." >&2 ;;
        esac
    done
}

update_changelog() {
    local tag_commits
    local commit_hash
    local changelog_entry
    local previous_tag=""
    local tags=($(git tag --sort=-creatordate))

    echo "# Changelog" > "$TEMP_CHANGELOG"

    # Agrupa os commits por tags/versão
    for (( i=0; i<${#tags[@]}; i++ )); do
        local current_tag="${tags[i]}"
        local next_tag="${tags[i+1]}"

        if [ -z "$next_tag" ]; then
            echo -e "\n\n## Versão $current_tag" >> "$TEMP_CHANGELOG"
            tag_commits=$(git log --pretty=format:'%h' "$current_tag")
        else
            echo -e "\n\n## Versão $current_tag" >> "$TEMP_CHANGELOG"
            tag_commits=$(git log --pretty=format:'%h' "$next_tag..$current_tag")
        fi

        # Percorre os commits e gera entradas no changelog
        for commit_hash in $tag_commits; do
            commit_message="$(git log --format=%B -n 1 "$commit_hash")"
            commit_date="$(git log --format=%cd --date=format:"$DATE_FORMAT" -n 1 "$commit_hash")"

            # Verifica se a mensagem do commit já existe no changelog
            if ! grep -Fxq "$commit_message" "$CHANGELOG"; then
                changelog_suggestion=$(get_changelog_suggestion "$commit_hash")
                if [ $? -eq 0 ]; then
                    if [ "$PROMPT_USER" == true ]; then
                        changelog_entry=$(prompt_user_for_changelog_entry "$commit_message" "$changelog_suggestion" "$commit_hash")  # Passa commit_hash como argumento
                    else
                        changelog_entry=" - $commit_date - $changelog_suggestion"
                    fi
                    echo "$changelog_entry" >> "$TEMP_CHANGELOG"
                fi
            fi
        done
    done

    # Substitui o changelog existente pelo arquivo temporário
    mv "$TEMP_CHANGELOG" "$CHANGELOG"
}

# Verifica se o Git está instalado
if ! command -v git &> /dev/null; then
    echo "Erro: Git não está instalado."
    exit 1
fi

# Verifica se há permissão de escrita
if [ ! -w "$CHANGELOG" ]; then
    echo "Erro: Permissão negada para escrever no $CHANGELOG."
    exit 1
fi

# Atualiza o changelog
update_changelog

echo "Changelog atualizado com sucesso!"