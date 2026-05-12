# Release checklist

Use this checklist when cutting a new version. The workflow [.github/workflows/release.yml](../.github/workflows/release.yml) runs on push of a tag `v*` and creates the GitHub Release with body from the tag message and changelog.

## Before tagging

1. **CHANGELOG.md**
   - Include all changes since the previous tag (e.g. `git log vX.Y.Z..HEAD`, `git diff vX.Y.Z..HEAD --stat`).
   - Move [Unreleased] entries to a new version section `## [X.Y.Z] - YYYY-MM-DD`.
   - Leave an empty `## [Unreleased]` at the top.

2. **UPGRADING.md**
   - Document the same changes since the previous tag for users upgrading from X.Y.(Z-1) to X.Y.Z.
   - Rename "Upgrading from X.Y.(Z-1) to the next release" to "Upgrading from X.Y.(Z-1) to X.Y.Z".
   - Optionally add a short "Upgrading from X.Y.Z to the next release" placeholder for the next cycle.

3. **Commit**
   - Commit CHANGELOG and UPGRADING (and any other release-related changes).
   - Push to `main` (or merge your release branch).

## Tag and push

Replace `X.Y.Z` with the version (e.g. `1.0.11`):

```bash
git checkout main
git pull origin main
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push origin vX.Y.Z
```

Example for v1.0.31:

```bash
git tag -a v1.0.31 -m "Release v1.0.31"
git push origin v1.0.31
```

After the push, GitHub Actions will create the release and attach the changelog entry (from `docs/CHANGELOG.md`) to the release body. Packagist will pick up the new tag automatically.

For branching and versioning policy, see [BRANCHING.md](BRANCHING.md).

**Sync workflow** ([`sync-releases.yml`](../.github/workflows/sync-releases.yml)) runs on a **schedule** and **manual dispatch** only. It does not run on tag push, so it does not race with this release job when you push `v*`.
