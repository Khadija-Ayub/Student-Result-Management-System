<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}// IMPORTANT for login + flash messages

// ─────────────────────────────────────────
// Helper Functions
// ─────────────────────────────────────────

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin($role = null) {
    if (!isLoggedIn()) {
        redirect('/student-result-system/index.php?error=Please+login+first');
    }

    if ($role && isset($_SESSION['role']) && $_SESSION['role'] !== $role) {
        redirect('/student-result-system/index.php?error=Access+denied');
    }
}

// ─────────────────────────────────────────
// Grade System
// ─────────────────────────────────────────

function getGrade($percentage) {
    if ($percentage >= 90) return ['grade' => 'A+', 'label' => 'Outstanding', 'color' => '#059669'];
    if ($percentage >= 80) return ['grade' => 'A',  'label' => 'Excellent',   'color' => '#10b981'];
    if ($percentage >= 70) return ['grade' => 'B',  'label' => 'Good',        'color' => '#3b82f6'];
    if ($percentage >= 60) return ['grade' => 'C',  'label' => 'Average',     'color' => '#f59e0b'];
    if ($percentage >= 50) return ['grade' => 'D',  'label' => 'Pass',        'color' => '#f97316'];
    return ['grade' => 'F',  'label' => 'Fail',        'color' => '#ef4444'];
}

function getStatusBadge($percentage) {
    $g = getGrade($percentage);

    return '<span class="badge" 
        style="background:' . $g['color'] . '20;
        color:' . $g['color'] . ';
        border:1px solid ' . $g['color'] . '40;
        padding:4px 8px;
        border-radius:6px;">
        ' . $g['grade'] . ' — ' . $g['label'] . '
    </span>';
}

// ─────────────────────────────────────────
// Flash Messages
// ─────────────────────────────────────────

function flashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function showFlash() {
    if (isset($_SESSION['flash'])) {

        $f = $_SESSION['flash'];

        $colors = [
            'success' => [
                'bg' => '#ecfdf5',
                'border' => '#6ee7b7',
                'text' => '#065f46'
            ],
            'error' => [
                'bg' => '#fef2f2',
                'border' => '#fca5a5',
                'text' => '#991b1b'
            ],
            'info' => [
                'bg' => '#eff6ff',
                'border' => '#93c5fd',
                'text' => '#1e40af'
            ],
        ];

        $c = $colors[$f['type']] ?? $colors['info'];

        echo '<div style="
            background:' . $c['bg'] . ';
            border:1px solid ' . $c['border'] . ';
            color:' . $c['text'] . ';
            padding:10px;
            margin-bottom:15px;
            border-radius:6px;
            font-weight:500;
        ">
            ' . htmlspecialchars($f['message']) . '
        </div>';

        unset($_SESSION['flash']);
    }
}
?>