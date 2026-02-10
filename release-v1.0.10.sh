#!/bin/bash

# Release script for v1.0.10
# Prepares and tags the v1.0.10 release.

set -e

VERSION="1.0.10"
echo "🚀 Preparing release v${VERSION}..."

# 1. Ensure docs and assets are in order
echo "📋 Checking docs/img screenshots..."
test -f docs/img/overlay-tooltip.png || { echo "Missing docs/img/overlay-tooltip.png"; exit 1; }
test -f docs/img/toolbar-dropdown.png || { echo "Missing docs/img/toolbar-dropdown.png"; exit 1; }
test -f docs/img/devtools-html-comments.png || { echo "Missing docs/img/devtools-html-comments.png"; exit 1; }

# 2. Changelog must contain [1.0.10]
echo "📋 Checking CHANGELOG..."
grep -q "\[1.0.10\]" docs/CHANGELOG.md || { echo "docs/CHANGELOG.md should contain [1.0.10]"; exit 1; }

# 3. Add all changes
echo "📦 Adding all changes..."
git add .

# 4. Commit
echo "💾 Committing..."
git commit -m "chore: Prepare release v${VERSION}

- Add screenshots to README (docs/img/)
- Add USAGE.md and How to use panel tab
- Filter by path + comma-separated; persistent highlight frames
- Template timing fix (Symfony Bridge ProfilerExtension)
- Demos: multiple Twig partials; Vitest tests for filter
- README/INSTALLATION/UPGRADING/CHANGELOG updated"

# 5. Tag
echo "🏷️  Creating tag v${VERSION}..."
git tag -a "v${VERSION}" -m "Release v${VERSION}

- Screenshots in README (overlay tooltip, toolbar dropdown, DevTools)
- How to use as first panel tab; toolbar dropdown simplified
- Filter by template name or path; comma-separated (OR)
- Persistent highlight frames when filter active
- Template timing in panel (Symfony Bridge profiler)
- USAGE.md, demos split into partials, Vitest tests
- UPGRADING and CHANGELOG updated"

# 6. Summary
echo ""
echo "📋 Summary:"
echo "==========="
git log --oneline -1
echo ""
echo "🏷️  Tag:"
git tag -l -n1 "v${VERSION}"
echo ""
read -p "Push commits and tag to remote? (y/N) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    git push origin main
    git push origin "v${VERSION}"
    echo ""
    echo "✅ Release v${VERSION} completed."
    echo "   https://github.com/nowo-tech/twig-inspector-bundle/releases"
else
    echo "Push manually: git push origin main && git push origin v${VERSION}"
fi
