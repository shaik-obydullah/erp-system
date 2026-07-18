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
        $this->model = config('services.ollama.model', env('AI_MODEL', 'llama3.2'));
    }

    public function chat(string $prompt, string $systemPrompt = '', float $temperature = 0.7): ?string
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

            $response = Http::timeout(120)->post("{$this->baseUrl}/api/chat", [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'options' => [
                    'temperature' => $temperature,
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
You are a product search assistant for an ecommerce store. Your job is to understand natural language queries from users and find the most relevant products from the catalog. Provide helpful, concise responses with product recommendations. When matching products, explain why each product matches the query. If no products match, suggest alternatives or clarify what the user might be looking for.
PROMPT;

        $productsJson = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $userPrompt = <<<PROMPT
Given this product catalog:
{$productsJson}

User query: "{$query}"

Find the most relevant products and provide a helpful response. List matching products with their names, prices, and why they match the query.
PROMPT;

        return $this->chat($userPrompt, $systemPrompt, 0.3);
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

Provide actionable, data-driven recommendations prioritized by urgency and financial impact. Format your response clearly with sections for each insight category.
PROMPT;

        $stockJson = json_encode($stockData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $userPrompt = <<<PROMPT
Analyze this inventory data and provide insights:

{$stockJson}

Provide:
- List of items that need immediate restocking
- Overstocked items to address
- Reorder suggestions with recommended quantities
- Any trends or patterns you notice
- Priority actions ranked by business impact
PROMPT;

        return $this->chat($userPrompt, $systemPrompt, 0.4);
    }

    public function salesForecast(array $salesHistory): ?string
    {
        $systemPrompt = <<<'PROMPT'
You are a sales analyst specializing in retail and ecommerce forecasting. Analyze historical sales data to:
1. Identify sales trends (growth, decline, seasonality)
2. Forecast sales for the next 3 months
3. Recommend inventory adjustments based on forecast
4. Suggest promotional strategies to boost underperforming periods
5. Highlight revenue opportunities

Be specific with numbers and percentages. Base forecasts on observed patterns in the data provided. Clearly state any assumptions you make.
PROMPT;

        $salesJson = json_encode($salesHistory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $userPrompt = <<<PROMPT
Based on this sales history:

{$salesJson}

Please provide:
1. Sales trend analysis (growth rates, patterns, seasonality)
2. Month-by-month forecast for the next 3 months with estimated revenue
3. Inventory recommendations based on projected demand
4. Promotional strategy suggestions
5. Key metrics summary
PROMPT;

        return $this->chat($userPrompt, $systemPrompt, 0.5);
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
            $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

Provide a specific recommended price, explain your reasoning, and note the expected impact on sales volume and margins. Always suggest a price range (minimum, optimal, maximum) and explain when each might be appropriate.
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

Provide:
1. Recommended price with reasoning
2. Minimum price (discount floor)
3. Maximum price (premium ceiling)
4. Expected impact on sales volume
5. Pricing strategy recommendation
PROMPT;

        return $this->chat($userPrompt, $systemPrompt, 0.5);
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/tags");

            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Ollama availability check failed', [
                'url' => $this->baseUrl,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
