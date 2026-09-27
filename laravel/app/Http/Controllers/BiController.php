<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BiController extends Controller
{
    private string $biApi = 'http://bi_flask:5000/api';

    private function bi(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = match (strtoupper($method)) {
                'GET' => Http::timeout(30)->get("{$this->biApi}{$endpoint}"),
                'POST' => Http::timeout(30)->withJson($data)->post("{$this->biApi}{$endpoint}"),
                default => Http::timeout(30)->get("{$this->biApi}{$endpoint}"),
            };
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function dashboard()
    {
        $employeeData = $this->bi('POST', '/analyze');
        $productData = $this->bi('GET', '/product-analysis');
        $forecastData = $this->bi('GET', '/sales-forecast');
        $comboData = $this->bi('GET', '/product-combos');

        return view('bi.dashboard', compact('employeeData', 'productData', 'forecastData', 'comboData'));
    }

    public function employees()
    {
        $data = $this->bi('POST', '/analyze');
        return view('bi.employees', compact('data'));
    }

    public function products()
    {
        $data = $this->bi('GET', '/product-analysis');
        return view('bi.products', compact('data'));
    }

    public function recommendations()
    {
        $strategy = request()->get('strategy', 'popular');
        $data = $this->bi('POST', '/recommendations/products', [
            'strategy' => $strategy,
            'limit' => 20,
        ]);
        return view('bi.recommendations', compact('data', 'strategy'));
    }

    public function forecast()
    {
        $data = $this->bi('GET', '/sales-forecast');
        return view('bi.forecast', compact('data'));
    }

    public function prophetForecast()
    {
        $periods = request()->get('periods', 12);
        $data = $this->bi('GET', "/prophet-forecast?periods={$periods}");
        return view('bi.prophet-forecast', compact('data', 'periods'));
    }

    public function combos()
    {
        $data = $this->bi('GET', '/product-combos');
        return view('bi.combos', compact('data'));
    }
}
