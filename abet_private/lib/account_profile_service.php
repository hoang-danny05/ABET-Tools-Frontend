<?php
declare(strict_types=1);

require_once '/home/osburn/abet_private/lib/db.php';

/**
 * @return array<string,string>
 */
function profile_get_for_user(int $userId): array {
    $stmt = db()->prepare(
        'SELECT display_name, department, phone, office_location, bio
         FROM user_profiles
         WHERE user_id = :uid
         LIMIT 1'
    );
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return [
            'display_name' => '',
            'department' => '',
            'phone' => '',
            'office_location' => '',
            'bio' => '',
        ];
    }

    return [
        'display_name' => (string)($row['display_name'] ?? ''),
        'department' => (string)($row['department'] ?? ''),
        'phone' => (string)($row['phone'] ?? ''),
        'office_location' => (string)($row['office_location'] ?? ''),
        'bio' => (string)($row['bio'] ?? ''),
    ];
}

function profile_save_for_user(int $userId, array $data): bool {
    $sql = 'INSERT INTO user_profiles
            (user_id, display_name, department, phone, office_location, bio)
            VALUES
            (:uid, :display_name, :department, :phone, :office_location, :bio)
            ON DUPLICATE KEY UPDATE
              display_name = VALUES(display_name),
              department = VALUES(department),
              phone = VALUES(phone),
              office_location = VALUES(office_location),
              bio = VALUES(bio),
              updated_at = CURRENT_TIMESTAMP';

    $stmt = db()->prepare($sql);
    return $stmt->execute([
        ':uid' => $userId,
        ':display_name' => $data['display_name'] ?? '',
        ':department' => $data['department'] ?? '',
        ':phone' => $data['phone'] ?? '',
        ':office_location' => $data['office_location'] ?? '',
        ':bio' => $data['bio'] ?? '',
    ]);
}
