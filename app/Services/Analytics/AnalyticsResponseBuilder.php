<?php

namespace App\Services\Analytics;

class AnalyticsResponseBuilder
{
    /** @var array<string, mixed> */
    private array $summary = [];

    /** @var array<int|string, mixed> */
    private array $series = [];

    /** @var array<int|string, mixed> */
    private array $rows = [];

    /** @var array<string, mixed>|null */
    private ?array $pagination = null;

    /** @var array<string, mixed> */
    private array $filtersApplied = [];

    /** @var array<string, mixed> */
    private array $config = [];

    /**
     * Set summary data.
     *
     * @param  array<string, mixed>  $summary
     */
    public function withSummary(array $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Set time-series data.
     *
     * @param  array<int|string, mixed>  $series
     */
    public function withSeries(array $series): static
    {
        $this->series = $series;

        return $this;
    }

    /**
     * Set tabular row data.
     *
     * @param  array<int|string, mixed>  $rows
     */
    public function withRows(array $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Set pagination metadata.
     *
     * @param  array<string, mixed>|null  $pagination
     */
    public function withPagination(?array $pagination): static
    {
        $this->pagination = $pagination;

        return $this;
    }

    /**
     * Set applied filters for display.
     *
     * @param  array<string, mixed>  $filtersApplied
     */
    public function withFiltersApplied(array $filtersApplied): static
    {
        $this->filtersApplied = $filtersApplied;

        return $this;
    }

    /**
     * Set configuration metadata.
     *
     * @param  array<string, mixed>  $config
     */
    public function withConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Build the final response array.
     *
     * @return array{summary: array<string, mixed>, series: array<int|string, mixed>, rows: array<int|string, mixed>, pagination: array<string, mixed>|null, filters_applied: array<string, mixed>, config: array<string, mixed>}
     */
    public function build(): array
    {
        return [
            'summary' => $this->summary,
            'series' => $this->series,
            'rows' => $this->rows,
            'pagination' => $this->pagination,
            'filters_applied' => $this->filtersApplied,
            'config' => $this->config,
        ];
    }
}
