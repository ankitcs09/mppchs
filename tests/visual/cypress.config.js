const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: process.env.PORTAL_BASE_URL || 'http://localhost/MPPGCLCashless/public',
    supportFile: false,
    video: false,
    screenshotsFolder: 'tests/visual/output',
    downloadsFolder: 'tests/visual/downloads',
    viewportWidth: 1440,
    viewportHeight: 900,
    specPattern: 'tests/visual/cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
  },
});
