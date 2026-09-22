# Git hooks

Versioned hooks enforcing the project's branch and commit naming convention:

- **`commit-msg`** — the commit message must start with `feat:`, `fix:` or
  `refacto:` followed by `#SD-XXXX` (4 digits), e.g. `fix: #SD-0005 - ci`.
- **`pre-push`** — the branch name must start with `feat/`, `fix/`,
  `hotfix/`, `junk/` or `refacto/` followed by `SD-XXXX` (4 digits), e.g.
  `feat/SD-0005-media`. `main`, `master` and `develop` are exempt.

`SD` must be uppercase: `.github/workflows/auto-pr.yaml` filters pushed
branches case-sensitively, so a branch named `sd-` would be pushed without
ever opening a PR. Both checks only enforce the prefix — anything can follow
the ticket number.

## Setup

`composer install`/`composer update` wire this automatically
(`git config core.hooksPath .githooks`, see `composer.json`). To do it
manually:

```sh
git config core.hooksPath .githooks
```
