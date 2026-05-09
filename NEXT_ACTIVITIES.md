So# AI Features Audit — Current Status & Next Activities

**Date:** 2026-05-03 | **Branch:** `feat/auth-provider-web-ui`

---

## Completed Work

### ✅ Step 1: Fixed AiAssistantFactory setting key mismatch
- Changed 5 setting key references from dot notation to underscore notation
- Added empty-array guard to prevent `FallbackChainAssistant([])` edge case
- File: `src/Infrastructure/Ai/AiAssistantFactory.php`

### ✅ Step 2: Enhanced ConfigLoader for .ENV environment population
- Added `foreach` loop in `ConfigLoader::load()` to call `putenv()` for each .ENV variable
- This bridges the gap between .ENV file parsing and `getenv()` availability
- File: `ConfigLoader.php:34-39`

### ✅ Step 3: Modified AiAssistantFactory for LOCAL_GEMINI_KEY fallback
- Added check for `LOCAL_GEMINI_KEY` as fallback when `GEMINI_API_KEY` not set
- Supports development environments using `.ENV` file
- File: `src/Infrastructure/Ai/AiAssistantFactory.php:121-126`

### ✅ Step 4: Container & Config Updates
- Added `APP_KEY` and `AI_PROVIDER` to `docker-compose.yml` environment
- Added `APP_KEY` to `.ENV` file (32-byte base64 encryption key)
- `.ENV` contains: `LOCAL_GEMINI_KEY`, `AI_PROVIDER=gemini`, `APP_DEBUG=true`

### ✅ Step 5: Created debug/AiDebugDIContainer.php
- `GET /debug/ai-status` endpoint: Returns JSON with provider config
- `POST /debug/ai-probe` endpoint: Tests AI vision directly
- Both routes guarded by `APP_DEBUG=true`

---

## Current Issue & Investigation

### Problem
`/debug/ai-status` endpoint shows:
```json
{
  "provider_vision": "null",
  "provider_chat": "null",
  "keys_configured": false,
  "assistant_class": "NullAssistant",
  "env_override": "gemini"
}
```

**Issue:** Even though:
- `AI_PROVIDER=gemini` is correctly detected (`env_override: "gemini"`)
- `LOCAL_GEMINI_KEY` is in `.ENV` file and correctly parsed by `EnvLoader`
- `ConfigLoader::load()` is called on every request (verified in `index.php:10`)

The `LOCAL_GEMINI_KEY` is NOT being picked up by `getenv()` in the web request context.

### What Was Tested
1. ✅ Verified `.ENV` file exists in container at `/var/www/html/saso/.env`
2. ✅ Verified `EnvLoader::loadFile()` correctly parses `.ENV` and returns all 9 key-value pairs including `LOCAL_GEMINI_KEY`
3. ✅ Verified `ConfigLoader::load()` is called during bootstrap (`index.php:10`)
4. ❌ **Unconfirmed:** Whether `putenv()` is actually being executed and persisting in the web server process

### Root Cause Hypothesis
The `putenv()` calls in `ConfigLoader::load()` may not be working as expected because:
- A. The condition `if (!getenv($key))` might be evaluating incorrectly
- B. `putenv()` might not persist across the web server's request handling
- C. The `putenv()` code might not be reached due to early exit or exception
- D. Apache/PHP-FPM configuration might be isolating environment variables between processes

---

## Next Steps (Prioritized)

### 1. Debug putenv() execution 
**Add logging to ConfigLoader::load()** to verify:
- Whether `putenv()` is being called for `LOCAL_GEMINI_KEY`
- What value is being passed to `putenv()`
- Whether subsequent `getenv()` calls within the same request can access it

**File to modify:** `ConfigLoader.php` — add temporary debug output or use error_log()

**Expected outcome:** Determine if `putenv()` is running but not persisting, or if it's not running at all.

### 2. Test via HTTP directly  
Created `/debug/test-env.php` to test:
```php
// Quick check: print all accessible env vars
echo json_encode(getenv());
```

This shows whether ANY .ENV variables are accessible via `getenv()` in the web request context.

### 3. Implement alternative: Docker container environment variables
If `putenv()` approach doesn't work reliably, switch to:
- Copy .ENV to Docker container startup via `docker-compose.yml` for non-sensitive vars
- Keep `LOCAL_GEMINI_KEY` in container `environment` section (or source from `.env`)
- Rebuild image if needed

### 4. Verify full AI pipeline after env fix
Once `LOCAL_GEMINI_KEY` is accessible:
- Test `/debug/ai-status` → expect `keys_configured: true`, `provider_vision: "gemini"`
- Test `POST /debug/ai-probe` with image payload
- Submit draft via `POST /api/v1/items/drafts` and verify auto-fill works

### 5. Continue implementation plan
After environment issue is resolved, implement remaining tasks:
- [ ] Migration seeder for `ai.auto_judge` feature flag
- [ ] `AiJudgeAutoSync` service for auto-managed flag
- [ ] Flag gate in `ProcessItemDraftHandler`
- [ ] `KeywordLookupStep` for enrichment pipeline
- [ ] 10 unit test cases for pipeline
- [ ] Full integration testing with JAN-13, ISBN-13, image-only drafts

---

## Files Modified This Session
- `ConfigLoader.php` — added `putenv()` loop for .ENV parsing
- `src/Infrastructure/Ai/AiAssistantFactory.php` — fixed key names + added LOCAL_GEMINI_KEY fallback
- `docker-compose.yml` — added `APP_KEY` and `AI_PROVIDER` to environment
- `.ENV` — added `APP_KEY` (32-byte base64 key)
- `debug/AiDebugDIContainer.php` — new debug endpoints
- `debug/test-env.php` — new test helper for env var debugging

## Key Insight
`putenv()` may not persist reliably in Apache/PHP-FPM environments. The working variables (`APP_KEY`, `AI_PROVIDER`) are coming from `docker-compose.yml` environment section, NOT from `.ENV` parsing. Consider switching to explicit Docker env vars for `LOCAL_GEMINI_KEY` instead of relying on `putenv()`.
