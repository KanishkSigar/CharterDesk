<?php
// assist.php - LLM clause assistant (explain / draft / review) via the Claude Messages API.
// Calls Claude over raw HTTPS (PHP cURL). The API key is read from the
// ANTHROPIC_API_KEY environment variable - it is never stored in the repo.
header('Content-Type: application/json');

$apiKey = getenv('ANTHROPIC_API_KEY');
if (!$apiKey) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'ANTHROPIC_API_KEY is not set on the server. Set it before starting PHP (see README).',
    ]);
    exit;
}

$in      = json_decode(file_get_contents('php://input'), true) ?: [];
$mode    = $in['mode']    ?? '';
$input   = trim($in['input']   ?? '');
$context = trim($in['context'] ?? '');   // optional fixture-terms summary

$system = "You are CharterDesk's assistant - an expert maritime chartering broker fluent in "
        . "charter-party practice (GENCON 1994, NYPE 2015), laytime, demurrage/despatch, NOR, "
        . "laycan, FIOST, and standard riders. Be concise, practical and precise, in plain English "
        . "a junior broker can act on. Do not invent facts that were not provided.";

if ($mode === 'explain') {
    if ($input === '') { echo json_encode(['status'=>'error','message'=>'Enter a term or clause to explain.']); exit; }
    $user = "Explain this charter-party term or clause in 3-5 sentences, including why it matters "
          . "during a negotiation:\n\n\"{$input}\"";
} elseif ($mode === 'draft') {
    if ($input === '') { echo json_encode(['status'=>'error','message'=>'Describe the clause you want drafted.']); exit; }
    $user = "Draft a professional charter-party rider/clause for the request below. Return only the "
          . "clause text, ready to paste into a fixture - tight and market-standard.\n\nRequest: {$input}";
    if ($context !== '') $user .= "\n\nDeal context (for consistency):\n{$context}";
} elseif ($mode === 'review') {
    if ($context === '') { echo json_encode(['status'=>'error','message'=>'No offer terms to review yet.']); exit; }
    $user = "Review the following fixture terms for a dry-bulk voyage charter and flag anything unusual, "
          . "risky, internally inconsistent, or off-market. Reply as a short bulleted list (max 6 bullets); "
          . "if nothing stands out, say so plainly.\n\nTerms:\n{$context}";
} else {
    echo json_encode(['status'=>'error','message'=>'Unknown assist mode.']); exit;
}

$payload = [
    'model'      => 'claude-opus-4-8',
    'max_tokens' => 1200,
    'system'     => $system,
    'messages'   => [['role' => 'user', 'content' => $user]],
];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT    => 60,
]);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($resp === false) { echo json_encode(['status'=>'error','message'=>'Request failed: '.$curlErr]); exit; }

$data = json_decode($resp, true);
if ($httpCode !== 200) {
    echo json_encode(['status'=>'error','message'=>($data['error']['message'] ?? "API error {$httpCode}")]);
    exit;
}

// Concatenate text blocks (ignoring any thinking blocks).
$text = '';
foreach (($data['content'] ?? []) as $block) {
    if (($block['type'] ?? '') === 'text') $text .= $block['text'];
}

echo json_encode(['status' => 'success', 'text' => trim($text)]);
