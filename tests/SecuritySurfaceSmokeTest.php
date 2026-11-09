<?php

/**
 * Static guard against adding autonomous financial or order-status actions.
 * Runs in CI only; this is not a merchant-facing CLI command.
 */

$runtime_root = dirname(__DIR__) . '/includes';
$forbidden_patterns = array(
    '/\b(?:wc_create_refund|wc_refund_payment|wc_cancel_order|wc_reduce_stock_levels|wc_maybe_reduce_stock_levels)\s*\(/i',
    '/->\s*(?:payment_complete|refund|cancel_order|set_status|update_status)\s*\(/i',
);
$violations = array();
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runtime_root, FilesystemIterator::SKIP_DOTS));
foreach ($files as $file) {
    if (!$file->isFile() || 'php' !== strtolower($file->getExtension())) {
        continue;
    }
    $source = file_get_contents($file->getPathname());
    if (false === $source) {
        fwrite(STDERR, 'Could not read runtime source: ' . $file->getPathname() . "\n");
        exit(1);
    }
    foreach ($forbidden_patterns as $pattern) {
        if (preg_match($pattern, $source)) {
            $violations[] = $file->getPathname();
            break;
        }
    }
}
if ($violations) {
    fwrite(STDERR, "Found an unapproved financial/order-status action in: " . implode(', ', $violations) . "\n");
    exit(1);
}
fwrite(STDOUT, "No autonomous payment, refund, cancellation, stock-reduction, or order-status calls exist in plugin runtime.\n");
