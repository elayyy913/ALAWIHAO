<?php
function profilePictureUrl($storedPath)
{
    $filename = basename(str_replace('\\', '/', (string)$storedPath));
    if (!preg_match('/^profile_[A-Za-z0-9_-]+\.(jpg|png|webp)$/i', $filename)) {
        return '';
    }

    $appRoot = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (basename($appRoot) === 'admin') {
        $appRoot = dirname($appRoot);
    }

    return $appRoot . '/admin/uploads/profile/' . rawurlencode($filename);
}

function renderProfileLink($storedPath, $name, $settingsUrl)
{
    $name = trim((string)$name);
    $label = $name !== '' ? $name : 'Profile';
    $imageUrl = profilePictureUrl($storedPath);

    echo '<a class="profile-link" href="' . htmlspecialchars($settingsUrl, ENT_QUOTES, 'UTF-8')
        . '" aria-label="Open profile settings for ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">';
    if ($imageUrl !== '') {
        echo '<img class="profile-avatar" src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8')
            . '" alt="" width="40" height="40">';
    } else {
        $initial = strtoupper(substr($label, 0, 1));
        echo '<span class="profile-avatar profile-avatar-fallback" aria-hidden="true">'
            . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</span>';
    }
    echo '<span class="profile-link-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
}
?>
