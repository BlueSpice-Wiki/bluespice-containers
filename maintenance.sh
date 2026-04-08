#!/bin/bash
cd "$(dirname "$0")"
source .env

show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "To build certain images that need special access, pass GITHUB_TOKEN and GITLAB_HW_TOKEN"
    echo "Options:"
    echo "  -a, --add-remote                Add remote repositories"
    echo "  -b, --build [OPTIONS]           Build Docker images"
    echo "    --dry-run                     Test build without saving images"
    echo "    --buildargs KEY=VALUE         Pass build arguments to docker build"
    echo "    --images IMAGE1,IMAGE2        Build only specific images (comma-separated)"
    echo "  -d, --dev-setup                 Setup deploy/compose/.env and override yml"
    echo "  -h, --help                      Show this help message"
    echo "  -i, --init-subtree              Show initializing commands of git subtree"
    echo "  -u, --update                    Update git subtree repositories"
}

ACTION="help"
BUILD_DRY_RUN=0
BUILD_ARGS=""
SELECTED_IMAGES=""

while [[ $# -gt 0 ]]; do
    case $1 in
        -a|--add-remote) ACTION="add"; shift ;;
        -b|--build)
            ACTION="build"
            shift
            while [[ $# -gt 0 && "$1" == --* ]]; do
                case $1 in
                    --dry-run) BUILD_DRY_RUN=1; shift ;;
                    --buildargs)
                        if [[ -z "$2" ]]; then
                            echo "Error: --buildargs requires a value"; show_usage; exit 1
                        fi
                        BUILD_ARGS="$BUILD_ARGS --build-arg $2"; shift 2 ;;
                    --images)
                        if [[ -z "$2" ]]; then
                            echo "Error: --images requires a value"; show_usage; exit 1
                        fi
                        SELECTED_IMAGES="$2"; shift 2 ;;
                    --*) echo "Error: Unknown build option: $1"; show_usage; exit 1 ;;
                esac
            done
            ;;
        -d|--dev-setup) ACTION="devsetup"; shift ;;
        -h|--help) show_usage; exit 0 ;;
        -i|--init-subtree) ACTION="init"; shift ;;
        -u|--update) ACTION="update"; shift ;;
        *) echo "Unknown option: $1"; show_usage; exit 1 ;;
    esac
done

iterate_components() {
    local callback_function="$1"
    while read -r key; do
        [[ -z "$key" ]] && continue
        var_name=$(echo "$key" | tr '-' '_')
        value=$(eval echo "\$$var_name")
        IFS='|' read -r prefix repo branch <<< "$value"

        "$callback_function" "$key" "$prefix" "$repo" "$branch"

    done <<< "$COMPONENTS"
}

add_git_remote_repos() {
    local key="$1"
    local prefix="$2" 
    local repo="$3"
    local branch="$4"
    git remote add "$key" "https://github.com/$repo"
    git remote set-url --push "$key" "git@github.com:$repo"
    # echo "git subtree add --prefix=$prefix $key $branch --squash"
}

init_git_subtree() {
    local key="$1"
    local prefix="$2"
    local repo="$3"
    local branch="$4"
    echo "git subtree add --prefix=$prefix https://github.com/$repo $branch --squash"
}

update_repos_git_subtree() {
    local key="$1"
    local prefix="$2"
    local repo="$3"
    local branch="$4"
    git subtree pull -P "$prefix" "$key" "$branch" || {
        echo "Failed to update $key. Please check the repository URL and branch."
        exit 1
    }
}

should_build_image() {
    local image_name="$1"
    if [[ -z "$SELECTED_IMAGES" ]]; then
        return 0  # Build all images
    fi
    IFS=',' read -ra images_array <<< "$SELECTED_IMAGES"
    for img in "${images_array[@]}"; do
        if [[ "$image_name" == "${img// /}" ]]; then
            return 0
        fi
    done
    return 1
}

build_docker_images() {
    local dry_run=$1
    local dry_run_text=""
    [[ $dry_run -eq 1 ]] && dry_run_text=" (dry-run mode)"

    echo "Building Docker images in images/ directory$dry_run_text..."
    local SECRET_ARGS=""
    [[ -n "$GITHUB_TOKEN" ]] && SECRET_ARGS+=" --secret id=GIT_AUTH_TOKEN.github.com,env=GITHUB_TOKEN"
    [[ -n "$GITLAB_HW_TOKEN" ]] && SECRET_ARGS+=" --secret id=GIT_AUTH_TOKEN.gitlab.hallowelt.com,env=GITLAB_HW_TOKEN"

    for dir in images/*/; do
        if [[ -d "$dir" && -f "$dir/Dockerfile" ]]; then
            local image_name=$(basename "$dir")

            if ! should_build_image "$image_name"; then
                echo "Skipping $image_name (not in selected images)"
                continue
            fi

            echo "Building $image_name..."
            local temp_file="/tmp/build_test_${image_name}"

            if [[ $dry_run -eq 1 ]]; then
                docker build --iidfile "$temp_file" $SECRET_ARGS $BUILD_ARGS "$dir" && \
                docker rmi "$(cat "$temp_file")" 2>/dev/null && \
                rm -f "$temp_file"
            else
                docker build -t "bluespice/$image_name:$IMAGES_VERSION_TAG" $SECRET_ARGS $BUILD_ARGS "$dir"
            fi
        fi
    done
    echo "Build completed!"
}

generate_dev_env() {
    local sample="$1"
    local dst="$2"
    local version_tag="$3"
    local project_name="$4"
    {
        echo "COMPOSE_PROJECT_NAME=$project_name"
        echo "CODEDIR=/path/to/your/bluespice/codebase"
        echo "# VERSION tag for images are overwritten below"
        echo ""
        cat "$sample"
        echo ""
        echo "VERSION=$version_tag"
        echo 'BLUESPICE_WIKI_IMAGE=bluespice/wiki:$VERSION'
        echo "SMTP_HOST=dev-mailhog"
        echo "SMTP_PORT=1025"
        echo "WIKI_LOG_LEVEL=debug"
    } > "$dst"
    echo "Created $dst"
    echo "Please continue editing $dst to finish your setup:"
    echo "Fill in your wanted CODEDIR, DATADIR and EDITION, DB_USER and DB_PASS"
}

backup_file_append() {
    local target="$1"
    local backup="${target}.backup"
    if [[ -f "$backup" ]]; then
        { echo ""; echo "# --- Backup from $(date) ---"; cat "$target"; } >> "$backup"
    else
        cp "$target" "$backup"
    fi
    echo "Old file backed up to $backup"
}

dev_setup() {
    local version_tag="$IMAGES_VERSION_TAG"
    local project_name
    project_name=$(echo "$version_tag" | tr -d '.')
    local override_src="dev/docker-compose.override.yml"
    local override_dst="deploy/compose/docker-compose.override.yml"
    local env_dst="deploy/compose/.env"
    local env_sample="deploy/compose/.env.sample"

    # --- Handle docker-compose.override.yml ---
    if [[ -f "$override_dst" ]]; then
        echo "File $override_dst already exists."
        read -r -p "Replace it? [y/N] " answer
        if [[ "$answer" =~ ^[Yy]$ ]]; then
            backup_file_append "$override_dst"
            cp "$override_src" "$override_dst"
            echo "Replaced $override_dst"
        else
            echo "Skipping $override_dst replacement."
        fi
    else
        cp "$override_src" "$override_dst"
        echo "Copied $override_src to $override_dst"
    fi

    # --- Handle .env ---
    if [[ -f "$env_dst" ]]; then
        echo "File $env_dst already exists."
        read -r -p "Replace it? [y/N] " answer
        if [[ "$answer" =~ ^[Yy]$ ]]; then
            backup_file_append "$env_dst"
            generate_dev_env "$env_sample" "$env_dst" "$version_tag" "$project_name"
        else
            echo "Skipping $env_dst replacement."
        fi
    else
        generate_dev_env "$env_sample" "$env_dst" "$version_tag" "$project_name"
    fi
}

case $ACTION in
    add) iterate_components add_git_remote_repos; git remote -v ;;
    build) build_docker_images $BUILD_DRY_RUN ;;
    devsetup) dev_setup ;;
    help) echo "Too few options provided"; show_usage; exit 1 ;;
    init)
        echo "To initialize git subtree, run the following commands.";
        echo "Your working tree must be clean, otherwise git subtree will fail.";
        echo "If you would like to keep all change histories, remove --squash flags.";
        echo "";
        iterate_components init_git_subtree ;;
    update) iterate_components update_repos_git_subtree ;;
esac
