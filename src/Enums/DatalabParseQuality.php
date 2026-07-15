<?php

namespace ImmiTranslate\Datalab\Enums;

/**
 * Interpretation bands for the parse_quality_score (0-5) returned by the Convert API.
 * Use these to build automated quality gates, e.g. retry with DatalabMode::Accurate
 * when a conversion comes back Fair or Poor.
 */
enum DatalabParseQuality: string
{
    /** 4.0 - 5.0 — use the output directly. */
    case Excellent = 'excellent';

    /** 3.0 - 3.9 — review for minor issues. */
    case Good = 'good';

    /** 2.0 - 2.9 — consider retrying with 'accurate' mode. */
    case Fair = 'fair';

    /** 0.0 - 1.9 — retry with 'accurate' mode or check the input file. */
    case Poor = 'poor';

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 4.0 => self::Excellent,
            $score >= 3.0 => self::Good,
            $score >= 2.0 => self::Fair,
            default => self::Poor,
        };
    }

    /**
     * Recommended action for this quality band, from the Datalab documentation.
     */
    public function recommendedAction(): string
    {
        return match ($this) {
            self::Excellent => 'Use the output directly',
            self::Good => 'Review for minor issues',
            self::Fair => "Consider retrying with 'accurate' mode",
            self::Poor => "Retry with 'accurate' mode or check the input file",
        };
    }
}
