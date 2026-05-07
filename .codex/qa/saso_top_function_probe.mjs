import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';

const baseUrl = process.env.SASO_QA_BASE_URL ?? 'https://saso.sksl.jp/';
const runs = Number.parseInt(process.env.SASO_QA_RUNS ?? '1', 10);
const outDir = process.env.SASO_QA_OUT_DIR ?? '.codex/qa/results';

const searchTerms = ['テスト', 'SASO', 'barcode'];

function unique(values) {
  return [...new Set(values.filter(Boolean))];
}

function normalizeText(value) {
  return String(value ?? '').replace(/\s+/g, ' ').trim();
}

async function collectTopState(page) {
  return await page.evaluate(() => {
    const visibleText = (el) => {
      const style = window.getComputedStyle(el);
      if (style && (style.visibility === 'hidden' || style.display === 'none')) {
        return '';
      }
      return (el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim();
    };

    const anchors = [...document.querySelectorAll('a[href]')].map((a) => ({
      text: visibleText(a),
      href: a.href,
      target: a.target || '',
    }));

    const buttons = [...document.querySelectorAll('button, input[type="submit"], input[type="button"]')].map((b) => ({
      text: visibleText(b) || b.value || b.getAttribute('aria-label') || '',
      type: b.type || '',
    }));

    const forms = [...document.querySelectorAll('form')].map((form) => ({
      method: form.method,
      action: form.action,
      inputs: [...form.querySelectorAll('input, select, textarea')].map((input) => ({
        tag: input.tagName.toLowerCase(),
        type: input.type || '',
        name: input.name || '',
        id: input.id || '',
        placeholder: input.placeholder || '',
        required: input.required || false,
      })),
    }));

    const searchCandidates = [...document.querySelectorAll('input, textarea')].filter((input) => {
      const haystack = [
        input.type,
        input.name,
        input.id,
        input.placeholder,
        input.getAttribute('aria-label'),
        input.closest('form')?.innerText,
      ].join(' ').toLowerCase();
      return haystack.includes('search') || haystack.includes('検索') || input.type === 'search';
    }).map((input) => ({
      name: input.name || '',
      id: input.id || '',
      type: input.type || '',
      placeholder: input.placeholder || '',
      formAction: input.form?.action || '',
    }));

    return {
      title: document.title,
      h1: [...document.querySelectorAll('h1,h2')].map(visibleText),
      anchors,
      buttons,
      forms,
      searchCandidates,
    };
  });
}

async function checkHref(context, href) {
  const response = await context.request.get(href, {
    maxRedirects: 0,
    failOnStatusCode: false,
  });

  return {
    href,
    status: response.status(),
    location: response.headers().location ?? null,
    contentType: response.headers()['content-type'] ?? null,
  };
}

async function runOnce(browser, runIndex) {
  const context = await browser.newContext({
    locale: 'ja-JP',
    timezoneId: 'Asia/Tokyo',
  });
  const page = await context.newPage();
  const consoleErrors = [];
  const requestFailures = [];
  const pageErrors = [];

  page.on('console', (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text());
    }
  });
  page.on('requestfailed', (request) => {
    requestFailures.push({
      url: request.url(),
      method: request.method(),
      failure: request.failure()?.errorText ?? 'unknown',
    });
  });
  page.on('pageerror', (error) => {
    pageErrors.push(error.message);
  });

  const startedAt = new Date().toISOString();
  const response = await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});

  const topState = await collectTopState(page);
  const screenshotPath = runIndex === 1 ? path.join(outDir, 'top-page-run-001.png') : null;
  if (screenshotPath) {
    await page.screenshot({ path: screenshotPath, fullPage: true });
  }

  const samePageLinks = topState.anchors
    .map((a) => a.href)
    .filter((href) => href.startsWith(baseUrl) || href.startsWith(new URL(baseUrl).origin));

  const linkChecks = [];
  for (const href of unique(samePageLinks)) {
    linkChecks.push(await checkHref(context, href));
  }

  const duplicateLinkLabels = Object.entries(
    topState.anchors.reduce((acc, anchor) => {
      const key = normalizeText(anchor.text);
      if (key) {
        acc[key] = acc[key] ?? [];
        acc[key].push(anchor.href);
      }
      return acc;
    }, {}),
  ).filter(([, hrefs]) => unique(hrefs).length > 1);

  const loginResult = {
    attempted: false,
    urlAfterSubmit: null,
    titleAfterSubmit: null,
    visibleErrorText: '',
  };

  const loginId = page.locator('#login-id, input[name="id"]').first();
  const password = page.locator('#login-password, input[name="password"]').first();
  const submit = page.locator('form button[type="submit"], form input[type="submit"]').first();
  if (await loginId.count() && await password.count() && await submit.count()) {
    loginResult.attempted = true;
    await loginId.fill(`codex_probe_${runIndex}`);
    await password.fill(`invalid-password-${runIndex}`);
    await Promise.all([
      page.waitForLoadState('domcontentloaded', { timeout: 15000 }).catch(() => {}),
      submit.click(),
    ]);
    await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
    loginResult.urlAfterSubmit = page.url();
    loginResult.titleAfterSubmit = await page.title();
    loginResult.visibleErrorText = normalizeText(await page.locator('.alert, .invalid-feedback, [role="alert"], .text-danger').allInnerTexts().catch(() => []));
  }

  const searchChecks = [];
  if (topState.searchCandidates.length > 0) {
    for (const candidate of topState.searchCandidates) {
      for (const term of searchTerms) {
        searchChecks.push({
          candidate,
          term,
          observed: 'search candidate exists on the top page; execution skipped to avoid mutating authenticated state',
        });
      }
    }
  }

  await context.close();
  return {
    runIndex,
    startedAt,
    finishedAt: new Date().toISOString(),
    baseUrl,
    status: response?.status() ?? null,
    finalUrl: page.url(),
    topState,
    linkChecks,
    duplicateLinkLabels,
    loginResult,
    searchChecks,
    consoleErrors,
    requestFailures,
    pageErrors,
  };
}

function summarize(results) {
  const issueCandidates = [];
  const first = results[0];

  const auth0Duplicates = first.topState.anchors.filter((a) => normalizeText(a.text) === 'Auth0 でログイン');
  if (auth0Duplicates.length > 1) {
    issueCandidates.push({
      key: 'duplicate-auth0-login-buttons',
      title: '[bug]: Login page renders duplicate indistinguishable Auth0 buttons',
      summary: 'トップ画面の外部ログイン欄に、同じ文言の「Auth0 でログイン」ボタンが複数表示されます。',
      actual: auth0Duplicates.map((a) => `${normalizeText(a.text)} -> ${a.href}`).join('\n'),
      expected: 'ユーザーが選択先を判断できるよう、プロバイダ名や説明が一意に表示されること。',
    });
  }

  if (first.topState.searchCandidates.length === 0) {
    issueCandidates.push({
      key: 'top-search-not-available-without-auth',
      title: '[bug]: Top page has no searchable UI for unauthenticated functional testing',
      summary: '依頼された「トップ画面からアクセスや検索できる情報」のうち、検索入力欄は未認証トップ画面には表示されませんでした。',
      actual: 'トップ画面はログイン画面で、検索候補のinput/textareaは0件でした。認証情報なしでは検索機能の動作確認に進めません。',
      expected: '未認証で検索を提供しない設計であれば仕様として明記されること。検索試験が必要な場合はテスト用アカウントでログイン後の導線を確認できること。',
    });
  }

  const localLoginRedirects = first.linkChecks.filter((check) => check.href.endsWith('/auth/start/1') && check.status === 302);
  if (localLoginRedirects.some((check) => check.location === '/auth/start')) {
    issueCandidates.push({
      key: 'local-login-provider-redirects-to-auth-start',
      title: '[bug]: Local Login provider button redirects back to generic auth start',
      summary: 'トップ画面の「Local Login でログイン」ボタンは、専用ログイン処理ではなく `/auth/start` へ302リダイレクトします。',
      actual: localLoginRedirects.map((check) => `${check.href} -> ${check.status} ${check.location}`).join('\n'),
      expected: 'ローカルログインボタンを表示する場合は、クリック後に利用者が明確にログインを継続できる画面または処理へ遷移すること。既存フォームと重複するだけなら非表示にすること。',
    });
  }

  return {
    totalRuns: results.length,
    successfulLoads: results.filter((r) => r.status === 200).length,
    loginAttempts: results.filter((r) => r.loginResult.attempted).length,
    topTitle: first.topState.title,
    links: first.topState.anchors,
    forms: first.topState.forms,
    issueCandidates,
    requestFailureCount: results.reduce((sum, r) => sum + r.requestFailures.length, 0),
    consoleErrorCount: results.reduce((sum, r) => sum + r.consoleErrors.length, 0),
  };
}

await fs.mkdir(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const results = [];
try {
  for (let i = 1; i <= runs; i += 1) {
    results.push(await runOnce(browser, i));
    console.log(`completed ${i}/${runs}`);
  }
} finally {
  await browser.close();
}

const summary = summarize(results);
await fs.writeFile(path.join(outDir, 'saso-top-probe-results.json'), JSON.stringify(results, null, 2));
await fs.writeFile(path.join(outDir, 'saso-top-probe-summary.json'), JSON.stringify(summary, null, 2));
console.log(JSON.stringify(summary, null, 2));
