# Frontend Asset Notes

## Tooling Overview
- `resources/scss/app.scss` compiles to `public/assets/css/app.css` via `npm run dev` (watch) or `npm run build` (one-off).
- `resources/js/app.js` bundles through esbuild to `public/assets/js/app.js` (ES modules) and now registers jQuery + DataTables from npm.
- Shared helpers live under `resources/js/modules/`; page-specific behaviours sit under `resources/js/pages/`.
- Bootstrap still loads from `public/assets/bootstrap/js/bootstrap.bundle.min.js` in `layouts/default.php`.

## Recent Changes
- DataTables and jQuery moved to npm dependencies; views no longer load CDN/vendor scripts.
- Layout/theme scaffolding refactored: `app/Views/layouts/default.php` and `layouts/auth.php` consume the new SCSS partials (`layouts/_shell.scss`, `_auth.scss`).
- Beneficiary hospitals, admin claims, and cashless summaries now share the `app-panel`, `table-surface`, and stacked mobile table patterns.
- Flash toasts surface via `data-feedback-*` markers; see `resources/js/modules/feedback.js`.
- OTP login/verify flows rely on shared validation + timer modules (`modules/formValidation.js`, `pages/auth/otpVerify.js`).

## Build / Watch Commands
- `npm run dev` – watch SCSS & JS in parallel.
- `npm run build` – generate production bundles (compressed Sass + minified JS).

## Screenshot & Visual Baseline Workflow
1. Run `npm run build` to ensure assets are up to date.
2. Start the PHP server locally (`php spark serve` or XAMPP) and open `/dashboard`, `/admin/claims`, `/hospitals`, `/login`, `/login/otp`.
3. For each theme (`wellcare-classic`, `wellcare-warm`, `freshcare-modern`) toggle the selector and capture:
   - Dashboard (beneficiary)
   - Admin claims list
   - Hospitals directory
   - Auth (password login + OTP verify)
4. Save full-HD (1920×1080) and mobile (375px width) screenshots under `design-assets/` using the naming pattern `{screen}-{theme}.png`.
5. Generate thumbnails with ImageMagick:\
   `magick design-assets/{screen}-{theme}.png -resize 1024x576 -quality 85 design-assets/thumbnail-{screen}-{theme}.png`.

## Automated Visual & Accessibility Baselines
- Cypress scaffold lives in `tests/visual/cypress/e2e/themes.cy.js`; execute with `npx cypress run --config-file tests/visual/cypress.config.js`.
- Percy integration command scaffolded: `npm run visual:percy` runs `percy exec -- cypress run` (requires `PERCY_TOKEN`).
- Axe accessibility checks can be enabled inside the Cypress spec (`cy.injectAxe(); cy.checkA11y();`) once `cypress-axe` is added.

## Migration TODOs
1. Move remaining legacy inline scripts (legacy hospital request, admin dashboards) into modules under `resources/js/pages/`.
2. Convert older Sass `@import` directives to `@use`/`@forward` before Dart Sass 3.0.
3. Populate the Cypress test with end-to-end flows and wire Percy into CI.
4. Flesh out theme-aware variants for components (breadcrumbs, alerts, badges) as SCSS partials.
5. Integrate axe-core checks during Cypress runs to maintain WCAG AA compliance.
