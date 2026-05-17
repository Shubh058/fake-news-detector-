<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $analyses = \App\Models\Analysis::orderBy('created_at', 'desc')->take(20)->get();
        $totalAnalyses = \App\Models\Analysis::count();
        $verifiedCount = \App\Models\Analysis::where('result', 'Verified News')->orWhere('result', 'Trusted Source')->count();
        $fakeCount = \App\Models\Analysis::where('result', 'Unverified')->orWhere('result', 'Fake News')->orWhere('result', 'Possibly Fake')->count();

        return view('news.create', compact('analyses', 'totalAnalyses', 'verifiedCount', 'fakeCount'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'news_text' => 'required|string',
        ]);

        $newsText = $request->input('news_text');
        $originalInput = $newsText;

        // If the input is a valid URL, try to extract some text
        if (filter_var($newsText, FILTER_VALIDATE_URL)) {
            try {
                $response = Http::timeout(5)->get($newsText);
                if ($response->ok()) {
                    $html = $response->body();
                    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $html);
                    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $html);
                    $extracted = trim(strip_tags($html));
                    if (strlen($extracted) > 50) {
                        $newsText = "URL: " . $originalInput . "\n\nExtracted Content: " . substr($extracted, 0, 4000);
                    }
                }
            } catch (\Exception $e) {
                // Silently ignore HTTP errors and just pass the URL to AI
            }
        }

        $prompt = "You are a highly intelligent fact-checker. Analyze the following news input.
If the input is primarily a URL from a well-known, highly trustworthy news media outlet (e.g., Reuters, AP, BBC, NYT, CNN), you should heavily weigh the domain's reputation. If the domain is highly trusted, classify it as 'Verified'.
If the input contains text claims, verify those claims against your knowledge base.
Provide your response strictly as a JSON object with the following keys:
- 'status': strictly 'Verified' or 'Unverified'
- 'explanation': a detailed paragraph explaining your reasoning and context.
- 'headlines': an array of strings containing related headlines from trustworthy websites.
- 'search_query': a highly optimized search query string (3-6 keywords max) to find this exact news event on a search engine.

News Input:
{$newsText}";

        $openAiKey = env('OPENAI_API_KEY');
        $geminiKey = env('GEMINI_API_KEY');

        // Use HTTP pool to make concurrent requests
        $responses = Http::pool(function (Pool $pool) use ($prompt, $openAiKey, $geminiKey) {
            $requests = [];

            if (!empty($openAiKey)) {
                $requests[] = $pool->as('openai')->withToken($openAiKey)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                ]);
            }

            if (!empty($geminiKey)) {
                $requests[] = $pool->as('gemini')->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ]);
            }

            return $requests;
        });

        $isVerified = false;
        $context = null;
        $searchQuery = null;

        $parseResponse = function ($content) use (&$isVerified, &$context, &$searchQuery) {
            if (!$content) return false;

            // Remove markdown code blocks if any
            $content = preg_replace('/```(?:json)?\s*(.*?)\s*```/s', '$1', $content);
            $json = json_decode($content, true);

            if (is_array($json) && isset($json['status'])) {
                $status = strtolower(trim($json['status']));
                if ($status === 'verified') {
                    $isVerified = true;
                }
                $context = $json;
                $searchQuery = $json['search_query'] ?? null;
                return true;
            }
            return false;
        };

        $success = false;

        if (isset($responses['openai']) && $responses['openai']->ok()) {
            $content = trim($responses['openai']->json('choices.0.message.content') ?? '');
            $success = $parseResponse($content);
        }

        if (!$success && isset($responses['gemini']) && $responses['gemini']->ok()) {
            $content = trim($responses['gemini']->json('candidates.0.content.parts.0.text') ?? '');
            $parseResponse($content);
        }

        // Map AI result to label — only two values: Verified News or Unverified
        $result = $isVerified ? 'Verified News' : 'Unverified';

        if (!$searchQuery && !$isVerified) {
            $words = str_word_count(strip_tags($originalInput), 1);
            $searchQuery = implode(" ", array_slice($words, 0, 4));
        }

        \Illuminate\Support\Facades\Log::info("AI Parsed: Verified=" . ($isVerified ? 'yes' : 'no') . " SearchQuery=" . $searchQuery);

        // Fallback to NewsAPI for unverified — if related articles found, mark related_news_found=true (yellow)
        // If nothing found, related_news_found stays false/absent (red in view)
        if (!$isVerified && $searchQuery) {
            try {
                $newsapiKey = env('NEWSAPI_KEY');
                if ($newsapiKey) {
                    $newsapi = new \jcobhams\NewsApi\NewsApi($newsapiKey);
                    $all_articles = $newsapi->getEverything($searchQuery, null, null, null, null, null, 'en', 'relevancy', 5, 1);

                    \Illuminate\Support\Facades\Log::info("NewsAPI returned totalResults: " . ($all_articles->totalResults ?? 'null'));

                    if (isset($all_articles->totalResults) && $all_articles->totalResults > 0) {
                        // Related news exists — keep Unverified but flag it (yellow in view)
                        $context['related_news_found'] = true;
                        $context['explanation'] = "This news could not be verified by the AI's internal knowledge base, but related articles from global news sources were found via real-time search.";
                        $context['headlines'] = [];
                        foreach ($all_articles->articles as $article) {
                            $context['headlines'][] = $article->title . " (" . $article->source->name . ")";
                        }
                    }
                    // If totalResults == 0, related_news_found is not set → red in view
                } else {
                    \Illuminate\Support\Facades\Log::info("NewsAPI Key is missing.");
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("NewsAPI Error: " . $e->getMessage());
            }
        }

        // Save to analyses table
        \App\Models\Analysis::create([
            'news_text' => $newsText,
            'result' => $result,
            'context' => $context ? json_encode($context) : null,
        ]);

        return view('news.result', compact('result', 'context'));
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(News $news)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, News $news)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        //
    }
}
