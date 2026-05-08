<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class OptimizeLegacyImagesCommand extends Command
{
    protected $signature = 'images:optimize-legacy
                            {--dry-run : Solo muestra que archivos se procesarian}
                            {--force : Regenera WEBP aunque ya exista}
                            {--quality=80 : Calidad WEBP (1-100)}
                            {--max-width=1920 : Ancho maximo de salida}';

    protected $description = 'Genera versiones WEBP optimizadas de imagenes legacy sin borrar originales.';

    private int $created = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private int $scanned = 0;

    public function handle(): int
    {
        if (!function_exists('imagewebp')) {
            $this->error('GD/WebP no esta disponible en este entorno PHP.');
            return self::FAILURE;
        }

        $quality = max(1, min(100, (int) $this->option('quality')));
        $maxWidth = max(320, (int) $this->option('max-width'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $targets = [
            public_path('imagenes/Landingpage'),
            storage_path('app/public/productos'),
        ];

        foreach ($targets as $dir) {
            $this->processDirectory($dir, $quality, $maxWidth, $dryRun, $force);
        }

        $this->newLine();
        $this->info('Resumen optimizacion legacy');
        $this->line("Escaneadas: {$this->scanned}");
        $this->line("Generadas: {$this->created}");
        $this->line("Saltadas: {$this->skipped}");
        $this->line("Errores: {$this->errors}");

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processDirectory(string $dir, int $quality, int $maxWidth, bool $dryRun, bool $force): void
    {
        if (!is_dir($dir)) {
            $this->warn("Directorio no encontrado: {$dir}");
            return;
        }

        $this->info("Procesando: {$dir}");

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $sourcePath = $file->getPathname();
            $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }

            $this->scanned++;
            $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $sourcePath);
            if (!$webpPath) {
                $this->skipped++;
                continue;
            }

            if (!$force && is_file($webpPath)) {
                $this->skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line('[DRY] ' . Str::replace(base_path() . DIRECTORY_SEPARATOR, '', $sourcePath));
                $this->created++;
                continue;
            }

            try {
                $this->generateWebp($sourcePath, $webpPath, $quality, $maxWidth);
                $this->created++;
            } catch (\Throwable $e) {
                $this->errors++;
                $this->warn('Error: ' . $sourcePath . ' -> ' . $e->getMessage());
            }
        }
    }

    private function generateWebp(string $sourcePath, string $webpPath, int $quality, int $maxWidth): void
    {
        $raw = @file_get_contents($sourcePath);
        if ($raw === false) {
            throw new \RuntimeException('No se pudo leer archivo fuente.');
        }

        $image = @imagecreatefromstring($raw);
        if (!$image) {
            throw new \RuntimeException('Formato de imagen no soportado por GD.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $targetImage = $image;
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) round(($height * $newWidth) / max(1, $width));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $targetImage = $resized;
        }

        $ok = imagewebp($targetImage, $webpPath, $quality);
        if ($targetImage !== $image) {
            imagedestroy($targetImage);
        }
        imagedestroy($image);

        if (!$ok) {
            throw new \RuntimeException('Fallo al escribir WEBP.');
        }
    }
}

