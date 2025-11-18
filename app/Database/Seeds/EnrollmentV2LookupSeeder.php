<?php

namespace App\Database\Seeds;

use App\Config\EnrollmentV2Masters;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class EnrollmentV2LookupSeeder extends Seeder
{
    private array $masters;

    public function run()
    {
        $this->masters = config(EnrollmentV2Masters::class)->data ?? [];
        $timestamp = Time::now('UTC')->toDateTimeString();

        $this->seedPlanOptions($timestamp);
        $this->seedBeneficiaryCategories($timestamp);
        $this->seedRegionalAccountOffices($timestamp);
        $this->seedRetirementOffices($timestamp);
        $this->seedDesignations($timestamp);
        $this->seedBanks($timestamp);
        $this->seedBloodGroups();
        $this->seedStates();
    }

    private function seedPlanOptions(string $timestamp): void
    {
        $entries = $this->masters['planOptions'] ?? [];
        $payload = [];
        foreach ($entries as $index => $entry) {
            $payload[] = [
                'id' => $entry['id'],
                'code' => $entry['code'],
                'label' => $entry['label'],
                'description' => null,
                'coverage_limit' => null,
                'ward_limit' => null,
                'is_active' => 1,
                'sort_order' => $index + 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->insertBatch('plan_options', $payload);
    }

    private function seedBeneficiaryCategories(string $timestamp): void
    {
        $entries = $this->masters['beneficiaryCategories'] ?? [];
        $payload = [];
        foreach ($entries as $index => $entry) {
            $payload[] = [
                'id' => $entry['id'],
                'code' => $entry['code'],
                'label' => $entry['label'],
                'description' => null,
                'is_active' => 1,
                'sort_order' => $index + 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->insertBatch('beneficiary_categories', $payload);
    }

    private function seedRegionalAccountOffices(string $timestamp): void
    {
        $entries = $this->masters['raos'] ?? [];
        $payload = [];
        foreach ($entries as $index => $entry) {
            $payload[] = [
                'id' => $entry['id'],
                'code' => $entry['code'],
                'name' => $entry['label'],
                'description' => null,
                'is_active' => 1,
                'sort_order' => $index + 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->insertBatch('regional_account_offices', $payload);
    }

    private function seedRetirementOffices(string $timestamp): void
    {
        $entries = $this->masters['retirementOffices'] ?? [];
        $payload = [];
        foreach ($entries as $index => $entry) {
            $payload[] = [
                'id' => $entry['id'],
                'code' => $entry['code'],
                'name' => $entry['label'],
                'description' => null,
                'is_active' => 1,
                'sort_order' => $index + 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->insertBatch('retirement_offices', $payload);
    }

    private function seedDesignations(string $timestamp): void
    {
        $entries = $this->masters['designations'] ?? [];
        $payload = [];
        foreach ($entries as $index => $entry) {
            $payload[] = [
                'id' => $entry['id'],
                'code' => $entry['code'],
                'title' => $entry['label'],
                'description' => null,
                'is_active' => 1,
                'sort_order' => $index + 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->insertBatch('designations_ref', $payload, 500);
    }

    private function seedBanks(string $timestamp): void
    {
        $entries = $this->masters['banks'] ?? [];
        $payload = [];
        foreach ($entries as $index => $entry) {
            $payload[] = [
                'id' => $entry['id'],
                'code' => $entry['code'],
                'name' => $entry['label'],
                'branch' => null,
                'is_active' => 1,
                'sort_order' => $index + 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $this->insertBatch('banks_ref', $payload);
    }

    private function seedBloodGroups(): void
    {
        $entries = $this->masters['bloodGroups'] ?? [];
        $payload = [];
        foreach ($entries as $index => $entry) {
            $payload[] = [
                'id' => $entry['id'],
                'label' => $entry['label'],
                'is_active' => 1,
                'sort_order' => $index + 1,
            ];
        }

        $this->insertBatch('blood_groups_ref', $payload);
    }

    private function seedStates(): void
    {
        $entries = $this->masters['states'] ?? [];
        $payload = [];
        foreach ($entries as $entry) {
            $payload[] = [
                'state_id' => $entry['id'],
                'state_name' => strtoupper($entry['label']),
                'allow_unrestricted_cities' => (int) ($entry['flag'] ?? 0),
            ];
        }

        if (! empty($payload)) {
            $builder = $this->db->table('states')->ignore(true);
            foreach (array_chunk($payload, 200) as $chunk) {
                $builder->insertBatch($chunk);
            }
        }
    }

    private function insertBatch(string $table, array $rows, int $chunkSize = 200): void
    {
        if (empty($rows)) {
            return;
        }

        $builder = $this->db->table($table)->ignore(true);
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $builder->insertBatch($chunk);
        }
    }
}
