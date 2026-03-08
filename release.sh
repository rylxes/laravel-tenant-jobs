#!/bin/bash

# Laravel Tenant Jobs - Release Script
# Usage: ./release.sh [version]
# Example: ./release.sh 1.1.0

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Check if version is provided
if [ -z "$1" ]; then
    print_error "Version number required!"
    echo "Usage: ./release.sh [version]"
    echo "Example: ./release.sh 1.1.0"
    exit 1
fi

VERSION=$1
TAG="v${VERSION}"

print_info "Starting release process for version ${VERSION}..."

# Check if we're on main branch
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    print_warning "You are on branch '${CURRENT_BRANCH}', not 'main'"
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Release cancelled"
        exit 1
    fi
fi

# Check for uncommitted changes
if [ -n "$(git status --porcelain)" ]; then
    print_warning "You have uncommitted changes!"
    git status --short
    read -p "Commit and continue? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        git add .
        git commit -m "Prepare release ${VERSION}"
        print_success "Changes committed"
    else
        print_error "Release cancelled"
        exit 1
    fi
fi

# Check if tag already exists
if git rev-parse "$TAG" >/dev/null 2>&1; then
    print_error "Tag ${TAG} already exists!"
    exit 1
fi

# Update CHANGELOG.md
print_info "Updating CHANGELOG.md..."
TODAY=$(date +%Y-%m-%d)
CHANGELOG_ENTRY="## [${VERSION}] - ${TODAY}\n\n### Added\n- \n\n### Changed\n- \n\n### Fixed\n- \n\n"

# Ask for changelog entry
print_warning "Please describe changes for this release:"
read -p "What was added? (optional): " ADDED
read -p "What was changed? (optional): " CHANGED
read -p "What was fixed? (optional): " FIXED

# Build changelog entry
CHANGELOG_ENTRY="## [${VERSION}] - ${TODAY}\n\n"
if [ -n "$ADDED" ]; then
    CHANGELOG_ENTRY+="### Added\n- ${ADDED}\n\n"
fi
if [ -n "$CHANGED" ]; then
    CHANGELOG_ENTRY+="### Changed\n- ${CHANGED}\n\n"
fi
if [ -n "$FIXED" ]; then
    CHANGELOG_ENTRY+="### Fixed\n- ${FIXED}\n\n"
fi

# Insert into CHANGELOG (after the first heading)
sed -i.bak "/^# Changelog/a\\
\\
${CHANGELOG_ENTRY}" CHANGELOG.md && rm CHANGELOG.md.bak

print_success "CHANGELOG.md updated"

# Commit changelog
git add CHANGELOG.md
git commit -m "Update CHANGELOG for ${VERSION}"

# Create tag
print_info "Creating git tag ${TAG}..."
git tag -a "$TAG" -m "Release ${VERSION}"
print_success "Tag ${TAG} created"

# Push to remote
print_info "Pushing to remote..."
git push origin "$CURRENT_BRANCH"
git push origin "$TAG"
print_success "Pushed to remote"

# Create GitHub release (if gh CLI is available)
if command -v gh &> /dev/null; then
    print_info "Creating GitHub release..."

    RELEASE_NOTES="## Version ${VERSION}\n\n"
    [ -n "$ADDED" ] && RELEASE_NOTES+="### Added\n- ${ADDED}\n\n"
    [ -n "$CHANGED" ] && RELEASE_NOTES+="### Changed\n- ${CHANGED}\n\n"
    [ -n "$FIXED" ] && RELEASE_NOTES+="### Fixed\n- ${FIXED}\n\n"
    RELEASE_NOTES+="\n---\n\n**Full Changelog**: https://github.com/rylxes/laravel-tenant-jobs/compare/v${VERSION}...${TAG}"

    echo -e "$RELEASE_NOTES" | gh release create "$TAG" \
        --title "v${VERSION}" \
        --notes-file -

    print_success "GitHub release created"
else
    print_warning "GitHub CLI (gh) not installed. Create release manually at:"
    echo "https://github.com/rylxes/laravel-tenant-jobs/releases/new?tag=${TAG}"
fi

# Success message
echo ""
print_success "Release ${VERSION} completed successfully!"
echo ""
print_info "Next steps:"
echo "  1. Packagist will auto-update via GitHub webhook"
echo "  2. Check release at: https://github.com/rylxes/laravel-tenant-jobs/releases"
echo "  3. Verify on Packagist: https://packagist.org/packages/rylxes/laravel-tenant-jobs"
echo ""
print_info "Installation command for users:"
echo "  composer require rylxes/laravel-tenant-jobs:^${VERSION}"
echo ""
