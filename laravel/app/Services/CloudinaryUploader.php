<?php

namespace App\Services;

use App\Exceptions\CloudinaryUploadException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Uploads images to Cloudinary through its signed REST upload endpoint.
 *
 * The official PHP SDK pins Guzzle < 8 and cannot be installed alongside
 * this Laravel version, and the upload API is a single signed POST anyway.
 *
 * @see https://cloudinary.com/documentation/upload_images#uploading_with_a_direct_call_to_the_rest_api
 */
class CloudinaryUploader
{
    public function __construct(
        private readonly string $cloudName,
        private readonly string $apiKey,
        #[\SensitiveParameter] private readonly string $apiSecret,
    ) {}

    public static function fromConfig(): self
    {
        $config = config('services.cloudinary');

        foreach (['cloud_name', 'api_key', 'api_secret'] as $key) {
            if (empty($config[$key])) {
                throw new CloudinaryUploadException("Cloudinary is not configured: missing {$key}");
            }
        }

        return new self($config['cloud_name'], $config['api_key'], $config['api_secret']);
    }

    /** Uploads the file into the given folder and returns its HTTPS URL. */
    public function upload(UploadedFile $file, string $folder): string
    {
        $params = [
            'folder' => $folder,
            'timestamp' => (string) now()->timestamp,
        ];

        try {
            $response = Http::timeout(60)
                ->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
                ->post($this->endpoint(), [
                    ...$params,
                    'api_key' => $this->apiKey,
                    'signature' => $this->sign($params),
                ]);
        } catch (ConnectionException $e) {
            throw new CloudinaryUploadException('Could not reach Cloudinary: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed() || ! is_string($response->json('secure_url'))) {
            throw new CloudinaryUploadException(
                $response->json('error.message') ?? "Cloudinary responded with HTTP {$response->status()}",
            );
        }

        return $response->json('secure_url');
    }

    public function endpoint(): string
    {
        return "https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload";
    }

    /**
     * Cloudinary signatures are the SHA-1 of the alphabetically sorted
     * `key=value` pairs joined by "&", with the API secret appended.
     *
     * @param  array<string, string>  $params
     */
    private function sign(array $params): string
    {
        ksort($params);

        $toSign = collect($params)
            ->map(fn (string $value, string $key) => "{$key}={$value}")
            ->implode('&');

        return sha1($toSign.$this->apiSecret);
    }
}
