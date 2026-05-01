#!/usr/bin/env bash
set -euo pipefail

# Publish professional GitHub releases for all Vaultic tags.
# Requirements:
# - Option A: gh CLI authenticated (gh auth login)
# - Option B: GITHUB_TOKEN with repo scope

OWNER="hamdyelbatal122"
REPO="vaultic"

TAGS=(
  "v1.0.0"
  "v1.0.1"
  "v1.0.2"
  "v1.2.0"
  "v1.2.1"
  "v1.3.0"
  "v2.0.0"
  "v3.0.0"
  "v3.0.1"
  "v3.1.0"
  "v3.2.0"
  "v3.2.1"
  "v3.3.0"
  "v3.3.1"
  "v4.0.0"
)

title_for_tag() {
  case "$1" in
    v1.0.0) echo "Vaultic v1.0.0 - Initial Legacy Baseline" ;;
    v1.0.1) echo "Vaultic v1.0.1 - Legacy Architecture Stabilization" ;;
    v1.0.2) echo "Vaultic v1.0.2 - Legacy Compatibility Patch" ;;
    v1.2.0) echo "Vaultic v1.2.0 - Laravel 6 Support" ;;
    v1.2.1) echo "Vaultic v1.2.1 - Laravel 6 Compatibility Patch" ;;
    v1.3.0) echo "Vaultic v1.3.0 - Laravel 7 Support" ;;
    v2.0.0) echo "Vaultic v2.0.0 - Laravel 8 and Named Rate Limiter" ;;
    v3.0.0) echo "Vaultic v3.0.0 - Laravel 9 Modernization" ;;
    v3.0.1) echo "Vaultic v3.0.1 - Laravel 9 Compatibility Patch" ;;
    v3.1.0) echo "Vaultic v3.1.0 - Laravel 10 Support" ;;
    v3.2.0) echo "Vaultic v3.2.0 - Laravel 11 Support" ;;
    v3.2.1) echo "Vaultic v3.2.1 - Laravel 11 Compatibility Patch" ;;
    v3.3.0) echo "Vaultic v3.3.0 - Laravel 12 Support" ;;
    v3.3.1) echo "Vaultic v3.3.1 - Laravel 12 Compatibility Patch" ;;
    v4.0.0) echo "Vaultic v4.0.0 - Laravel 13 Modern Release" ;;
    *) echo "Vaultic $1" ;;
  esac
}

notes_for_tag() {
  case "$1" in
    v1.0.0)
      cat <<'EOF'
## Overview
Initial baseline release of Vaultic for legacy Laravel applications.

## Highlights
- Passkey/WebAuthn foundation and package scaffolding.
- Redis-backed challenge storage model introduced.
- Basic passkey registration/authentication routes.

## Compatibility
- PHP: 7.1+
- Laravel: 5.5 baseline
EOF
      ;;
    v1.0.1)
      cat <<'EOF'
## Overview
Hardening release for the legacy line with architecture cleanup.

## Highlights
- Clean architecture alignment: Controller -> Service -> Repository -> Contracts.
- Improved maintainability and SOLID-oriented separation.
- Legacy-safe code path consolidation.

## Compatibility
- PHP: 7.1+
- Laravel: 5.5 - 5.8
EOF
      ;;
    v1.0.2)
      cat <<'EOF'
## Overview
Compatibility patch for the legacy 5.5 line.

## Highlights
- Corrected minimum PHP requirement to match Laravel 5.5 runtime expectations.
- Documentation updated for accurate install constraints.

## Compatibility
- PHP: 7.1.3+
- Laravel: 5.5 - 5.8

## Notes
Supersedes v1.0.0 and v1.0.1 for production use.
EOF
      ;;
    v1.2.0)
      cat <<'EOF'
## Overview
Dedicated release line for Laravel 6 projects.

## Highlights
- Dependency matrix updated for Laravel 6 components.
- Maintains layered architecture and package contracts.

## Compatibility
- PHP: 7.2+
- Laravel: 6.x
EOF
      ;;
    v1.2.1)
      cat <<'EOF'
## Overview
Compatibility patch for the Laravel 6 line.

## Highlights
- Corrected minimum PHP requirement to official Laravel 6 floor.
- README and package metadata aligned.

## Compatibility
- PHP: 7.2.5+
- Laravel: 6.x

## Notes
Supersedes v1.2.0 for production use.
EOF
      ;;
    v1.3.0)
      cat <<'EOF'
## Overview
Stable support release for Laravel 7 applications.

## Highlights
- Laravel 7 dependency targeting.
- Legacy-friendly implementation retained.

## Compatibility
- PHP: 7.2.5+
- Laravel: 7.x
EOF
      ;;
    v2.0.0)
      cat <<'EOF'
## Overview
Major release for Laravel 8 with improved framework-native controls.

## Highlights
- Added named rate limiter integration (`vaultic.passkeys`).
- Better alignment with Laravel 8 auth/Jetstream-era patterns.
- Continued SOLID architecture and abstraction boundaries.

## Compatibility
- PHP: 7.3+
- Laravel: 8.x
EOF
      ;;
    v3.0.0)
      cat <<'EOF'
## Overview
Modernization release for Laravel 9 and PHP 8.

## Highlights
- Introduced constructor promotion and stricter typing in core DTO/event paths.
- Modernized internals while preserving package extension points.

## Compatibility
- PHP: 8.0+
- Laravel: 9.x
EOF
      ;;
    v3.0.1)
      cat <<'EOF'
## Overview
Compatibility patch for Laravel 9 support line.

## Highlights
- Corrected minimum PHP requirement to Laravel 9 official floor.
- Metadata and docs synchronized.

## Compatibility
- PHP: 8.0.2+
- Laravel: 9.x

## Notes
Supersedes v3.0.0 for production use.
EOF
      ;;
    v3.1.0)
      cat <<'EOF'
## Overview
Release line targeting Laravel 10 environments.

## Highlights
- Dependencies aligned with Laravel 10 stack.
- Preserves modern typed architecture from v3.

## Compatibility
- PHP: 8.1+
- Laravel: 10.x
EOF
      ;;
    v3.2.0)
      cat <<'EOF'
## Overview
Release line for Laravel 11 applications.

## Highlights
- Laravel 11 dependency targeting.
- Maintains named limiter and layered design.

## Compatibility
- PHP: 8.1+
- Laravel: 11.x
EOF
      ;;
    v3.2.1)
      cat <<'EOF'
## Overview
Compatibility patch for Laravel 11 support line.

## Highlights
- Corrected minimum PHP requirement to Laravel 11 official floor.
- Documentation and composer constraints aligned.

## Compatibility
- PHP: 8.2+
- Laravel: 11.x

## Notes
Supersedes v3.2.0 for production use.
EOF
      ;;
    v3.3.0)
      cat <<'EOF'
## Overview
Release line for Laravel 12 ecosystems.

## Highlights
- Laravel 12 dependency targeting.
- Consistent service/repository architecture.

## Compatibility
- PHP: 8.1+
- Laravel: 12.x
EOF
      ;;
    v3.3.1)
      cat <<'EOF'
## Overview
Compatibility patch for Laravel 12 support line.

## Highlights
- Corrected minimum PHP requirement to Laravel 12 official floor.
- Metadata and docs corrected.

## Compatibility
- PHP: 8.2+
- Laravel: 12.x

## Notes
Supersedes v3.3.0 for production use.
EOF
      ;;
    v4.0.0)
      cat <<'EOF'
## Overview
Modern flagship release targeting Laravel 13.

## Highlights
- Laravel 13 and PHP 8.3 baseline.
- Readonly-enhanced DTO/event models for stronger immutability.
- Updated configuration for modern runtime behavior.

## Compatibility
- PHP: 8.3+
- Laravel: 13.x

## Recommended
Use this line for new projects targeting latest Laravel.
EOF
      ;;
    *)
      cat <<'EOF'
## Overview
Release notes unavailable.
EOF
      ;;
  esac
}

create_with_gh() {
  local tag="$1"
  local title="$2"
  local body_file="$3"
  if gh release view "$tag" --repo "$OWNER/$REPO" >/dev/null 2>&1; then
    echo "[skip] Release already exists: $tag"
    return 0
  fi
  gh release create "$tag" \
    --repo "$OWNER/$REPO" \
    --title "$title" \
    --notes-file "$body_file"
}

create_with_api() {
  local tag="$1"
  local title="$2"
  local body_file="$3"
  local body
  body=$(python3 - <<'PY'
import json,sys
p=sys.argv[1]
print(json.dumps(open(p,'r',encoding='utf-8').read()))
PY
"$body_file")

  local payload
  payload=$(cat <<JSON
{
  "tag_name": "$tag",
  "name": "$title",
  "body": $body,
  "draft": false,
  "prerelease": false,
  "generate_release_notes": false
}
JSON
)

  local status
  status=$(curl -sS -o /tmp/release_${tag}.json -w "%{http_code}" \
    -X POST \
    -H "Authorization: Bearer ${GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github+json" \
    "https://api.github.com/repos/${OWNER}/${REPO}/releases" \
    -d "$payload")

  if [[ "$status" == "201" ]]; then
    echo "[ok] Release created: $tag"
  elif [[ "$status" == "422" ]]; then
    echo "[skip] Release exists or validation issue for: $tag"
  else
    echo "[err] Failed for $tag (HTTP $status)"
    cat "/tmp/release_${tag}.json"
    return 1
  fi
}

main() {
  local mode=""

  if command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1; then
    mode="gh"
  elif [[ -n "${GITHUB_TOKEN:-}" ]]; then
    mode="api"
  else
    echo "No GitHub auth available."
    echo "Use one of:"
    echo "1) gh auth login"
    echo "2) export GITHUB_TOKEN=<token_with_repo_scope>"
    exit 1
  fi

  mkdir -p docs/releases/generated

  for tag in "${TAGS[@]}"; do
    title=$(title_for_tag "$tag")
    note_file="docs/releases/generated/${tag}.md"
    notes_for_tag "$tag" > "$note_file"

    if [[ "$mode" == "gh" ]]; then
      create_with_gh "$tag" "$title" "$note_file"
    else
      create_with_api "$tag" "$title" "$note_file"
    fi
  done

  echo "Release publishing pipeline completed."
}

main "$@"
