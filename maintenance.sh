#!/bin/bash
cd "$(dirname "$0")"
source .env

show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "Options:"
    echo "  -a, --add-remote     Add remote repositories. Current remotes can be listed with 'git remote -v'"
    echo "  -b, --build          Build Docker images. Run after preparing build environment"
    echo "  -d, --dry-run        Test Docker builds (no images created)"
    echo "  -h, --help           Show this help message"
    echo "  -i, --init-subtree   Show initializing commands of git subtree. Decide whether to run them on your own"
    echo "  -p, --prepare        Prepare build environment"
    echo "  -u, --update         Update git subtree repositories. Run after initializing git subtree"
}

ACTION="help"
while [[ $# -gt 0 ]]; do
    case $1 in
        -a|--add-remote) ACTION="add"; shift ;;
        -b|--build) ACTION="build"; shift ;;
        -d|--dry-run) ACTION="dry-run"; shift ;;
        -h|--help) show_usage; exit 0 ;;
        -i|--init-subtree) ACTION="init"; shift ;;
        -p|--prepare) ACTION="prepare"; shift ;;
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

prepare_build() {
    local key="$1"
    local prefix="$2"
    local repo="$3"
    local branch="$4"
    if [[ "$key" == "bluespice-image-wiki" ]]; then
        echo "Preparing build environment in $prefix/_codebase..."
        # Load (empty) BlueSpice codebase
        mkdir -p "$prefix/_codebase/bluespice"
        # Load (latest) Simplesamlphp
        local sp_latest=$(curl -s https://api.github.com/repos/simplesamlphp/simplesamlphp/releases/latest | \
            grep -o '"tag_name": "v[^"]*"' | cut -d'"' -f4 | sed 's/^v//' || echo "2.4.2")
        echo "Downloading simplesamlphp v$sp_latest..."
        curl -sL "https://github.com/simplesamlphp/simplesamlphp/releases/download/v${sp_latest}/simplesamlphp-${sp_latest}-slim.tar.gz" | \
            tar -xzf - -C "$prefix/_codebase" && \
                mv "$prefix/_codebase/simplesamlphp-${sp_latest}" "$prefix/_codebase/simplesamlphp" 2>/dev/null || true
        echo "Build environment ready: bluespice/ and simplesamlphp/ created"
    fi
}

dry_run_build() {
    echo "Testing Docker builds in images/ directory (no images created)..."
    for dir in images/*/; do
        if [[ -d "$dir" && -f "$dir/Dockerfile" ]]; then
            local image_name=$(basename "$dir")
            local temp_file="/tmp/build_test_${image_name}"
            echo "Testing build for $image_name..."
            docker build --iidfile "$temp_file" "$dir" && \
            docker rmi "$(cat "$temp_file")" 2>/dev/null && \
            rm -f "$temp_file"
        fi
    done
    echo "Dry-run build tests completed!"
}

build_images() {
    echo "Building Docker images in images/ directory..."
    for dir in images/*/; do
        if [[ -d "$dir" && -f "$dir/Dockerfile" ]]; then
            local image_name=$(basename "$dir")
            echo "Building $image_name..."
            docker build -t "bluespice/$image_name:$IMAGES_VERSION_TAG" "$dir"
        fi
    done
    echo "Build completed!"
}

case $ACTION in
    add) iterate_components add_git_remote_repos; git remote -v ;;
    build) build_images ;;
    dry-run) dry_run_build ;;
    help) echo "Too few options provided"; show_usage; exit 1 ;;
    init)
        echo "To initialize git subtree, run the following commands.";
        echo "Your working tree must be clean, otherwise git subtree will fail.";
        echo "If you would like to keep all change histories, remove --squash flags.";
        echo "";
        iterate_components init_git_subtree ;;
    prepare) iterate_components prepare_build ;;
    update) iterate_components update_repos_git_subtree ;;
esac

