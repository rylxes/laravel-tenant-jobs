#!/bin/bash

# Quick Release Script - For minor updates without manual changelog
# Usage: ./quick-release.sh [patch|minor|major] "Brief description"
# Example: ./quick-release.sh patch "Fix slow query threshold"

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ $1${NC}"; }

# Check arguments
if [ -z "$1" ] || [ -z "$2" ]; then
    print_error "Missing arguments!"
    echo "Usage: ./quick-release.sh [patch|minor|major] \"Description\""
    echo "Example: ./quick-release.sh patch \"Fix slow query threshold\""
    exit 1
fi

BUMP_TYPE=$1
DESCRIPTION=$2

# Get current version from git tags
CURRENT_VERSION=$(git describe --tags --abbrev=0 2>/dev/null | sed 's/v//')
if [ -z "$CURRENT_VERSION" ]; then
    CURRENT_VERSION="1.0.0"
    print_info "No previous tags found. Starting from ${CURRENT_VERSION}"
fi

# Parse version
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]:-0}
MINOR=${VERSION_PARTS[1]:-0}
PATCH=${VERSION_PARTS[2]:-0}

# Bump version
case $BUMP_TYPE in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        CHANGE_TYPE="Breaking Changes"
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        CHANGE_TYPE="Added"
        ;;
    patch)
        PATCH=$((PATCH + 1))
        CHANGE_TYPE="Fixed"
        ;;
    *)
        print_error "Invalid bump type: ${BUMP_TYPE}"
        echo "Use: patch, minor, or major"
        exit 1
        ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
TAG="v${NEW_VERSION}"

print_info "Bumping from ${CURRENT_VERSION} to ${NEW_VERSION} (${BUMP_TYPE})"

# Update CHANGELOG
TODAY=$(date +%Y-%m-%d)
CHANGELOG_ENTRY="## [${NEW_VERSION}] - ${TODAY}\n\n### ${CHANGE_TYPE}\n- ${DESCRIPTION}\n\n"

sed -i.bak "/^# Changelog/a\\
\\
${CHANGELOG_ENTRY}" CHANGELOG.md && rm CHANGELOG.md.bak

print_success "CHANGELOG updated"

# Commit everything
git add .
git commit -m "${DESCRIPTION}" -m "Version ${NEW_VERSION}"

# Create and push tag
git tag -a "$TAG" -m "Release ${NEW_VERSION}: ${DESCRIPTION}"
git push origin main
git push origin "$TAG"

print_success "Released ${TAG}"
print_info "Packagist will auto-update via webhook"
