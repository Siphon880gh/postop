<?php
declare(strict_types=1);

const ACCOUNT_FILE_VERSION = 4;
const ACCOUNT_ROLES = ['admin', 'contributor', 'add-only', 'viewer'];

function account_role(array $account): string {
    $role = (string)($account['role'] ?? '');
    return in_array($role, ACCOUNT_ROLES, true) ? $role : 'viewer';
}

function account_can(array $account, string $op): bool {
    if ($op === 'account_save') return true;
    $role = account_role($account);
    if ($role === 'admin') return true;
    if ($role === 'viewer') return false;
    if ($op === 'patient_import') return false;
    if ($role === 'contributor') return true;
    return $role === 'add-only' && in_array($op, ['note_add', 'upload', 'placeholder'], true);
}

function account_can_edit(): bool {
    global $account;
    return is_array($account) && in_array(account_role($account), ['admin', 'contributor'], true);
}

function account_can_add(): bool {
    global $account;
    return is_array($account) && in_array(account_role($account), ['admin', 'contributor', 'add-only'], true);
}

function account_can_import(): bool {
    global $account;
    return is_array($account) && account_role($account) === 'admin';
}

function account_catalog(string $hash): array {
    $accounts = [
        ['display_name' => 'Weng', 'username' => 'admin', 'password_hash' => $hash, 'role' => 'admin', 'patient_ids' => ['sample-patient']],
    ];
    $privateFile = __DIR__ . '/.env.accounts.php';
    if (!is_file($privateFile)) return $accounts;
    $privateAccounts = require $privateFile;
    if (!is_array($privateAccounts)) fail('The private account configuration is invalid.', 500);
    return array_merge($accounts, $privateAccounts);
}

function seed_account(string $accountFile): void {
    if (is_file($accountFile)) return;
    $hash = password_hash('password', PASSWORD_DEFAULT);
    atomic_write($accountFile, ['version' => ACCOUNT_FILE_VERSION, 'accounts' => account_catalog($hash)]);
}

function ensure_accounts(string $accountFile): void {
    if (!is_file($accountFile)) return;
    $raw = @file_get_contents($accountFile);
    $decoded = $raw === false ? null : json_decode($raw, true);
    if (!is_array($decoded)) return;
    $wrapped = isset($decoded['accounts']) && is_array($decoded['accounts']);
    $list = $wrapped ? $decoded['accounts'] : [$decoded];
    $changed = !$wrapped || (int)($decoded['version'] ?? 0) < ACCOUNT_FILE_VERSION;
    $seen = [];
    $configured = [];
    foreach (account_catalog('') as $candidate) {
        if (!is_array($candidate)) continue;
        $username = strtolower(trim((string)($candidate['username'] ?? '')));
        if ($username !== '') $configured[$username] = $candidate;
    }
    foreach ($list as &$existing) {
        if (!is_array($existing)) continue;
        $username = strtolower(trim((string)($existing['username'] ?? '')));
        if ($username !== '') $seen[$username] = true;
        $role = (string)($existing['role'] ?? '');
        if (!in_array($role, ACCOUNT_ROLES, true)) {
            $configuredRole = (string)($configured[$username]['role'] ?? '');
            $existing['role'] = in_array($configuredRole, ACCOUNT_ROLES, true) ? $configuredRole : 'viewer';
            $changed = true;
        }
    }
    unset($existing);
    foreach ($configured as $username => $candidate) {
        if (!empty($seen[$username])) continue;
        if (empty($candidate['password_hash'])) $candidate['password_hash'] = password_hash('password', PASSWORD_DEFAULT);
        $list[] = $candidate;
        $changed = true;
    }
    if (!$changed) return;
    $list = array_values(array_filter($list, 'is_array'));
    atomic_write($accountFile, ['version' => ACCOUNT_FILE_VERSION, 'accounts' => $list]);
}

function read_account_file(): array {
    global $accountFile;
    $raw = @file_get_contents($accountFile);
    $decoded = $raw === false ? null : json_decode($raw, true);
    if (!is_array($decoded)) fail('The account configuration is unavailable or invalid.', 500);
    return $decoded;
}

function account_entry($account): ?array {
    if (!is_array($account)) return null;
    $hash = $account['password_hash'] ?? null;
    if (!is_string($hash) || $hash === '') return null;
    $display = trim((string)($account['display_name'] ?? ''));
    $username = trim((string)($account['username'] ?? ''));
    if ($display === '' || $username === '') return null;
    $patientIds = [];
    $scoped = isset($account['patient_ids']) && is_array($account['patient_ids']);
    if ($scoped) foreach ($account['patient_ids'] as $id) if (is_string($id) && safe_id($id)) $patientIds[] = $id;
    $role = (string)($account['role'] ?? '');
    if (!in_array($role, ACCOUNT_ROLES, true)) $role = 'viewer';
    return ['display_name' => $display, 'username' => $username, 'password_hash' => $hash, 'role' => $role, 'patient_ids' => $patientIds, 'patient_scope' => $scoped];
}

function load_accounts(): array {
    $decoded = read_account_file();
    $candidates = isset($decoded['accounts']) && is_array($decoded['accounts']) ? $decoded['accounts'] : [$decoded];
    $accounts = [];
    foreach ($candidates as $candidate) {
        $entry = account_entry($candidate);
        if ($entry) $accounts[] = $entry;
    }
    if (!$accounts) fail('The account configuration is unavailable or invalid.', 500);
    return $accounts;
}

function account_can_access(array $account, string $patientId): bool {
    if (empty($account['patient_scope'])) return true;
    return in_array($patientId, $account['patient_ids'], true);
}

function save_account(array $account): void {
    global $accountFile;
    $decoded = read_account_file();
    if (isset($decoded['accounts']) && is_array($decoded['accounts'])) {
        foreach ($decoded['accounts'] as &$existing) {
            if (!is_array($existing) || ($existing['username'] ?? '') !== $account['username']) continue;
            $existing['display_name'] = $account['display_name'];
            $existing['password_hash'] = $account['password_hash'];
            if (array_key_exists('patient_ids', $account)) $existing['patient_ids'] = $account['patient_ids'];
            if (isset($account['role']) && in_array($account['role'], ACCOUNT_ROLES, true)) $existing['role'] = $account['role'];
            unset($existing);
            atomic_write($accountFile, $decoded);
            return;
        }
        fail('The account configuration is unavailable or invalid.', 500);
    }
    atomic_write($accountFile, $account);
}
