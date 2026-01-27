<?php
declare(strict_types=1);

namespace App\Services\OpenAI;

final class OpenAIClient
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl = 'https://api.openai.com/v1'
    ) {}

    /** @return array<string,mixed> */
    public function json(string $method, string $path, ?array $body = null): array
    {
        $url = rtrim($this->baseUrl, '/') . $path;

        $ch = curl_init($url);
        $headers = [
            "Authorization: Bearer {$this->apiKey}",
            "Content-Type: application/json",
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
        ]);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("OpenAI cURL error: {$err}");
        }
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("OpenAI {$method} {$path} failed HTTP {$code}: {$raw}");
        }

        return json_decode($raw, true) ?? [];
    }

    /** @return array<string,mixed> */
    public function uploadBatchFile(string $jsonlPath): array
    {
        $url = rtrim($this->baseUrl, '/') . "/files";

        $ch = curl_init($url);
        $post = [
            'purpose' => 'batch',
            'file' => new \CURLFile($jsonlPath, 'application/jsonl', basename($jsonlPath)),
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->apiKey}"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);

        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Upload cURL error: {$err}");
        }
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Upload failed HTTP {$code}: {$raw}");
        }

        return json_decode($raw, true) ?? [];
    }

    public function downloadFileContent(string $fileId, string $destPath): void
    {
        $url = rtrim($this->baseUrl, '/') . "/files/{$fileId}/content";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$this->apiKey}"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
        ]);

        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Download cURL error: {$err}");
        }
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("Download failed HTTP {$code}: {$raw}");
        }

        file_put_contents($destPath, $raw);
    }

    public function listModels(): array
    {
        return $this->json('GET', '/models', null);
    }
}