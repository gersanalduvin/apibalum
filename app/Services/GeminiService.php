<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Enviar un prompt a Gemini y obtener la respuesta.
     */
    public function generateContent(string $prompt, bool $isJson = true)
    {
        try {
            $endpoint = "{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(90)
                ->withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => $isJson ? 'application/json' : 'text/plain',
                    ]
                ]);

            if ($response->failed()) {
                Log::error('Error en Gemini API:', $response->json());
                throw new Exception('La comunicación con la IA ha fallado: ' . ($response->json()['error']['message'] ?? 'Error desconocido'));
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                throw new Exception('No se recibió contenido de la IA.');
            }

            return $isJson ? json_decode($text, true) : $text;
        } catch (Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
