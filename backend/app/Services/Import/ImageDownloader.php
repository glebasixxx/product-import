<?php

namespace App\Services\Import;

use App\Exceptions\ImageDownloadException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageDownloader
{
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/bmp' => 'bmp',
    ];

    private const MAX_HOST_FAILURES = 3;

    /** @var array<string, int> */
    private array $hostFailures = [];

    /**
     * @throws ImageDownloadException
     */
    public function download(string $url): string
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array($scheme, ['http', 'https'], true)) {
            throw new ImageDownloadException(sprintf('Некорректная ссылка на изображение: %s', $url));
        }

        $basename = $this->directory().'/'.sha1($url);

        foreach (self::EXTENSIONS as $extension) {
            if ($this->disk()->exists($basename.'.'.$extension)) {
                return $basename.'.'.$extension;
            }
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (($this->hostFailures[$host] ?? 0) >= self::MAX_HOST_FAILURES) {
            throw new ImageDownloadException(sprintf('Хост %s недоступен, изображение %s пропущено.', $host, $url));
        }

        try {
            $response = Http::connectTimeout(5)
                ->timeout((int) config('import.images.timeout'))
                ->withUserAgent('ProductImport/1.0')
                ->retry(2, 300, throw: false)
                ->get($url);
        } catch (\Throwable $e) {
            $this->hostFailures[$host] = ($this->hostFailures[$host] ?? 0) + 1;

            throw new ImageDownloadException(sprintf('Не удалось скачать %s: %s', $url, $e->getMessage()), previous: $e);
        }

        unset($this->hostFailures[$host]);

        if (! $response->successful()) {
            throw new ImageDownloadException(sprintf('Не удалось скачать %s: HTTP %d', $url, $response->status()));
        }

        $body = $response->body();
        $maxBytes = (int) config('import.images.max_size_kb') * 1024;

        if ($body === '' || strlen($body) > $maxBytes) {
            throw new ImageDownloadException(sprintf('Изображение %s пустое или слишком большое.', $url));
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($body);
        $extension = self::EXTENSIONS[$mime] ?? null;

        if ($extension === null) {
            throw new ImageDownloadException(sprintf('Файл %s не является изображением (%s).', $url, $mime ?: 'unknown'));
        }

        $path = $basename.'.'.$extension;

        if (! $this->disk()->put($path, $body)) {
            throw new ImageDownloadException(sprintf('Не удалось сохранить изображение %s.', $url));
        }

        return $path;
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('import.images.disk'));
    }

    private function directory(): string
    {
        return (string) config('import.images.directory');
    }
}
