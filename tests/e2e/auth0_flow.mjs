import { chromium } from 'playwright';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

dotenv.config({ path: path.resolve(__dirname, '../../.env') });

const {
  AUTH0_DOMAIN,
  AUTH0_CLIENT_ID,
  AUTH0_CLIENT_SECRET,
  AUTH0_CLIENT_USER_EMAIL,
  AUTH0_CLIENT_USER_PASSWORD,
} = process.env;

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // 1. Go to provider creation page
    console.log('Navigating to provider creation page...');
    await page.goto('http://localhost:8080/auth/provider');

    // 2. Select Auth0
    console.log('Selecting Auth0...');
    await page.click('[data-provider="auth0"]');

    // 3. Fill the form
    const uniqueName = 'E2E Auth0 ' + Date.now();
    console.log('Filling Auth0 form for ' + uniqueName + '...');
    await page.fill('#auth0-provider-name', uniqueName);
    await page.fill('#auth0-domain', AUTH0_DOMAIN);
    await page.fill('#auth0-client-id', AUTH0_CLIENT_ID);
    await page.fill('#auth0-client-secret', AUTH0_CLIENT_SECRET);

    // 4. Test connection
    console.log('Testing connection...');
    await page.click('[data-test-connection="auth0"]');
    await page.waitForSelector('#auth0-test-result .text-success', { timeout: 15000 });
    console.log('Connection test successful.');

    // 5. Save the provider
    console.log('Saving provider...');
    await page.click('#form-auth0 button[type="submit"]');
    
    // Wait for potential redirect or UI update
    await page.waitForTimeout(3000);

    // 6. Go to login page
    console.log('Navigating to login page...');
    await page.goto('http://localhost:8080/auth/start');

    // 7. Find the Auth0 button and click it
    console.log('Starting Auth0 login flow...');
    const auth0Btn = page.locator('a:has-text("' + uniqueName + '")');
    await auth0Btn.waitFor({ state: 'visible', timeout: 5000 });
    await auth0Btn.click();

    // 8. Auth0 Universal Login
    console.log('Handling Auth0 Universal Login...');
    // We expect to be redirected to Auth0. 
    // Wait for the URL to change to the Auth0 domain.
    await page.waitForURL(url => url.host === AUTH0_DOMAIN, { timeout: 15000 });
    
    // Auth0 Universal Login page often uses 'username' or 'email' name for the field
    await page.fill('input[name="username"], input[name="email"]', AUTH0_CLIENT_USER_EMAIL);
    await page.fill('input[name="password"]', AUTH0_CLIENT_USER_PASSWORD);
    
    // Click submit/login button
    await page.click('button[type="submit"], button:has-text("Continue"), button:has-text("Log In")');

    // 9. Handle potential consent page (if it's the first time or prompted)
    try {
        await page.waitForSelector('button[value="accept"], button:has-text("Accept")', { timeout: 5000 });
        await page.click('button[value="accept"], button:has-text("Accept")');
    } catch (e) {
        // Consent might not appear, ignore
    }

    // 10. Verify callback and home page
    console.log('Verifying callback and home page...');
    // The final redirect should be to the app home page
    await page.waitForURL('http://localhost:8080/', { timeout: 20000 });
    console.log('Successfully logged in via Auth0!');

  } catch (error) {
    console.error('Test failed:', error);
    await page.screenshot({ path: 'e2e-failure.png' });
    // Also log the current URL and page content for debugging
    console.log('Current URL:', page.url());
    console.log('Page content:', await page.content());
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
