const themes = ['wellcare-classic', 'wellcare-warm', 'freshcare-modern'];
const pages = [
  { path: '/dashboard', label: 'beneficiary dashboard' },
  { path: '/admin/claims', label: 'admin claims list' },
  { path: '/hospitals', label: 'hospitals directory' },
  { path: '/login', label: 'password login' },
  { path: '/login/otp', label: 'OTP login' },
];

const setTheme = (theme) => {
  cy.document().then((doc) => {
    doc.body.dataset.theme = theme;
    doc.body.classList.remove('theme-dark');
    doc.documentElement.setAttribute('data-bs-theme', 'light');
  });
};

const toggleDark = () => {
  cy.document().then((doc) => {
    doc.body.classList.add('theme-dark');
    doc.documentElement.setAttribute('data-bs-theme', 'dark');
  });
};

describe('Theme smoke snapshots', () => {
  themes.forEach((theme) => {
    pages.forEach(({ path, label }) => {
      const slug = label.replace(/\s+/g, '-');

      it(`${label} – ${theme} (light)`, () => {
        cy.visit(path);
        setTheme(theme);
        cy.wait(500);
        cy.screenshot(`${slug}-${theme}-light`, { capture: 'viewport' });
      });

      it(`${label} – ${theme} (dark)`, () => {
        cy.visit(path);
        setTheme(theme);
        toggleDark();
        cy.wait(500);
        cy.screenshot(`${slug}-${theme}-dark`, { capture: 'viewport' });
      });
    });
  });
});
