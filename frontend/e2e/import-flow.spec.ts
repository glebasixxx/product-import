import { expect, test, type Page } from '@playwright/test';
import path from 'node:path';

const EXAMPLE_FILE = path.resolve(__dirname, '../../backend/tests/Fixtures/import_example.xlsx');
const EMAIL = process.env['E2E_EMAIL'] ?? 'admin@example.com';
const PASSWORD = process.env['E2E_PASSWORD'] ?? 'password';

async function login(page: Page): Promise<void> {
  await page.goto('/');
  await expect(page).toHaveURL(/\/login/);

  await page.getByTestId('email').fill(EMAIL);
  await page.getByTestId('password').fill(PASSWORD);
  await page.getByTestId('login-submit').click();

  await expect(page).toHaveURL(/\/products/);
}

test.describe('Product import', () => {
  test('guest is redirected to the login page', async ({ page }) => {
    await page.goto('/products/1');

    await expect(page).toHaveURL(/\/login\?returnUrl=%2Fproducts%2F1/);
  });

  test('wrong password shows an error', async ({ page }) => {
    await page.goto('/login');
    await page.getByTestId('email').fill(EMAIL);
    await page.getByTestId('password').fill('definitely-wrong');
    await page.getByTestId('login-submit').click();

    await expect(page.getByRole('alert')).toContainText('Неверный email или пароль');
  });

  test('rejects a non-xlsx file on the client', async ({ page }) => {
    await login(page);
    await page.getByRole('link', { name: 'Импорт' }).first().click();

    await page.getByTestId('file-input').setInputFiles({
      name: 'products.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from('a;b;c'),
    });

    await expect(page.getByTestId('file-error')).toContainText('.xlsx');
    await expect(page.getByTestId('upload-button')).toBeDisabled();
  });

  test('imports the example file, then browses the list and the product card', async ({ page }) => {
    await login(page);

    await page.getByRole('link', { name: 'Импорт' }).first().click();
    await expect(page).toHaveURL(/\/import/);

    await page.getByTestId('file-input').setInputFiles(EXAMPLE_FILE);
    await expect(page.getByTestId('selected-file')).toHaveText('import_example.xlsx');
    await page.getByTestId('upload-button').click();

    const job = page.getByTestId('import-job');
    await expect(job).toBeVisible();
    await expect(page.getByTestId('import-status')).toHaveText('Завершён', { timeout: 170_000 });
    await expect(page.getByTestId('import-progress')).toHaveText('100%');
    await expect(page.getByTestId('failed-count')).toHaveText('0');

    await page.getByRole('link', { name: 'Товары' }).first().click();
    await page.getByTestId('filter-name').fill('Бермуды');
    await page.getByTestId('filter-submit').click();

    await expect(page).toHaveURL(/name=/);
    const links = page.locator('a.product-link');
    await expect(links).toHaveCount(2);
    await expect(links.first()).toContainText('Бермуды');

    await page.getByTestId('filter-price-min').fill('1000');
    await page.getByTestId('filter-submit').click();
    await expect(links).toHaveCount(1);
    await expect(links.first()).toContainText('Grigio/Verde');

    await links.first().click();
    await expect(page).toHaveURL(/\/products\/\d+$/);
    await expect(page.getByTestId('product-name')).toHaveText(
      'Бермуды мужские, Grigio/Verde, OMSA, 46(M), РОССИЯ',
    );
    await expect(page.getByTestId('product-discount')).toContainText('33,33%');
    await expect(page.getByTestId('product-attributes')).toContainText('Бренд');
    await expect(page.getByTestId('product-attributes')).toContainText('OMSA');
    await expect(page.getByTestId('main-image')).toBeVisible();

    await page.getByRole('link', { name: 'К списку товаров' }).click();
    await expect(page).toHaveURL(/price_min=1000/);
  });
});
