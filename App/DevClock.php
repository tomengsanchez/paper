<?php
namespace App;

use DateTimeImmutable;

/**
 * Development clock: when a tester sets a simulated date, the app uses it
 * instead of the real date. Helps verify escalation (days_to_address) and other
 * date-based logic without waiting real days.
 *
 * Stored in session; only active when Development → Simulated date is set. Time is ignored (date only).
 */
class DevClock
{
    private const SESSION_KEY = 'dev_clock_override';

    /** Return the "current" date/time (simulated date at midnight if set, otherwise real). */
    public static function now(): DateTimeImmutable
    {
        $override = self::getOverride();
        if ($override !== null) {
            try {
                return new DateTimeImmutable($override . ' 00:00:00');
            } catch (\Exception $e) {
                // fallback to real time
            }
        }
        return new DateTimeImmutable('now');
    }

    /** Return "today" as Y-m-d for use in SQL (e.g. DATEDIFF). */
    public static function today(): string
    {
        return self::now()->format('Y-m-d');
    }

    public static function isOverridden(): bool
    {
        return self::getOverride() !== null;
    }

    /** Get the override date (Y-m-d) or null. */
    public static function getOverride(): ?string
    {
        $v = $_SESSION[self::SESSION_KEY] ?? null;
        return is_string($v) && $v !== '' ? $v : null;
    }

    /**
     * Set the simulated date. Accepts date string (Y-m-d). Stored as date only; time is not used.
     * Use clearOverride() to revert to real date.
     */
    public static function setOverride(string $date): void
    {
        $trimmed = trim($date);
        if ($trimmed === '') {
            self::clearOverride();
            return;
        }
        try {
            $dt = new DateTimeImmutable($trimmed);
            $_SESSION[self::SESSION_KEY] = $dt->format('Y-m-d');
        } catch (\Exception $e) {
            // invalid; do not set
        }
    }

    public static function clearOverride(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
