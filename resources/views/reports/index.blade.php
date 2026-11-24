@extends('layouts.mainlayout')

@section('title', 'Saved Reports | ' . config('app.name', 'FoodoCosting'))
@section('page-title', 'Saved Reports')

@section('content')
    <section class="section saved-reports-page">

        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('cost-analysis.index') }}" class="btn btn-primary btn-sm">+ New Analysis</a>
        </div>

        <div class="card">
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Report</th>
                                <th scope="col">Saved</th>
                                <th scope="col" class="text-end">Items</th>
                                <th scope="col" class="text-end">Direct Cost</th>
                                <th scope="col" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reports as $report)
                                <tr>
                                    <td class="fw-semibold">{{ $report->title }}</td>
                                    <td>{{ $report->created_at->format('M d, Y g:i A') }}</td>
                                    <td class="text-end">{{ count($report->snapshot['items']) }}</td>
                                    <td class="text-end">₱{{ number_format(collect($report->snapshot['items'])->sum('line_total'), 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('cost-analysis.reports.show', $report) }}"
                                           class="btn btn-outline-primary btn-sm py-1 px-2"
                                           aria-label="View report: {{ $report->title }}" title="View report">
                                            <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('cost-analysis.reports.show', ['report' => $report, 'print' => 1]) }}"
                                           target="_blank" rel="noopener"
                                           class="btn btn-outline-dark btn-sm py-1 px-2"
                                           aria-label="Print report: {{ $report->title }}" title="Print report">
                                            <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                                <path d="M7 14h10v7H7zM17.5 11h.01" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No saved reports yet. Save an analysis to keep a copy here.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
