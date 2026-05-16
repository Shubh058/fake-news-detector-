@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header text-center py-4 bg-transparent border-0">
                    <h2 class="mb-0 font-weight-bold" style="color: #4a4a4a;">AI Analysis Result</h2>
                </div>
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-5">
                        @if($result === 'Verified News')
                            <span class="badge bg-success fs-4 p-3 rounded-pill shadow-sm">✅ Verified News</span>
                        @else
                            <span class="badge bg-danger fs-4 p-3 rounded-pill shadow-sm">⚠️ Unverified / Fake</span>
                        @endif
                    </div>

                    @if(!empty($context))
                        <div class="mt-4 p-4 rounded" style="background: rgba(255,255,255,0.6); border: 1px solid rgba(255,255,255,0.8);">
                            <h4 class="mb-3" style="color: #6a82fb;">Explanation</h4>
                            <p class="mb-4" style="font-size: 1.1rem; line-height: 1.7; color: #555;">
                                {{ $context['explanation'] ?? 'No detailed explanation provided by the AI.' }}
                            </p>
                            
                            @if(!empty($context['headlines']) && is_array($context['headlines']))
                                <h4 class="mt-4 mb-3" style="color: #6a82fb;">Related Headlines</h4>
                                <ul class="list-group list-group-flush rounded shadow-sm">
                                    @foreach($context['headlines'] as $headline)
                                        <li class="list-group-item" style="background: rgba(255,255,255,0.7); border-color: rgba(0,0,0,0.05); color: #444;">
                                            📰 {{ is_string($headline) ? $headline : json_encode($headline) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning mt-3 text-center rounded-pill">
                            Could not parse detailed context from the AI models. 
                        </div>
                    @endif

                    <div class="text-center mt-5">
                        <a href="{{ route('news.create') }}" class="btn btn-primary px-5 py-3 fs-5 shadow-sm">Analyze Another Article</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
