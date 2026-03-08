# Publishing Guide - Laravel Tenant Jobs

Complete step-by-step guide to publish your package to GitHub and Packagist.

---

## Pre-Publishing Checklist

- [x] Vendor name: `rylxes`
- [x] Namespace: `TenantJobs`
- [x] Author: Sherriff Agboola (rylxes@gmail.com)
- [x] License: MIT
- [x] Package name: `rylxes/laravel-tenant-jobs`
- [ ] GitHub repository created
- [ ] Tests passing
- [ ] Documentation complete

---

## Step 1: Create GitHub Repository

### Via GitHub CLI

```bash
gh auth login
gh repo create laravel-tenant-jobs --public --description "Bulletproof multi-tenant queue job handling for Laravel"
```

### Via GitHub Website

1. Go to https://github.com/new
2. Repository name: `laravel-tenant-jobs`
3. Description: `Bulletproof multi-tenant queue job handling for Laravel`
4. Set as **Public** (required for Packagist free tier)
5. **DO NOT** initialize with README, .gitignore, or LICENSE (we have them already)
6. Click "Create repository"

---

## Step 2: Push to GitHub

```bash
cd "/Users/rylxes/WebDevelopment/Plugins/laravel-multitenancy"

# Set remote (already done if using gh repo create)
git remote set-url origin git@github.com:rylxes/laravel-tenant-jobs.git

# Push to main branch
git branch -M main
git push -u origin main
```

---

## Step 3: Create GitHub Release (v1.0.0)

### Via GitHub CLI

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0

gh release create v1.0.0 \
  --title "v1.0.0 - Initial Release" \
  --notes "Bulletproof multi-tenant queue job handling for Laravel. See README for full documentation."
```

### Via Release Script

```bash
./release.sh 1.0.0
```

---

## Step 4: Submit to Packagist

### 4.1 Create Packagist Account

1. Go to https://packagist.org/
2. Click "Login with GitHub" (recommended)
3. Authorize Packagist to access your GitHub

### 4.2 Submit Package

1. Go to https://packagist.org/packages/submit
2. Paste: `https://github.com/rylxes/laravel-tenant-jobs`
3. Click "Check"
4. If validation passes, click "Submit"

### 4.3 Setup Auto-Update

**Option A: GitHub Hook (Recommended)**
1. Go to package page: https://packagist.org/packages/rylxes/laravel-tenant-jobs
2. Click "Settings"
3. Copy the webhook URL
4. Go to: https://github.com/rylxes/laravel-tenant-jobs/settings/hooks
5. Click "Add webhook"
6. Paste URL, Content type: `application/json`
7. Save

**Option B: Packagist API Token**
1. Generate token: https://packagist.org/profile/
2. Add to GitHub Secrets: `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN`
3. The `.github/workflows/packagist-update.yml` workflow handles the rest

---

## Step 5: Enhance GitHub Repository

### Add Topics

Go to https://github.com/rylxes/laravel-tenant-jobs and add topics:
- `laravel`
- `multi-tenant`
- `queue`
- `jobs`
- `tenancy`
- `multitenancy`
- `laravel-package`
- `php`

### Repository Website

Point to: `https://packagist.org/packages/rylxes/laravel-tenant-jobs`

---

## Step 6: Verify Installation

```bash
# In a test Laravel project
composer require rylxes/laravel-tenant-jobs

# Should install successfully!
```

---

## Version Management

### Creating New Releases

```bash
# Full interactive release
./release.sh 1.1.0

# Quick release with auto-bump
./quick-release.sh patch "Fix context leak on retry"
./quick-release.sh minor "Add custom resolver support"
./quick-release.sh major "Breaking: new middleware API"
```

### Semantic Versioning

- **Patch** (v1.0.1): Bug fixes
- **Minor** (v1.1.0): New features (backward compatible)
- **Major** (v2.0.0): Breaking changes

---

## Analytics & Monitoring

- **Packagist Stats**: https://packagist.org/packages/rylxes/laravel-tenant-jobs/stats
- **GitHub Insights**: https://github.com/rylxes/laravel-tenant-jobs/graphs/traffic
