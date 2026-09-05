import { test, expect } from '@playwright/test';

test.describe('PO Tracking Module', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
    });

    test('shows PO tracking page', async ({ page }) => {
        await expect(page.locator('h1')).toContainText('PO Tracking');
    });

    test('displays lead time metrics', async ({ page }) => {
        await page.goto('/modules/ksf_FA_PurchaseOrderTracking/');
        const leadTimeColumn = page.locator('text=Lead Time');
        await expect(leadTimeColumn).toBeVisible();
    });
});