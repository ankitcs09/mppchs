# Cashless Enrollment v2 – Data Model Draft

> Status: draft for implementation. This captures the schema additions we will
> build alongside the existing database. Legacy tables (`beneficiaries` /
> `dependents`) remain untouched.

## Core Tables

### 1. `beneficiaries_v2`
One row per enrollment profile. Fields mirror the Zoho form’s beneficiary pages.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT PK | Auto increment |
| `legacy_beneficiary_id` | BIGINT NULL | Crosswalk to old table (optional) |
| `reference_number` | VARCHAR(30) UNIQUE | System-generated ref `BEN{batch}{seq}-{legacy}` |
| `legacy_reference` | VARCHAR(30) UNIQUE | Original Zoho submission id |
| `plan_option_id` | SMALLINT FK `plan_options` | Option-1 / Option-2 / Option-3 |
| `category_id` | SMALLINT FK `beneficiary_categories` | Pensioner / Family / NPS |
| `first_name` | VARCHAR(120) | |
| `middle_name` | VARCHAR(120) NULL | Optional |
| `last_name` | VARCHAR(120) | |
| `gender` | ENUM(`male`,`female`,`transgender`) | |
| `date_of_birth` | DATE | |
| `retirement_or_death_date` | DATE | Retirement date or date of death |
| `deceased_employee_name` | VARCHAR(180) NULL | Visible for Family Pensioner |
| `rao_id` | SMALLINT FK `regional_account_offices` NULL | Dropdown selection |
| `rao_other` | VARCHAR(180) NULL | Manual value when “Other” |
| `retirement_office_id` | SMALLINT FK `retirement_offices` NULL | |
| `retirement_office_other` | VARCHAR(180) NULL | |
| `designation_id` | SMALLINT FK `designations` NULL | |
| `designation_other` | VARCHAR(180) NULL | |
| `correspondence_address` | TEXT | Postal address |
| `city` | VARCHAR(120) | |
| `state_id` | SMALLINT FK `states` | |
| `postal_code` | VARCHAR(10) | |
| `ppo_number_enc` | VARBINARY | AES-encrypted PPO |
| `ppo_number_masked` | VARCHAR(32) | e.g. `XXXXXX1234` |
| `pran_number_enc` | VARBINARY NULL | NPS only |
| `pran_number_masked` | VARCHAR(32) NULL | |
| `gpf_number_enc` | VARBINARY NULL | Optional |
| `gpf_number_masked` | VARCHAR(32) NULL | |
| `bank_source_id` | SMALLINT FK `banks` NULL | Source bank |
| `bank_source_other` | VARCHAR(180) NULL | |
| `bank_servicing_id` | SMALLINT FK `banks` NULL | Credit bank (hidden for NPS) |
| `bank_servicing_other` | VARCHAR(180) NULL | |
| `bank_account_enc` | VARBINARY | Encrypted account number |
| `bank_account_masked` | VARCHAR(32) | `XXXX XXXX 1234` |
| `aadhaar_enc` | VARBINARY | Encrypted beneficiary Aadhaar |
| `aadhaar_masked` | VARCHAR(20) | `XXXX XXXX 1234` |
| `pan_enc` | VARBINARY NULL | Optional |
| `pan_masked` | VARCHAR(20) NULL | |
| `primary_mobile_enc` | VARBINARY | Encrypted mobile for OTP |
| `primary_mobile_masked` | VARCHAR(20) | |
| `primary_mobile_hash` | CHAR(64) | SHA-256 hash of canonical (10-digit) primary mobile |
| `alternate_mobile_enc` | VARBINARY NULL | |
| `alternate_mobile_masked` | VARCHAR(20) NULL | |
| `email` | VARCHAR(150) NULL | Lower-risk PII (stored plaintext) |
| `blood_group_id` | SMALLINT FK `blood_groups` NULL | |
| `samagra_enc` | VARBINARY NULL | Samagra ID (if provided) |
| `samagra_masked` | VARCHAR(20) NULL | |
| `terms_accepted_at` | DATETIME NULL | |
| `otp_verified_at` | DATETIME NULL | |
| `otp_reference` | VARCHAR(40) NULL | OTP transaction id |
| `submission_source` | VARCHAR(40) NULL | `import`, `self-service`, etc. |
| `version` | INT DEFAULT 1 | Increment on each edit |
| `pending_review` | TINYINT(1) DEFAULT 0 | For admin workflows |
| `created_by` | BIGINT NULL | |
| `created_at` | DATETIME DEFAULT CURRENT_TIMESTAMP | |
| `updated_by` | BIGINT NULL | |
| `updated_at` | DATETIME NULL | |

> **Note**: encryption uses AES-256; keys will be stored in a secure store
> (environment/KMS). Hash columns can be added if duplicate detection is needed.

### 2. `beneficiary_dependents_v2`
All spouse/children/parents captured here. Mirrors Zoho dependent rules.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT PK | |
| `beneficiary_id` | BIGINT FK `beneficiaries_v2` | |
| `relationship` | ENUM(`spouse`,`child`,`father`,`mother`,`other`) | |
| `dependant_order` | TINYINT NULL | 1,2,3… (for children; null otherwise) |
| `twin_group` | TINYINT NULL | Same value for twins (e.g. 2) |
| `is_alive` | ENUM(`alive`,`not_alive`,`not_applicable`) | |
| `is_health_dependant` | ENUM(`yes`,`no`,`not_applicable`) | |
| `first_name` | VARCHAR(120) | |
| `gender` | ENUM(`male`,`female`,`transgender`) | |
| `blood_group_id` | SMALLINT FK `blood_groups` NULL | |
| `date_of_birth` | DATE NULL | |
| `aadhaar_enc` | VARBINARY NULL | Encrypted Aadhaar |
| `aadhaar_masked` | VARCHAR(20) NULL | |
| `created_at` | DATETIME DEFAULT CURRENT_TIMESTAMP | |
| `created_by` | BIGINT NULL | |
| `updated_at` | DATETIME NULL | |
| `updated_by` | BIGINT NULL | |

### 3. `beneficiary_change_logs`
Stores every submission/edit with unique change reference + diff.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT PK | |
| `beneficiary_id` | BIGINT FK `beneficiaries_v2` | |
| `change_reference` | VARCHAR(30) UNIQUE | e.g. `CHF-20251029-0045` |
| `change_type` | ENUM(`create`,`update`) | |
| `summary` | VARCHAR(255) | Human-readable summary |
| `diff_json` | JSON | `{ field: { old: "", new: "" }, ... }` |
| `changed_by` | BIGINT NULL | |
| `changed_at` | DATETIME DEFAULT CURRENT_TIMESTAMP | |
| `previous_version` | INT NULL | |
| `new_version` | INT NULL | |
| `review_status` | ENUM(`pending`,`approved`,`rejected`) DEFAULT `pending` | Optional admin workflow |

> Change service will populate `summary` + `diff_json` after comparing old vs new payloads.

## Lookup Tables
Static reference data (id, code, label, `is_active`, `sort_order`):

* `plan_options`
* `beneficiary_categories`
* `regional_account_offices`
* `retirement_offices`
* `designations`
* `banks`
* `blood_groups`
* `states` (reuse existing master if present)

## Mandatory vs Optional (summary)
* Plan option, category, name/surname, gender, DOB, retirement/death date, RAO/office/designation (or manual), address/city/state/pin, PPO (unless NPS), PRAN (only NPS), bank source/servicing + account, primary mobile, Aadhaar, spouse/child/parent data per Zoho rules, terms checkbox, OTP verification – **required**.
* Middle name, PAN, GPF, alternate mobile, email, blood group, Samagra – **optional**.

## PII Handling
* Encrypted columns (`*_enc`) for Aadhaar, PAN, PRAN, bank account, PPO, GPF, Samagra, mobile numbers.
* Masked columns (`*_masked`) for safe display (“XXXXXXXX1234”). Users re-enter full value when editing; form never shows plaintext by default.
* Access to decrypted values restricted; audit entries logged in `beneficiary_change_logs`.

## Implementation Notes
1. Run `php spark migrate [-n App]` to create the v2 schema alongside legacy tables.
2. Seed lookup masters with `php spark db:seed EnrollmentV2LookupSeeder`. Canonical master data and synonym maps live in `app/Config/EnrollmentV2Masters.php`.
3. Encrypt sensitive columns before UI work: configure `encryption.key` in `.env`, run `php spark enrollment:encrypt-existing` once to backfill, and rely on the importer’s built-in encryption for new loads.
4. Ingest Zoho exports through `php spark enrollment:import [file] [--batch=ID] [--dry-run]` which maps beneficiaries and dependents while logging the change set. Re-running the command for the same CSV safely updates rows (matches by `legacy_reference`).
5. Build the new MVC stack on these tables; legacy controllers/views stay in place until cutover.
6. After validation, update routes and retire the old tables as part of the final switchover plan.



