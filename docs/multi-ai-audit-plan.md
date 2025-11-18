# Full Multi-AI Code Audit Plan

## Scope

Run all audits on the entire CodeIgniter 4 codebase, covering:

- `app/`
- `public/`
- `resources/`
- `tests/`
- `writable/`
- `builds/`

Critical focus areas: services (`app/Services`), controllers, models, libraries, helpers, filters, beneficiary onboarding, Aadhaar/employee flows, OTP/login, hospital directory logic, dependent data, exporters (Excel/PDF), file uploads, session management, and API endpoints.

## Objectives

Identify issues in:

- Architecture & separation of concerns
- Security (SQLi, XSS, CSRF, auth/session flaws, sensitive data leakage, broken access controls, OTP/file upload risks)
- Database schema & performance (N+1 queries, missing indexes, inefficient loops, poor caching)
- Maintainability & code quality (dead code, duplication, hardcoded values, poor naming, untyped arrays, missing DTOs/interfaces)
- Standards compliance (PSR-12, CI4 structure, naming consistency)
- Exporter security (PDF/Excel injection, binary sanitization)

Deliverables per tool: summary report, security findings, architecture flaws, performance bottlenecks, code smells, severity ratings, recommended fixes, refactor suggestions, maintainability grade, duplicated code blocks.

## Tool Matrix & Instructions

1. **CodeRabbit AI**
   - Prompts: “Review complete project structure and give architecture-level issues.” “Detect deep security flaws.” “Suggest CodeIgniter 4 best-practice improvements.”
2. **SonarCloud**
   - Collect code smells, duplications, maintainability rating, security hotspots, cognitive complexity.
3. **Snyk Code**
   - Look for SQL/command injection, path traversal, deserialization flaws, sensitive-data handling.
4. **DeepSource**
   - Catch anti-patterns, complex functions, type issues, inefficient code blocks, bad DB patterns.
5. **Codacy**
   - Static analysis, lint warnings, best-practice alignment.
6. **GitHub Actions (PHPStan + PHPMD)**
   - PHPStan level 8.
   - PHPMD rulesets: `cleancode`, `codesize`, `controversial`, `design`.

## Output Expectations

Each engine should produce:

- Security/high-risk issue list (Critical/High/Medium/Low).
- Architecture/performance findings.
- Maintainability/code smell catalog with remediation guidance.
- Coverage of duplicated logic and naming inconsistencies.
- Actionable fixes or refactor plans for oversized services, importers, and dashboards.

Use this document when opening multi-tool audit PRs so all reviewers/AIs follow the same checklist.
