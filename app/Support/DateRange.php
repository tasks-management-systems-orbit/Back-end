<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Value object encapsulating a filterable date range plus preset definitions.
 *
 * Semantics: a `date_from` / `date_to` pair is a half-open interval
 * (`created_at >= date_from 00:00:00` and `created_at <= date_to 23:59:59.999999`).
 * Either bound may be null/empty, in which case it is treated as unbounded.
 */
class DateRange
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
    ) {}

    /**
     * Build a DateRange from the current request's query string.
     */
    public static function fromRequest(Request $request): self
    {
        $from = $request->query('date_from');
        $to = $request->query('date_to');

        $from = self::normalizeDate($from);
        $to = self::normalizeDate($to);

        return new self($from, $to);
    }

    /**
     * Whether the user has applied any non-empty date bound.
     */
    public function isActive(): bool
    {
        return $this->from !== null || $this->to !== null;
    }

    /**
     * Whether the range is "All time" (no bounds at all).
     */
    public function isEmpty(): bool
    {
        return ! $this->isActive();
    }

    /**
     * Array suitable for `http_build_query()` so we can deep-link from
     * dashboard cards without manually concatenating strings.
     */
    public function toQueryString(): array
    {
        return array_filter([
            'date_from' => $this->from,
            'date_to' => $this->to,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Available preset shortcuts, with server-side date math (so the user
     * never has to round-trip through JS). `null` means "no bound".
     *
     * @return array<int, array{label: string, key: string, from: ?string, to: ?string}>
     */
    public static function presets(): array
    {
        $today = Carbon::today();

        return [
            ['key' => '7',  'label' => 'Last 7 days',  'from' => $today->copy()->subDays(6)->toDateString(),  'to' => $today->toDateString()],
            ['key' => '30', 'label' => 'Last 30 days', 'from' => $today->copy()->subDays(29)->toDateString(), 'to' => $today->toDateString()],
            ['key' => '90', 'label' => 'Last 90 days', 'from' => $today->copy()->subDays(89)->toDateString(), 'to' => $today->toDateString()],
            ['key' => 'month', 'label' => 'This month', 'from' => $today->copy()->startOfMonth()->toDateString(), 'to' => $today->toDateString()],
            ['key' => 'all', 'label' => 'All time', 'from' => null, 'to' => null],
        ];
    }

    /**
     * Apply this range to a query builder that has the `scopeCreatedBetween`
     * scope (User, Project, Report, ProjectReport).
     */
    public function apply($query)
    {
        return $query->createdBetween($this->from, $this->to);
    }

    /**
     * Render a short human-readable summary of the range, e.g.
     * "2026-05-19 → 2026-06-18" or "Since 2026-05-19" / "Up to 2026-06-18".
     */
    public function summary(): string
    {
        if ($this->isEmpty()) {
            return 'All time';
        }

        if ($this->from && $this->to) {
            return "{$this->from} → {$this->to}";
        }

        if ($this->from) {
            return "Since {$this->from}";
        }

        return "Up to {$this->to}";
    }

    /**
     * Validate a single Y-m-d style string. Returns null for empty/invalid
     * values so the model scope can simply skip the bound.
     */
    private static function normalizeDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
