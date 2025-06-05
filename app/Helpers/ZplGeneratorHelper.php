<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class ZplGeneratorHelper
{
    private const LABELARY_API_URL = 'https://api.labelary.com/v1/printers/8dpmm/labels/4x4/0/';
    
    /**
     * ZPL kodunu PNG görüntüsüne çevirir
     *
     * @param string $zplCode
     * @return string|null Binary PNG data or null on failure
     */
    public static function generatePngFromZpl(string $zplCode): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'image/png',
                    'Accept-Language' => 'tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
                    'X-Linter' => 'On',
                    'X-Quality' => 'grayscale'
                ])
                ->attach('file', $zplCode, 'blob', [
                    'Content-Type' => 'text/plain'
                ])
                ->post(self::LABELARY_API_URL, [
                    '_charset_' => 'UTF-8'
                ]);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error('Labelary API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'zpl_length' => strlen($zplCode)
            ]);

            return null;
        } catch (RequestException $e) {
            Log::error('Labelary API request failed', [
                'message' => $e->getMessage(),
                'zpl_length' => strlen($zplCode)
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ZPL generation failed', [
                'message' => $e->getMessage(),
                'zpl_length' => strlen($zplCode)
            ]);

            return null;
        }
    }
    
    /**
     * ZPL kodunun geçerli olup olmadığını kontrol eder
     *
     * @param string $zplCode
     * @return bool
     */
    public static function isValidZpl(string $zplCode): bool
    {
        // Basit ZPL format kontrolü
        return str_starts_with(trim($zplCode), '^XA') && str_ends_with(trim($zplCode), '^XZ');
    }
} 