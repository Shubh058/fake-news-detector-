@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center mb-5">
        <div class="col-md-10">
            <div class="card shadow-sm border-0" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);">
                <div class="card-body p-4 p-md-5">
                    <h2 class="text-center mb-4 font-weight-bold" style="color: #4a4a4a;">Detect Fake News</h2>
                    <form method="POST" action="{{ route('news.analyze') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="news_text" class="form-label text-secondary fw-bold">Paste News Article or URL</label>
                            <textarea class="form-control fs-5" id="news_text" name="news_text" rows="5" placeholder="Enter the text or URL you want to verify..." required></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary px-5 py-3 fs-5 shadow-sm">Analyze Article</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <hr style="border-color: rgba(0,0,0,0.1); margin-top: 3rem; margin-bottom: 3rem;">

    <!-- Statistics Cards -->
    <div class="row mb-5">
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100 text-center py-4" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);">
                <div class="card-body">
                    <h5 class="text-secondary text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">Total Checks</h5>
                    <h1 class="display-3 fw-bold" style="color: #6a82fb;">{{ $totalAnalyses }}</h1>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100 text-center py-4" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);">
                <div class="card-body">
                    <h5 class="text-secondary text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">Verified News</h5>
                    <h1 class="display-3 fw-bold text-success">{{ $verifiedCount }}</h1>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100 text-center py-4" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);">
                <div class="card-body">
                    <h5 class="text-secondary text-uppercase fw-bold mb-3" style="letter-spacing: 1px;">Unverified / Fake</h5>
                    <h1 class="display-3 fw-bold text-danger">{{ $fakeCount }}</h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Analyses Table -->
    <div class="card shadow-sm border-0 mb-5" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(10px);">
        <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4">
            <h3 class="mb-0" style="color: #4a4a4a;">Recent Analyses</h3>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle bg-transparent mb-0">
                    <thead style="background: rgba(255,255,255,0.4);">
                        <tr>
                            <th class="border-0 text-secondary">#</th>
                            <th class="border-0 text-secondary">News Snippet</th>
                            <th class="border-0 text-secondary text-center">Result</th>
                            <th class="border-0 text-secondary text-end">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($analyses as $analysis)
                        <tr style="background: transparent;">
                            <td class="border-light">{{ $loop->iteration }}</td>
                            <td class="border-light" style="max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $analysis->news_text }}
                            </td>
                            <td class="border-light text-center">
                                @if($analysis->result === 'Verified News' || $analysis->result === 'Trusted Source')
                                    <span class="badge bg-success rounded-pill px-3 py-2">✅ {{ $analysis->result }}</span>
                                @elseif($analysis->result === 'Unverified')
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">⚠️ Unverified</span>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3 py-2">🚫 Unverified / Fake</span>
                                @endif
                            </td>
                            <td class="border-light text-end text-muted">
                                {{ $analysis->created_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No analyses have been performed yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
