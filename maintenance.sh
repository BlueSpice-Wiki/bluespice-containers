#!/bin/bash
cd "$(dirname "$0")"
source .env

show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo "To build certain images that need special access, pass GITHUB_TOKEN and GITLAB_HW_TOKEN"
    echo "Options:"
    echo "  -a, --add-remote     Add remote repositories. Current remotes can be listed with 'git remote -v'"
    echo "  -b, --build          Build Docker images"
    echo "  -d, --dry-run        Test to build Docker images (no images will actually be saved)"
    echo "  -h, --help           Show this help message"
    echo "  -i, --init-subtree   Show initializing commands of git subtree. Decide whether to run them on your own"
    echo "  -u, --update         Update git subtree repositories. Run after adding remote repos"
}

ACTION="help"
while [[ $# -gt 0 ]]; do
    case $1 in
        -a|--add-remote) ACTION="add"; shift ;;
        -b|--build) ACTION="build"; shift ;;
        -d|--dry-run) ACTION="dry-run"; shift ;;
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

dry_run_build() {
    echo "Testing Docker builds in images/ directory (no images created)..."
    local SECRET_ARGS=""
    [[ -n "$GITHUB_TOKEN" ]] && SECRET_ARGS+=" --secret id=GIT_AUTH_TOKEN.github.com,env=GITHUB_TOKEN"
    [[ -n "$GITLAB_HW_TOKEN" ]] && SECRET_ARGS+=" --secret id=GIT_AUTH_TOKEN.gitlab.hallowelt.com,env=GITLAB_HW_TOKEN"
    for dir in images/*/; do
        if [[ -d "$dir" && -f "$dir/Dockerfile" ]]; then
            local image_name=$(basename "$dir")
            local temp_file="/tmp/build_test_${image_name}"
            echo "Testing build for $image_name..."
            docker build --iidfile "$temp_file" $SECRET_ARGS "$dir" && \
            docker rmi "$(cat "$temp_file")" 2>/dev/null && \
            rm -f "$temp_file"
        fi
    done
    echo "Dry-run build tests completed!"
}

build_images() {
    echo "Building Docker images in images/ directory..."
    local SECRET_ARGS=""
    [[ -n "$GITHUB_TOKEN" ]] && SECRET_ARGS+=" --secret id=GIT_AUTH_TOKEN.github.com,env=GITHUB_TOKEN"
    [[ -n "$GITLAB_HW_TOKEN" ]] && SECRET_ARGS+=" --secret id=GIT_AUTH_TOKEN.gitlab.hallowelt.com,env=GITLAB_HW_TOKEN"
    for dir in images/*/; do
        if [[ -d "$dir" && -f "$dir/Dockerfile" ]]; then
            local image_name=$(basename "$dir")
            echo "Building $image_name..."
            docker build -t "bluespice/$image_name:$IMAGES_VERSION_TAG" $SECRET_ARGS "$dir"
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
    update) iterate_components update_repos_git_subtree ;;
esac

