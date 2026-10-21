import assert from 'node:assert/strict';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { Builder, Browser, By, until } from 'selenium-webdriver';
import chrome from 'selenium-webdriver/chrome.js';

const baseUrl = process.env.AQUARIUM_BASE_URL || 'http://127.0.0.1:8001';
const roleAccounts = process.env.AQUARIUM_E2E_ACCOUNTS_JSON
    ? JSON.parse(process.env.AQUARIUM_E2E_ACCOUNTS_JSON)
    : [];
const outputDir = path.resolve('storage/app/testing/selenium');
const startedAt = new Date();
const results = [];
const options = new chrome.Options();
options.addArguments('--headless=new', '--disable-gpu', '--no-sandbox', '--window-size=1440,1100');
const driver = await new Builder().forBrowser(Browser.CHROME).setChromeOptions(options).build();

async function scenario(name, action) {
    const start = performance.now();
    try {
        await action();
        results.push({ name, status: 'passed', duration_ms: Math.round(performance.now() - start) });
    } catch (error) {
        results.push({ name, status: 'failed', duration_ms: Math.round(performance.now() - start), error: error.message });
        throw error;
    }
}

try {
    await scenario('Inicio público y navegación principal', async () => {
        await driver.get(`${baseUrl}/`);
        await driver.wait(until.elementLocated(By.css('main')), 10000);
        assert.match(await driver.getTitle(), /AQUARIUM/i);
        const navigation = await driver.findElement(By.css('nav[aria-label="Navegación principal"]')).getText();
        for (const label of ['Inicio', 'Catálogo', 'Clínica', 'Información']) assert.ok(navigation.includes(label), `Falta navegación: ${label}`);
        assert.ok((await driver.findElements(By.css('#informacion'))).length > 0, 'No está la sección Información');
    });

    await scenario('Catálogo público y filtro de agua salada', async () => {
        await driver.get(`${baseUrl}/catalogo`);
        await driver.wait(until.elementLocated(By.css('h1')), 10000);
        assert.match(await driver.findElement(By.css('h1')).getText(), /Peces e insumos/i);
        await driver.findElement(By.css('select[name="water_type"]')).sendKeys('salada');
        await driver.findElement(By.css('button[type="submit"]')).click();
        await driver.wait(async () => (await driver.getCurrentUrl()).includes('water_type=salada'), 10000);
        assert.ok(await driver.findElement(By.css('select[name="water_type"]')).getAttribute('value') === 'salada');
    });

    await scenario('La agenda exige autenticación antes de mostrar las citas', async () => {
        await driver.get(`${baseUrl}/citas`);
        await driver.wait(until.urlContains('/acceso'), 10000);
        assert.ok((await driver.findElements(By.css('input[type="password"]'))).length > 0, 'No se mostró el formulario de autenticación');
    });

    for (const account of roleAccounts) {
        await scenario(`Acceso de rol ${account.role} al módulo ${account.path}`, async () => {
            await driver.manage().deleteAllCookies();
            await driver.get(`${baseUrl}/acceso`);
            await driver.findElement(By.css('input[type="email"]')).sendKeys(account.email);
            await driver.findElement(By.css('input[type="password"]')).sendKeys(account.password);
            await driver.findElement(By.css('form button')).click();
            await driver.wait(until.urlContains('/cuenta'), 10000);
            await driver.get(`${baseUrl}${account.path}`);
            await driver.wait(until.elementLocated(By.css('main h1')), 10000);
            const heading = await driver.findElement(By.css('main h1')).getText();
            assert.match(heading, new RegExp(account.heading, 'i'), `El módulo no corresponde al rol ${account.role}: ${heading}`);
        });
    }
} finally {
    await driver.quit();
    await mkdir(outputDir, { recursive: true });
    const report = {
        suite: 'selenium-public-local',
        base_url: baseUrl,
        started_at: startedAt.toISOString(),
        finished_at: new Date().toISOString(),
        status: results.some((result) => result.status === 'failed') ? 'failed' : 'passed',
        scenarios: results,
    };
    const stamp = startedAt.toISOString().replaceAll(':', '').replaceAll('-', '').replaceAll('.', '').slice(0, 15);
    await writeFile(path.join(outputDir, `selenium-${stamp}.json`), `${JSON.stringify(report, null, 2)}\n`);
    await writeFile(path.join(outputDir, 'latest.json'), `${JSON.stringify(report, null, 2)}\n`);
    console.log(JSON.stringify(report, null, 2));
}
