<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment;

use Saso\Application\Enrichment\Step\AiVisionStepInterface;
use Saso\Application\Enrichment\Step\MergeStep;

/**
 * Orchestrates the iterative AI extraction loop for the auto-register mode.
 *
 * On each pass we compute which target fields are still empty and either run
 * the full AI vision pass (first attempt) or a schema-subset re-prompt for
 * just the gaps (subsequent attempts). The loop exits when every target is
 * filled, when the AI returns an empty payload (provider misconfigured or
 * already exhausted), or when we hit the hard attempt cap.
 *
 * Fields populated by barcode lookups (`jan_code`, `isbn`) are removed from
 * the missing list permanently — barcode data is trusted over AI guesses.
 */
final class IterativeAiResolver
{
    /**
     * Fields the auto-register flow tries to fill before promoting a draft.
     * Order is the priority surface area for the retry prompts.
     */
    public const TARGET_KEYS = [
        'item_name',
        'description',
        'category_hint',
        'jan_code',
        'isbn',
    ];

    private const BARCODE_KEYS = ['jan_code', 'isbn'];

    public function __construct(
        private readonly AiVisionStepInterface $aiVision,
        private readonly MergeStep $merge,
        private readonly int $maxAttempts = 3,
    ) {
    }

    /**
     * @param array<string, mixed> $base already-populated fields (e.g. ISBN/JAN lookup output)
     * @param list<string> $userProtected keys the user supplied and that must not be overwritten
     *
     * @return array<string, mixed>
     */
    public function run(
        array $base,
        string $imagePath,
        ?string $barcodeHint,
        array $userProtected = [],
    ): array {
        $result = $base;
        $maxAttempts = max(1, $this->maxAttempts);

        $barcodeLocked = [];
        foreach (self::BARCODE_KEYS as $key) {
            if ($this->isFilledValue($result[$key] ?? null)) {
                $barcodeLocked[] = $key;
            }
        }

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $missing = $this->missingFields($result, $barcodeLocked);
            if ($missing === []) {
                break;
            }

            $overlay = $attempt === 1
                ? $this->aiVision->run($imagePath, $barcodeHint, $result)
                : $this->aiVision->runForFields($imagePath, $barcodeHint, $result, $missing);

            if ($overlay === []) {
                break;
            }

            foreach ($barcodeLocked as $lockedKey) {
                unset($overlay[$lockedKey]);
            }

            $result = $this->merge->merge($result, $overlay, $userProtected);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @param list<string> $barcodeLocked
     *
     * @return list<string>
     */
    private function missingFields(array $result, array $barcodeLocked): array
    {
        $missing = [];
        foreach (self::TARGET_KEYS as $key) {
            if (in_array($key, $barcodeLocked, true)) {
                continue;
            }
            if ($this->isMissingForRetry($result, $key)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /**
     * A key counts as still-missing for retry purposes only when nothing — not
     * even an explicit null verdict — has been recorded for it yet, OR when
     * the recorded value is an empty string / empty array (i.e. an AI answer
     * that arrived but contained no signal). Explicit `null` is treated as
     * the AI's verdict "no data" and is not re-queried.
     *
     * @param array<string, mixed> $result
     */
    private function isMissingForRetry(array $result, string $key): bool
    {
        if (!array_key_exists($key, $result)) {
            return true;
        }
        $value = $result[$key];
        if ($value === null) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    private function isFilledValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value)) {
            return trim($value) !== '';
        }
        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }
}
