<?php
declare(strict_types=1);

function v_trim(?string $value, int $maxLen): string {
    $v = trim((string)$value);
    if (mb_strlen($v) > $maxLen) {
        $v = mb_substr($v, 0, $maxLen);
    }
    return $v;
}

/**
 * @return array{0: array<string,string>, 1: array<string,string>}
 */
function validate_profile_input(array $post): array {
    $data = [
        'display_name'    => v_trim($post['display_name'] ?? '', 120),
        'department'      => v_trim($post['department'] ?? '', 120),
        'phone'           => v_trim($post['phone'] ?? '', 30),
        'office_location' => v_trim($post['office_location'] ?? '', 120),
        'bio'             => v_trim($post['bio'] ?? '', 500),
    ];

    $errors = [];

    if ($data['display_name'] !== '' && mb_strlen($data['display_name']) < 2) {
        $errors['display_name'] = 'Display name is too short.';
    }

    if ($data['phone'] !== '' && !preg_match('/^[0-9+\-\s().]{7,30}$/', $data['phone'])) {
        $errors['phone'] = 'Phone format is invalid.';
    }

    return [$data, $errors];
}