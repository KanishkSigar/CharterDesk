<?php

function startCharterSession(): void
{
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '604800');
    session_name('charterdesk_session');
    session_set_cookie_params([
        'lifetime' => 604800,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || getenv('CHARTERDESK_SECURE_COOKIE') === '1',
    ]);
    session_start();
}

function failRequest(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

function requirePost(): void
{
    // Browser requests from another origin would have to preflight this header.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_CHARTERDESK_REQUEST'] ?? '') !== '1') {
        failRequest(403, 'Open CharterDesk to perform this action.');
    }
}

function fieldText($value): string
{
    return is_string($value) ? trim($value) : '';
}

function participantName($value): string
{
    $name = fieldText($value);
    if ($name === '' || mb_strlen($name) > 100 || !mb_check_encoding($name, 'UTF-8')) {
        failRequest(400, 'Enter a name between 1 and 100 characters.');
    }
    return $name;
}

function memberThreads(): array
{
    startCharterSession();
    $memberships = $_SESSION['memberships'] ?? [];
    session_write_close();
    return $memberships;
}

function rememberMembership(string $uuid, string $seat): void
{
    session_regenerate_id(false);
    $_SESSION['memberships'][$uuid] = $seat;
    session_write_close();
}

function requireParticipant(PDO $pdo, string $uuid, bool $hostOnly = false): array
{
    $seat = memberThreads()[$uuid] ?? null;
    if ($seat !== null && (!$hostOnly || $seat === 'host')) {
        $query = $pdo->prepare(
            'SELECT p.thread_id, p.seat, p.display_name
             FROM thread_participants p JOIN threads t ON t.id = p.thread_id
             WHERE t.thread_uuid = ? AND p.seat = ? AND p.display_name IS NOT NULL'
        );
        $query->execute([$uuid, $seat]);
        $participant = $query->fetch();
        if ($participant) return $participant;
    }
    failRequest(404, 'This negotiation is unavailable to this session.');
}
