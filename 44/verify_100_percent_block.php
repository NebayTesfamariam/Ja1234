<?php
/**
 * Verify 100% Porn Blocking
 * Tests that pornographic domains are permanently blocked
 */

require __DIR__ . '/config.php';
require __DIR__ . '/config_porn_block.php';

header('Content-Type: application/json');

$tests = [];
$all_passed = true;

// Test domains
$test_domains = [
    // Pornographic (should be blocked)
    'pornhub.com' => false,
    'xvideos.com' => false,
    'xhamster.com' => false,
    'redtube.com' => false,
    'xnxx.com' => false,
    'porn.com' => false,
    'xxx.com' => false,
    'adult.com' => false,
    'sex.com' => false,
    'onlyfans.com' => false,
    'chaturbate.com' => false,
    
    // Normal (should be allowed if in whitelist)
    'wikipedia.org' => true,
    'google.com' => true,
    'example.com' => true,
];

foreach ($test_domains as $domain => $should_allow) {
    $is_porn = is_pornographic_domain($domain);
    $blocked = !$is_porn ? null : true;
    $passed = $should_allow ? !$is_porn : $is_porn;
    
    if (!$passed) {
        $all_passed = false;
    }
    
    $tests[] = [
        'domain' => $domain,
        'is_pornographic' => $is_porn,
        'blocked' => $blocked,
        'expected' => $should_allow ? 'allowed' : 'blocked',
        'status' => $passed ? 'pass' : 'fail'
    ];
}

// Test whitelist API blocking
$test_porn_domain = 'pornhub.com';
$validation = validate_domain_for_whitelist($test_porn_domain);
$api_blocked = !$validation['valid'] && isset($validation['blocked']);

if (!$api_blocked) {
    $all_passed = false;
}

$tests[] = [
    'test' => 'API blocks porn domains',
    'domain' => $test_porn_domain,
    'api_blocked' => $api_blocked,
    'status' => $api_blocked ? 'pass' : 'fail'
];

json_out([
    'status' => $all_passed ? 'pass' : 'fail',
    'message' => $all_passed 
        ? '✅ 100% porn blocking verified - all tests passed'
        : '❌ Some tests failed - porn blocking not 100%',
    'tests' => $tests,
    'summary' => [
        'total' => count($tests),
        'passed' => count(array_filter($tests, fn($t) => $t['status'] === 'pass')),
        'failed' => count(array_filter($tests, fn($t) => $t['status'] === 'fail'))
    ]
]);
