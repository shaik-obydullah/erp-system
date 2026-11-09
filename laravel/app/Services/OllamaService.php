<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url', env('OLLAMA_URL', 'http://erp_ollama:11434'));
        $this->model = config('services.ollama.model', env('AI_MODEL', 'qwen2.5:0.5b'));
    }

    public function chat(string $prompt, string $systemPrompt = '', float $temperature = 0.7, int $maxTokens = 256): ?string
    {
        try {
            $messages = [];

            if ($systemPrompt) {
                $messages[] = [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $prompt,
            ];

            $response = Http::timeout(180)->post("{$this->baseUrl}/api/chat", [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'keep_alive' => '10m',
                'options' => [
                    'temperature' => $temperature,
                    // Keep CPU-only generation bounded so old machines finish
                    // instead of hitting the HTTP timeout.
                    'num_predict' => $maxTokens,
                    'num_ctx' => 2048,
                ],
            ]);

            if ($response->successful()) {
                return $response->json('message.content', '');
            }

            Log::error('Ollama chat request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Ollama chat exception', [
                'message' => $e->getMessage(),
                'prompt' => $prompt,
            ]);

            return null;
        }
    }

    public function generateProductDescription(string $productName, string $category = '', string $specs = ''): ?string
    {
        $systemPrompt = <<<'PROMPT'
You are a professional product description writer for an ecommerce store. Write compelling, accurate, and SEO-friendly product descriptions that convert browsers into buyers. Focus on key benefits, use engaging language, and maintain a professional tone. Keep descriptions between 100-200 words.
PROMPT;

        $userPrompt = "Generate a compelling product description for the following product:\n";
        $userPrompt .= "Product Name: {$productName}\n";

        if ($category) {
            $userPrompt .= "Category: {$category}\n";
        }

        if ($specs) {
            $userPrompt .= "Specifications: {$specs}\n";
        }

        $userPrompt .= "\nWrite a product description that highlights key features, benefits, and encourages purchase.";

        return $this->chat($userPrompt, $systemPrompt, 0.8);
    }

    public function searchProducts(string $query, array $products): ?string
    {
        $systemPrompt = <<<'PROMPT'
You are a product search assistant for an ecommerce store. You must ONLY recommend products that appear in the catalog provided by the user. Never invent products, prices, or categories. If no product matches, say that no matching products were found and suggest broadening the search.
PROMPT;

        // Narrow the catalog first so the tiny CPU model is not asked to
        // reason over every product and hallucinate.
        $candidates = $this->narrowProductCandidates($query, $products);
        $productsJson = json_encode(
            array_slice($candidates, 0, 20),
            JSON_UNESCAPED_UNICODE
        );

        $userPrompt = <<<PROMPT
Product catalog (JSON):
{$productsJson}

Customer query: "{$query}"

From the catalog above only, list up to 3 matching products with name, price and one short reason each. Respect any price limit. Do not mention products that are not in the catalog. Keep the answer under 120 words.
PROMPT;

        return $this->chat($userPrompt, $systemPrompt, 0.2, 180);
    }

    /**
     * Reduce the catalog to plausible candidates using simple keyword,
     * category and price matching before the LLM sees it.
     */
    protected function narrowProductCandidates(string $query, array $products): array
    {
        $q = strtolower($query);
        $max = null;
        $min = null;

        if (preg_match('/(?:under|below|less than|cheaper than|up to|upto|max(?:imum)?)\s*\$?\s*(\d+(?:\.\d+)?)/i', $q, $m)) {
            $max = (float) $m[1];
        }
        if (preg_match('/(?:over|above|more than|from)\s*\$?\s*(\d+(?:\.\d+)?)/i', $q, $m)) {
            $min = (float) $m[1];
        }

        $synonyms = [
            'headphone' => ['audio'], 'headphones' => ['audio'],
            'earphone' => ['audio'], 'earphones' => ['audio'],
            'earbud' => ['audio', 'electronics'], 'earbuds' => ['audio', 'electronics'],
            'speaker' => ['audio'], 'speakers' => ['audio'], 'audio' => ['audio'],
            'phone' => ['phones'], 'smartphone' => ['phones'], 'mobile' => ['phones'],
            'laptop' => ['laptops'], 'notebook' => ['laptops'],
            'tv' => ['electronics', 'accessories'], 'television' => ['electronics', 'accessories'],
            'watch' => ['wearables'], 'wearable' => ['wearables'],
            'shoe' => ['shoes'], 'shoes' => ['shoes'], 'sneaker' => ['shoes'], 'sneakers' => ['shoes'],
            'book' => ['fiction', 'non-fiction'], 'novel' => ['fiction'],
            'lego' => ['lego'], 'toy' => ['lego', 'board games'], 'game' => ['board games'],
            'kitchen' => ['kitchen'], 'mixer' => ['kitchen'], 'blender' => ['kitchen'], 'fryer' => ['kitchen'],
            'furniture' => ['furniture'], 'shelf' => ['furniture'], 'chair' => ['furniture'],
            'fitness' => ['fitness', 'sports'], 'gym' => ['fitness', 'sports'], 'yoga' => ['sports'],
            'beauty' => ['skincare', 'makeup'], 'skincare' => ['skincare'], 'makeup' => ['makeup'],
            'coffee' => ['coffee'], 'snack' => ['snacks'], 'snacks' => ['snacks'],
            'camera' => ['electronics'], 'printer' => ['electronics'], 'keyboard' => ['electronics'],
            'bag' => ['bags'], 'sunglasses' => ['accessories'],
        ];

        $stop = [
            'a', 'an', 'the', 'for', 'with', 'and', 'or', 'of', 'to', 'in', 'on',
            'under', 'below', 'less', 'than', 'cheaper', 'up', 'max', 'maximum',
            'over', 'above', 'more', 'from', 'me', 'show', 'find', 'some', 'any',
            'best', 'good', 'cheap', 'cheapest', 'wireless', 'new', 'please', 'i',
            'want', 'need', 'looking', 'look', 'buy',
        ];

        $tokens = array_values(array_filter(
            preg_split('/[^a-z0-9]+/', $q) ?: [],
            fn ($t) => $t !== '' && ! in_array($t, $stop, true) && ! is_numeric($t)
        ));

        $categoryHints = [];
        foreach ($tokens as $token) {
            foreach ($synonyms[$token] ?? [] as $category) {
                $categoryHints[$category] = true;
            }
        }

        $scored = [];
        foreach ($products as $product) {
            $price = (float) ($product['price'] ?? 0);
            if ($max !== null && $price > $max) {
                continue;
            }
            if ($min !== null && $price < $min) {
                continue;
            }

            $haystack = strtolower(
                ($product['name'] ?? '') . ' ' .
                ($product['sku'] ?? '') . ' ' .
                ($product['category'] ?? '')
            );

            $score = 0;
            foreach ($tokens as $token) {
                if (str_contains($haystack, $token)) {
                    $score += 2;
                }
            }
            if (isset($categoryHints[strtolower((string) ($product['category'] ?? ''))])) {
                $score += 3;
            }

            $scored[] = ['product' => $product, 'score' => $score];
        }

        // If text/category hints matched anything, keep only those. Otherwise
        // keep everything that passed the numeric price filter.
        $hasTextMatch = false;
        foreach ($scored as $row) {
            if ($row['score'] > 0) {
                $hasTextMatch = true;
                break;
            }
        }
        if ($hasTextMatch) {
            $scored = array_values(array_filter($scored, fn ($row) => $row['score'] > 0));
        }

        usort($scored, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return ($b['product']['stock'] ?? 0) <=> ($a['product']['stock'] ?? 0);
            }
            return $b['score'] <=> $a['score'];
        });

        $result = array_map(fn ($row) => $row['product'], $scored);

        // Never send an empty catalog; fall back to the full list.
        return $result ?: $products;
    }

    public function inventoryInsights(array $stockData): ?string
    {
        $systemPrompt = <<<'PROMPT'
You are an inventory management expert with deep knowledge of supply chain optimization. Analyze inventory data to identify:
1. Products at risk of stockout (low stock levels)
2. Overstocked items that may need markdowns or promotions
3. Reorder quantity suggestions based on stock levels
4. Trends in inventory movement
5. Potential supply chain risks

Provide actionable, data-driven recommendations prioritized by urgency and financial impact. Format your response clearly with sections for each insight category. Be concise: use short bullets and keep the entire answer under 180 words.
PROMPT;

        // Send a compact, balanced subset: the lowest-stock and highest-stock
        // items are the ones the insights actually act on. This keeps the
        // prompt within a small context window on CPU-only machines.
        $sorted = $stockData;
        usort($sorted, fn ($a, $b) => ($a['quantity'] ?? 0) <=> ($b['quantity'] ?? 0));
        $subset = array_merge(
            array_slice($sorted, 0, 10),
            array_slice($sorted, -6)
        );

        $stockJson = json_encode($subset, JSON_UNESCAPED_UNICODE);

        $userPrompt = <<<PROMPT
Based on the inventory data below, answer with at most 6 short bullet points (max 15 words each). No headings and no extra text. Cover: urgent restock items, overstock/markdown items, one reorder quantity, and one key risk.

{$stockJson}
PROMPT;

        return $this->chat($userPrompt, $systemPrompt, 0.3, 200);
    }

    public function salesForecast(array $salesHistory): ?string
    {
        $systemPrompt = <<<'PROMPT'
You are a sales analyst. Use ONLY the monthly revenue data provided. Never invent numbers or products. If there are fewer than 3 months with sales, say the forecast is unreliable and give a cautious range instead of precise figures. Be concise.
PROMPT;

        // Sending a year of zero rows confuses a tiny model. Keep only the
        // months that actually had sales.
        $monthsWithSales = array_values(array_filter(
            $salesHistory,
            fn ($row) => (float) ($row['total'] ?? 0) > 0
        ));
        $monthCount = count($monthsWithSales);
        $salesJson = json_encode($monthsWithSales ?: $salesHistory, JSON_UNESCAPED_UNICODE);

        $latest = $monthsWithSales ? end($monthsWithSales) : null;
        $latestTotal = $latest ? (float) $latest['total'] : 0.0;
        $latestMonth = $latest['month'] ?? 'n/a';
        $lastThree = array_slice($monthsWithSales, -3);
        $avg3 = $lastThree
            ? round(array_sum(array_column($lastThree, 'total')) / count($lastThree), 2)
            : 0.0;

        // Compute the range deterministically; the tiny model cannot do the
        // arithmetic reliably and otherwise invents numbers.
        $low = round($latestTotal * 0.9, 2);
        $high = round($latestTotal * 1.1, 2);
        $latestText = number_format($latestTotal, 2, '.', '');
        $avgText = number_format($avg3, 2, '.', '');
        $lowText = number_format($low, 2, '.', '');
        $highText = number_format($high, 2, '.', '');

        $userPrompt = <<<PROMPT
Monthly revenue data: {$salesJson}

Give exactly two short suggestions for next month:
- one inventory action (which products to stock more or less)
- one promotion idea
Do not mention revenue, prices or dollar amounts. Reply as two short sentences, no labels and no extra text.
PROMPT;

        $suggestions = $this->chat($userPrompt, $systemPrompt, 0.4, 100);

        // The numbers are computed in PHP because the tiny model cannot do
        // the arithmetic reliably; the model only adds the suggestions.
        $forecast = "1. Data: {$monthCount} months of sales; latest {$latestMonth} was \${$latestText}.\n"
            . "2. Next month: \${$lowText} to \${$highText}.";

        $forecast .= "\n3. Suggestions: "
            . trim($suggestions ?: 'Align stock with recent demand and run a small promotion to build more sales history.');

        return $forecast;
    }

    public function customerSupport(string $message, array $context = []): ?string
    {
        $appName = config('app.name', 'Our Store');

        $systemPrompt = <<<PROMPT
You are a helpful and friendly customer support assistant for {$appName}. You help customers with:
- Product questions and recommendations
- Order status inquiries
- Returns and refund policies
- General shopping assistance
- Troubleshooting common issues

Guidelines:
- Be polite, professional, and empathetic
- Provide accurate information based on the context given
- If you don't know something, acknowledge it and offer to connect them with a human agent
- Keep responses concise but thorough
- Use a warm, approachable tone
PROMPT;

        $userPrompt = $message;

        if (!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
            $userPrompt = <<<PROMPT
Customer message: "{$message}"

Additional context:
{$contextJson}

Respond helpfully using the provided context.
PROMPT;
        }

        return $this->chat($userPrompt, $systemPrompt, 0.6);
    }

    public function suggestPrice(string $productName, float $currentPrice, array $competitorPrices = []): ?string
    {
        $systemPrompt = <<<'PROMPT'
You are a pricing strategy expert for ecommerce. When suggesting prices, consider:
1. Competitive market positioning
2. Profit margin optimization
3. Customer perceived value
4. Price elasticity considerations
5. Market demand signals

Provide a specific recommended price, explain your reasoning, and note the expected impact on sales volume and margins. Always suggest a price range (minimum, optimal, maximum). Be concise: use short bullets and keep the entire answer under 120 words.
PROMPT;

        $userPrompt = "Suggest optimal pricing for the following product:\n";
        $userPrompt .= "Product Name: {$productName}\n";
        $userPrompt .= "Current Price: $" . number_format($currentPrice, 2) . "\n";

        if (!empty($competitorPrices)) {
            $userPrompt .= "Competitor Prices:\n";
            foreach ($competitorPrices as $index => $price) {
                $num = $index + 1;
                $priceVal = is_array($price) ? $price['price'] ?? 0 : $price;
                $source = is_array($price) ? ($price['source'] ?? "Competitor {$num}") : "Competitor {$num}";
                $userPrompt .= "- {$source}: $" . number_format($priceVal, 2) . "\n";
            }
        }

        $userPrompt .= <<<PROMPT

Reply in this exact format with no extra text:
Recommended: <price> - <one short reason>
Min: <price>
Max: <price>
Impact: <one short sentence>
Strategy: <one short sentence>
PROMPT;

        return $this->chat($userPrompt, $systemPrompt, 0.4, 160);
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            if (! $response->successful()) {
                return false;
            }

            // Ollama is only usable if the configured model has actually been
            // pulled. `/api/tags` returning 200 with an empty list still means
            // every generation request will fail with "model not found".
            $models = collect($response->json('models', []))
                ->pluck('name')
                ->filter()
                ->values();

            if ($models->isEmpty()) {
                Log::warning('Ollama is running but no models are installed', [
                    'url' => $this->baseUrl,
                    'model' => $this->model,
                ]);

                return false;
            }

            $hasModel = $models->contains(function (string $name) {
                return $name === $this->model
                    || str_starts_with($name, $this->model.':');
            });

            if (! $hasModel) {
                Log::warning('Ollama model is not installed', [
                    'url' => $this->baseUrl,
                    'model' => $this->model,
                    'installed' => $models->all(),
                ]);
            }

            return $hasModel;
        } catch (\Exception $e) {
            Log::warning('Ollama availability check failed', [
                'url' => $this->baseUrl,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
