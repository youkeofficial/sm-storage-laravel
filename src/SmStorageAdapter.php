<?php

namespace Youke\SmStorage;

use Illuminate\Support\Facades\Http;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;

class SmStorageAdapter implements FilesystemAdapter
{
    protected $config;
    protected $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $endpoint = trim($config['endpoint'] ?? $config['url'] ?? '');
        $endpoint = rtrim($endpoint, '/');
        
        if (!empty($endpoint) && !str_contains($endpoint, 'api.php')) {
            $endpoint .= '/api.php';
        }
        
        $this->baseUrl = $endpoint;
    }

    /**
     * Crée une nouvelle requête HTTP avec configuration SSL dynamique
     */
    protected function newRequest()
    {
        // Headers API standards
        $request = Http::timeout(60)->withHeaders([
            'Accept' => 'application/json',
        ]);
        
        if (app()->isLocal()) {
            $request->withoutVerifying();
        }
        
        return $request;
    }

    /**
     * Upload d'un fichier vers votre SaaS
     */
    public function write(string $path, string $contents, Config $config): void
    {
        // On passe les tokens en GET dans l'URL pour plus de compatibilité
        $query = http_build_query([
            'action' => 'api_upload',
            'key' => $this->config['key'],
            'bucket_id' => $this->config['bucket_id']
        ]);
        
        $response = $this->newRequest()
            ->attach('file', $contents, basename($path))
            ->post($this->baseUrl . '?' . $query);

        if (!$response->successful()) {
            throw new \Exception("SaaS Error [UPLOAD]: " . $response->body());
        }
    }

    /**
     * Upload via Stream (utilisé par Laravel pour les gros fichiers)
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    /**
     * Suppression d'un fichier via API
     */
    public function delete(string $path): void
    {
        $query = http_build_query([
            'action' => 'api_delete',
            'key' => $this->config['key'],
            'bucket_id' => $this->config['bucket_id'],
            'id' => $path
        ]);

        $response = $this->newRequest()->post($this->baseUrl . '?' . $query);

        // Si le serveur répond 404, c'est que le fichier est introuvable ou déjà supprimé.
        // Dans le cycle de vie de Laravel, une suppression sur un fichier manquant doit rester silencieuse (succès logique).
        if (!$response->successful() && $response->status() !== 404) {
            throw new \Exception("SaaS Error [DELETE]: " . $response->body());
        }
    }

    /**
     * Vérifier si un fichier existe (via api_list)
     */
    public function fileExists(string $path): bool
    {
        $response = $this->newRequest()->get($this->baseUrl, [
            'action' => 'api_list',
            'key' => $this->config['key'],
            'bucket_id' => $this->config['bucket_id'],
        ]);

        if ($response->successful()) {
            $files = $response->json('data', []);
            foreach ($files as $file) {
                if ($file['original_name'] === basename($path))
                    return true;
            }
        }
        return false;
    }

    /**
     * Génération de l'URL publique
     * Laravel appelle cette méthode quand vous faites Storage::url($path)
     */
    public function url(string $path): string
    {
        if (!empty($this->config['url'])) {
            return rtrim($this->config['url'], '/') . '/' . ltrim($path, '/');
        }
        
        $baseUrl = rtrim(str_replace('api.php', '', $this->baseUrl), '/') . '/';
        return $baseUrl . 'uploads/' . ltrim($path, '/');
    }

    public function publicUrl(string $path, $config): string
    {
        return $this->url($path);
    }

    // --- Méthodes obligatoires pour l'interface mais optionnelles pour un usage simple ---
    // Vous pouvez les laisser vides ou retourner des erreurs si non supportées.

    public function read(string $path): string
    {
        return $this->newRequest()->get($this->publicUrl($path, new Config()))->body();
    }
    public function readStream(string $path)
    {
        $response = $this->newRequest()->get($this->publicUrl($path, new Config()));
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $response->body());
        rewind($stream);
        return $stream;
    }
    public function deleteDirectory(string $path): void
    {
    }
    public function createDirectory(string $path, Config $config): void
    {
    }
    public function setVisibility(string $path, string $visibility): void
    {
    }
    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, Visibility::PUBLIC);
    }
    public function mimeType(string $path): FileAttributes
    {
        return new FileAttributes($path);
    }
    public function lastModified(string $path): FileAttributes
    {
        return new FileAttributes($path);
    }
    public function fileSize(string $path): FileAttributes
    {
        return new FileAttributes($path);
    }
    public function listContents(string $path, bool $deep): iterable
    {
        $response = $this->newRequest()->get($this->baseUrl, [
            'action' => 'api_list',
            'key' => $this->config['key'],
            'bucket_id' => $this->config['bucket_id'],
        ]);
        
        if ($response->successful() && is_array($response->json('data'))) {
            $files = $response->json('data');
            return array_map(function ($file) {
                return new FileAttributes(
                    $file['original_name'],
                    null,
                    Visibility::PUBLIC,
                    null
                );
            }, $files);
        }

        return [];
    }
    public function move(string $source, string $destination, Config $config): void
    {
    }
    public function copy(string $source, string $destination, Config $config): void
    {
    }
    public function directoryExists(string $path): bool
    {
        return false;
    }
}
