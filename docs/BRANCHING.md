# Branching Strategy

This project follows a simplified Git Flow workflow.

## Branch Types

| Branch | Purpose | Base | Merges to |
|--------|---------|------|-----------|
| `main` | Production releases only | - | - |
| `develop` | Development integration | `main` | `main` (releases) |
| `feature/*` | New features | `develop` | `develop` |
| `fix/*` | Bug fixes | `develop` | `develop` |
| `hotfix/*` | Urgent production fixes | `main` | `main` + `develop` |
| `release/*` | Release preparation | `develop` | `main` + `develop` |

## Workflow Diagram

```
main     ●─────────────────●─────────────────●  (v1.0.0)  (v1.1.0)
          \               /                 /
develop    ●─────●───●───●─────●───●───────●
                  \     /       \         /
feature/xxx        ●───●         \       /
                                  \     /
fix/yyy                            ●───●
```

## Creating Branches

### New Feature

```bash
git checkout develop
git pull origin develop
git checkout -b feature/my-feature
# ... work on feature ...
git push -u origin feature/my-feature
# Create Pull Request to develop
```

### Bug Fix

```bash
git checkout develop
git pull origin develop
git checkout -b fix/fix-description
# ... fix bug ...
git push -u origin fix/fix-description
# Create Pull Request to develop
```

### Hotfix (urgent production fix)

```bash
git checkout main
git pull origin main
git checkout -b hotfix/critical-fix
# ... fix issue ...
git push -u origin hotfix/critical-fix
# Create Pull Request to main AND develop
```

### Release

```bash
git checkout develop
git pull origin develop
git checkout -b release/1.2.0
# ... update version, changelog ...
git push -u origin release/1.2.0
# Create Pull Request to main
# After merge, tag the release and merge back to develop
```

## Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0): Breaking changes
- **MINOR** (0.X.0): New features, backward compatible
- **PATCH** (0.0.X): Bug fixes, backward compatible

## Tagging Releases

After merging a release to `main`:

```bash
git checkout main
git pull origin main
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0
```

## Branch Protection Rules (GitHub)

Recommended settings for `main` branch:

- ✅ Require pull request before merging
- ✅ Require status checks to pass (CI)
- ✅ Require conversation resolution before merging
- ✅ Do not allow bypassing the above settings

Recommended settings for `develop` branch:

- ✅ Require pull request before merging
- ✅ Require status checks to pass (CI)
- ✅ Require conversation resolution before merging
- ✅ Do not allow bypassing the above settings

## Commit Message Convention

We follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Code style (formatting, etc.) |
| `refactor` | Code refactoring |
| `test` | Adding/updating tests |
| `chore` | Maintenance tasks |

### Examples

```
feat(twig): add support for template exclusion patterns
fix(collector): handle missing session data
docs(readme): update installation instructions
chore(deps): update PHPUnit to v11
```

## Best Practices

1. **Keep branches focused**: One feature/fix per branch
2. **Keep branches short-lived**: Merge as soon as ready
3. **Keep branches up to date**: Regularly rebase on `develop`
4. **Write clear commit messages**: Follow Conventional Commits
5. **Test before pushing**: Ensure all tests pass locally (coverage must be at least 97.5%)
6. **Update documentation**: Keep docs in sync with code changes
7. **Small, frequent commits**: Easier to review and revert if needed
8. **No direct commits to `main` or `develop`**: Always use PRs

## Common Scenarios

### Updating Your Branch with Latest Changes

```bash
git checkout develop
git pull origin develop
git checkout feature/my-feature
git rebase develop
git push origin feature/my-feature --force-with-lease
```

### Renaming a Branch

```bash
# Rename local branch
git branch -m old-name new-name

# Delete old remote branch
git push origin --delete old-name

# Push new branch
git push origin new-name

# Set upstream
git push origin -u new-name
```

### Undoing Last Commit (Before Push)

```bash
git reset --soft HEAD~1  # Keep changes staged
# or
git reset HEAD~1         # Unstage changes
```

### Undoing Last Commit (After Push)

```bash
# Create a new commit that undoes changes
git revert HEAD
git push origin feature/my-feature
```

## Questions?

If you have questions about branching:
- Open an issue on GitHub
- Contact maintainers at hectorfranco@nowo.com
- Check [docs/CONTRIBUTING.md](CONTRIBUTING.md) for general contribution guidelines

