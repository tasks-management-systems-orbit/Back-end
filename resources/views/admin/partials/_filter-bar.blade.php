@php
    /**
     * Shared filter-bar partial for admin pages.
     *
     * Expected variables (all optional, sane defaults):
     *   - $dateRange       App\Support\DateRange   (active date range)
     *   - $resetRoute      string|null              (name of the route Reset goes to; defaults to current URL with no query)
     *   - $submitOnChange  bool                     (auto-submit extra filters on change; default true)
     *   - $showPresets     bool                     (render preset buttons; default true)
     *
     * The form preserves all current query parameters except `date_from`,
     * `date_to`, and `page` (so the date range can be edited and pagination
     * resets to page 1 on filter change). Extra filters from the consumer
     * land in the `@yield('extra_filters')` slot inside the form.
     */
    use App\Support\DateRange;
    use Illuminate\Support\Carbon;

    $dateRange = $dateRange ?? DateRange::fromRequest(request());
    $submitOnChange = $submitOnChange ?? true;
    $showPresets = $showPresets ?? true;
    $presets = DateRange::presets();
@endphp

<form method="GET" action="{{ url()->current() }}" id="admin-filter-form">
    {{-- Preserve every existing query param EXCEPT the date range fields and pagination. --}}
    @foreach (request()->except(['date_from', 'date_to', 'page']) as $key => $value)
        @if (is_string($value) || is_numeric($value))
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    {{-- Row 1: date range + presets + Apply + Reset --}}
    <div class="d-flex flex-wrap align-items-center mb-2">
        <div class="input-group input-group-sm mr-2 mb-2" style="max-width: 170px;">
            <div class="input-group-prepend">
                <span class="input-group-text">From</span>
            </div>
            <input type="date" name="date_from" id="date_from"
                class="form-control"
                value="{{ $dateRange->from ?? '' }}"
                max="{{ Carbon::today()->toDateString() }}">
        </div>

        <div class="input-group input-group-sm mr-2 mb-2" style="max-width: 170px;">
            <div class="input-group-prepend">
                <span class="input-group-text">To</span>
            </div>
            <input type="date" name="date_to" id="date_to"
                class="form-control"
                value="{{ $dateRange->to ?? '' }}"
                max="{{ Carbon::today()->toDateString() }}">
        </div>

        @if ($showPresets)
            <div class="btn-group btn-group-sm mr-2 mb-2" role="group" aria-label="Date presets">
                @foreach ($presets as $preset)
                    <button type="button"
                        class="btn btn-outline-secondary js-date-preset"
                        data-from="{{ $preset['from'] ?? '' }}"
                        data-to="{{ $preset['to'] ?? '' }}">
                        {{ $preset['label'] }}
                    </button>
                @endforeach
            </div>
        @endif

        <button type="submit" class="btn btn-sm btn-primary mb-2 mr-2">
            <i class="fas fa-filter"></i> Apply
        </button>

        <a href="{{ $resetRoute ?? url()->current() }}"
            class="btn btn-sm btn-secondary mb-2">
            Reset
        </a>
    </div>

    {{-- Row 2: section-specific filters (search, status, sort, per_page, etc.).
         Consumers push content via @push('extra_filters') ... @endpush. --}}
    <div class="d-flex flex-wrap align-items-center" id="admin-extra-filters">
        @stack('extra_filters')
    </div>
</form>

@if ($showPresets)
    @once
        @push('js')
            <script>
                (function () {
                    // Wire up preset buttons: clicking one fills the date inputs and submits the form.
                    document.addEventListener('click', function (e) {
                        var btn = e.target.closest('.js-date-preset');
                        if (!btn) { return; }
                        var form = btn.closest('form');
                        if (!form) { return; }
                        var from = form.querySelector('#date_from');
                        var to = form.querySelector('#date_to');
                        if (from) { from.value = btn.dataset.from || ''; }
                        if (to) { to.value = btn.dataset.to || ''; }
                        form.submit();
                    });

                    // Auto-submit on change for elements inside the extra-filters slot
                    // that opt in via [data-auto-submit].
                    document.addEventListener('change', function (e) {
                        if (e.target.matches('#admin-extra-filters [data-auto-submit]')) {
                            e.target.form && e.target.form.submit();
                        }
                    });
                })();
            </script>
        @endpush
    @endonce
@endif
