# Vaultic Release Status

This document tracks the recommended production tags after compatibility auditing.

## Recommended Production Tags

- v1.0.2 -> PHP 7.1.3+, Laravel 5.5-5.8
- v1.2.1 -> PHP 7.2.5+, Laravel 6.x
- v1.3.0 -> PHP 7.2.5+, Laravel 7.x
- v2.0.0 -> PHP 7.3+, Laravel 8.x
- v3.0.1 -> PHP 8.0.2+, Laravel 9.x
- v3.1.0 -> PHP 8.1+, Laravel 10.x
- v3.2.1 -> PHP 8.2+, Laravel 11.x
- v3.3.1 -> PHP 8.2+, Laravel 12.x
- v4.0.0 -> PHP 8.3+, Laravel 13.x

## Superseded Tags

- v1.0.0, v1.0.1 superseded by v1.0.2
- v1.2.0 superseded by v1.2.1
- v3.0.0 superseded by v3.0.1
- v3.2.0 superseded by v3.2.1
- v3.3.0 superseded by v3.3.1

## Professional Release Publishing

Use the automated script:

```bash
bash scripts/publish_releases.sh
```

Authentication requirements:

- Either: `gh auth login`
- Or: `export GITHUB_TOKEN=<token_with_repo_scope>`

The script creates a professional title and description for each existing tag.
