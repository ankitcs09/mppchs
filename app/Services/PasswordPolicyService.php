<?php

namespace App\Services;

use App\Config\AuthPolicy;
use App\Models\UserPasswordHistoryModel;
use CodeIgniter\I18n\Time;

class PasswordPolicyService
{
    private AuthPolicy $config;
    private UserPasswordHistoryModel $historyModel;

    public function __construct(
        ?AuthPolicy $config = null,
        ?UserPasswordHistoryModel $historyModel = null
    ) {
        $this->config = $config ?? config('AuthPolicy');
        $this->historyModel = $historyModel ?? new UserPasswordHistoryModel();
    }

    /**
     * Validate a prospective password against policy rules.
     *
     * @param int         $userId       User identifier (for history lookups).
     * @param string      $password     Plain text password candidate.
     * @param array       $context      Optional hints (username, current_hash, blacklist overrides).
     *
     * @return string[]   List of validation errors. Empty array means the password is acceptable.
     */
    public function validateNewPassword(int $userId, string $password, array $context = []): array
    {
        $errors = [];
        $password = trim($password);

        if ($password === '') {
            return ['Password cannot be empty.'];
        }

        // Length requirement.
        if (mb_strlen($password) < $this->config->passwordMinLength) {
            $errors[] = sprintf(
                'Password must be at least %d characters long.',
                $this->config->passwordMinLength
            );
        }

        // Character classes requirement.
        $classPatterns = [
            'uppercase letters' => '/[A-Z]/u',
            'lowercase letters' => '/[a-z]/u',
            'numbers'           => '/[0-9]/u',
            'symbols'           => '/[^a-zA-Z0-9]/u',
        ];

        $classMatches = [];
        foreach ($classPatterns as $label => $pattern) {
            $classMatches[$label] = preg_match($pattern, $password) === 1;
        }

        $classCount   = (int) array_sum($classMatches);
        $classesNeeded = (int) $this->config->passwordClassesRequired;
        if ($classCount < $classesNeeded) {
            $missing = array_keys(array_filter($classMatches, static fn (bool $matched): bool => ! $matched));

            if ($classesNeeded >= count($classPatterns)) {
                $errors[] = 'Password must include uppercase letters, lowercase letters, numbers, and special symbols.';
            } else {
                $errors[] = sprintf(
                    'Password must include at least %d of the following character types: uppercase letters, lowercase letters, numbers, special symbols. Add: %s.',
                    $classesNeeded,
                    implode(', ', $missing)
                );
            }
        }

        // Avoid reusing username or obvious variants.
        $username = $context['username'] ?? null;
        if ($username && strcasecmp($password, (string) $username) === 0) {
            $errors[] = 'Password must not match your username.';
        }

        // Block common or weak passwords.
        if ($this->config->blockCommonPasswords) {
            $commonList = array_map('strtolower', $this->config->commonPasswordList);
            if (in_array(strtolower($password), $commonList, true)) {
                $errors[] = 'That password is too common. Please choose something harder to guess.';
            }
        }

        // Simple heuristics for keyboard sequences or repeated characters.
        if ($this->looksSequential($password)) {
            $errors[] = 'Avoid using obvious sequences or repeated characters.';
        }

        // Prevent reuse of current or recent passwords.
        $currentHash = $context['current_hash'] ?? null;
        if ($currentHash && password_verify($password, $currentHash)) {
            $errors[] = 'New password must be different from your current password.';
        }

        $historyDepth = max(0, (int) $this->config->passwordHistoryDepth);
        if ($historyDepth > 0) {
            $recentHashes = $this->historyModel->getRecentHashes($userId, $historyDepth);
            foreach ($recentHashes as $oldHash) {
                if (password_verify($password, $oldHash)) {
                    $errors[] = 'You have used a similar password recently. Please choose a new one.';
                    break;
                }
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Record a password change, pruning old history based on policy depth.
     */
    public function recordPasswordChange(int $userId, string $passwordHash): void
    {
        $retain = max(0, (int) $this->config->passwordHistoryDepth);
        $this->historyModel->recordPassword($userId, $passwordHash, $retain);
    }

    /**
     * Helper heuristic for simple incremental sequences or repeated characters.
     */
    private function looksSequential(string $password): bool
    {
        $lower = strtolower($password);

        $sequences = [
            'abcdefghijklmnopqrstuvwxyz',
            'qwertyuiopasdfghjklzxcvbnm',
            '0123456789',
        ];

        foreach ($sequences as $sequence) {
            if (str_contains($sequence, $lower) || str_contains(strrev($sequence), $lower)) {
                return true;
            }
        }

        if (preg_match('/(.)\1{3,}/u', $password)) {
            return true;
        }

        return false;
    }
}
