<?php

/**
 * Single Zip Deploy Helper for Laravel on cPanel
 * SIPD-MWM Deployment Automation
 */

declare(strict_types=1);

// Prevent timeouts and memory issues during extraction
ini_set('memory_limit', '512M');
set_time_limit(300);

header('Content-Type: application/json; charset=utf-8');

// Secret key for authorization
$expectedKey = getenv('DEPLOY_SECRET_KEY') ?: 'sipd-mwm-deploy-key-2026';
$providedKey = $_GET['key'] ?? $_SERVER['HTTP_X_DEPLOY_KEY'] ?? '';

if (!hash_equals($expectedKey, (string) $providedKey)) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized: Invalid deploy key.',
    ], JSON_PRETTY_PRINT);
    exit;
}

// Check if a zip file was uploaded directly via POST
if (isset($_FILES['deploy_zip']) && $_FILES['deploy_zip']['error'] === UPLOAD_ERR_OK) {
    $uploadedPath = __DIR__ . '/../deploy.zip';
    if (move_uploaded_file($_FILES['deploy_zip']['tmp_name'], $uploadedPath)) {
        $zipPath = $uploadedPath;
    }
}

// Possible locations for deploy.zip if not uploaded via POST
if (!isset($zipPath)) {
    $possibleZipPaths = [
        __DIR__ . '/../deploy.zip',              // /home/user/deploy.zip (Recommended)
        __DIR__ . '/deploy.zip',                 // /home/user/public_html/deploy.zip
        __DIR__ . '/../laravel-app/deploy.zip',  // /home/user/laravel-app/deploy.zip
    ];

    $zipPath = null;
    foreach ($possibleZipPaths as $path) {
        if (file_exists($path)) {
            $zipPath = $path;
            break;
        }
    }

    if (!$zipPath) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'deploy.zip not found in any expected location.',
            'checked_paths' => $possibleZipPaths,
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

// Validate that deploy.zip is not empty or corrupted (must be at least 10KB)
if (filesize($zipPath) < 10240) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'deploy.zip is corrupted or incomplete (size: ' . filesize($zipPath) . ' bytes). Upload may have been interrupted.',
    ], JSON_PRETTY_PRINT);
    exit;
}


// Define destination directories
$publicDir = realpath(__DIR__) ?: __DIR__;
$coreDir = realpath(__DIR__ . '/../laravel-app') ?: (__DIR__ . '/../laravel-app');

if (!is_dir($coreDir)) {
    mkdir($coreDir, 0755, true);
}

// Open and extract ZIP
$zip = new ZipArchive();
$openResult = $zip->open($zipPath);

if ($openResult !== true) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to open deploy.zip (Error code: ' . $openResult . ')',
    ], JSON_PRETTY_PRINT);
    exit;
}

$extractedCount = 0;
$errors = [];

/**
 * Safely merge new .htaccess with existing cPanel PHP handlers and configurations.
 */
function mergeHtaccess(string $existingContent, string $newContent): string
{
    $cpanelBlocks = [];

    // 1. Standard cPanel generated handlers & directives enclosed in comment tags
    if (preg_match_all('/# (?:php -- )?BEGIN cPanel-generated.*?(?:# (?:php -- )?END cPanel-generated[^\r\n]*)/is', $existingContent, $matches)) {
        foreach ($matches[0] as $block) {
            $trimmed = trim($block);
            if (!empty($trimmed) && !in_array($trimmed, $cpanelBlocks, true)) {
                $cpanelBlocks[] = $trimmed;
            }
        }
    }

    // 2. MultiPHP ea-php / alt-php MIME handler (only if NOT already inside an extracted block)
    if (preg_match_all('/<IfModule\s+mime_module>\s*AddHandler\s+application\/x-httpd-(?:ea|alt)-php\d+\s+.*?<\/IfModule>/is', $existingContent, $matches)) {
        foreach ($matches[0] as $block) {
            $trimmed = trim($block);
            $alreadyIncluded = false;
            foreach ($cpanelBlocks as $existingBlock) {
                if (str_contains($existingBlock, $trimmed)) {
                    $alreadyIncluded = true;
                    break;
                }
            }
            if (!$alreadyIncluded && !in_array($trimmed, $cpanelBlocks, true)) {
                $cpanelBlocks[] = $trimmed;
            }
        }
    }

    // 3. LiteSpeed / CloudLinux lsphp handler (only if NOT already inside an extracted block)
    if (preg_match_all('/<FilesMatch\s+["\'].*?php.*?["\']>\s*SetHandler\s+application\/x-lsphp\d+.*?<\/FilesMatch>/is', $existingContent, $matches)) {
        foreach ($matches[0] as $block) {
            $trimmed = trim($block);
            $alreadyIncluded = false;
            foreach ($cpanelBlocks as $existingBlock) {
                if (str_contains($existingBlock, $trimmed)) {
                    $alreadyIncluded = true;
                    break;
                }
            }
            if (!$alreadyIncluded && !in_array($trimmed, $cpanelBlocks, true)) {
                $cpanelBlocks[] = $trimmed;
            }
        }
    }

    // If existing .htaccess has cPanel blocks, preserve them at the top
    if (!empty($cpanelBlocks)) {
        $blocksToPrepend = [];
        foreach ($cpanelBlocks as $b) {
            if (!str_contains($newContent, $b)) {
                $blocksToPrepend[] = $b;
            }
        }
        if (!empty($blocksToPrepend)) {
            return implode("\n\n", $blocksToPrepend) . "\n\n" . $newContent;
        }
    }

    // Fallback: If no cPanel block was found in existing or new content, ensure ea-php82 is set
    if (!str_contains($newContent, 'x-httpd-ea-php') && !str_contains($newContent, 'x-httpd-alt-php') && !str_contains($newContent, 'x-lsphp')) {
        $defaultHandler = "# php -- BEGIN cPanel-generated handler, do not edit\n"
            . "# Set the “ea-php82” package as the default “PHP” programming language.\n"
            . "<IfModule mime_module>\n"
            . "  AddHandler application/x-httpd-ea-php82 .php .php8 .phtml\n"
            . "</IfModule>\n"
            . "# php -- END cPanel-generated handler, do not edit\n\n";

        return $defaultHandler . $newContent;
    }

    return $newContent;
}

for ($i = 0; $i < $zip->numFiles; $i++) {
    $entryName = $zip->getNameIndex($i);

    // Prevent directory traversal attacks
    if (str_contains($entryName, '..') || str_starts_with($entryName, '/')) {
        continue;
    }

    $isDir = str_ends_with($entryName, '/');
    $targetPath = null;
    $relativePath = null;

    if (str_starts_with($entryName, 'core/')) {
        $relativePath = substr($entryName, 5);
        if ($relativePath === '') continue;
        $targetPath = $coreDir . '/' . $relativePath;
    } elseif (str_starts_with($entryName, 'public/')) {
        $relativePath = substr($entryName, 7);
        if ($relativePath === '') continue;
        $targetPath = $publicDir . '/' . $relativePath;
    } else {
        // Fallback to core directory
        $targetPath = $coreDir . '/' . $entryName;
    }

    if ($isDir) {
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }
    } else {
        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        $stream = $zip->getStream($entryName);
        if ($stream) {
            // Khusus file .htaccess: Lindungi dan gabungkan konfigurasi cPanel MultiPHP yang ada di server
            if ($relativePath === '.htaccess' && file_exists($targetPath)) {
                $existingContent = (string) file_get_contents($targetPath);
                $newContent = (string) stream_get_contents($stream);
                $finalContent = mergeHtaccess($existingContent, $newContent);

                if (file_put_contents($targetPath, $finalContent) !== false) {
                    $extractedCount++;
                } else {
                    $errors[] = "Failed to write: $targetPath";
                }
            } else {
                $dest = fopen($targetPath, 'wb');
                if ($dest) {
                    stream_copy_to_stream($stream, $dest);
                    fclose($dest);
                    $extractedCount++;
                } else {
                    $errors[] = "Failed to write: $targetPath";
                }
            }
            fclose($stream);
        }
    }
}

$zip->close();

// Delete deploy.zip after successful extraction
@unlink($zipPath);

// Execute Laravel Artisan Maintenance & Optimizations
$artisanResults = [];
$autoloadPath = $coreDir . '/vendor/autoload.php';
$bootstrapPath = $coreDir . '/bootstrap/app.php';

if (file_exists($autoloadPath) && file_exists($bootstrapPath)) {
    try {
        require_once $autoloadPath;
        /** @var \Illuminate\Foundation\Application $app */
        $app = require_once $bootstrapPath;

        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

        // Run migrations
        $kernel->call('migrate', ['--force' => true]);
        $artisanResults['migrate'] = trim($kernel->output());

        // Clear and rebuild caches
        $kernel->call('optimize:clear');
        $kernel->call('config:cache');
        $kernel->call('route:cache');
        $kernel->call('view:cache');
        $artisanResults['cache'] = 'Config, route, and view caches refreshed.';

        // Ensure storage symlink exists
        $storageSymlink = $publicDir . '/storage';
        $storageTarget = $coreDir . '/storage/app/public';
        if (!file_exists($storageSymlink) && is_dir($storageTarget)) {
            @symlink($storageTarget, $storageSymlink);
            $artisanResults['storage_link'] = 'Storage symlink created.';
        }
    } catch (\Throwable $e) {
        $artisanResults['error'] = $e->getMessage();
    }
}

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Deployment package extracted and executed successfully.',
    'files_extracted' => $extractedCount,
    'artisan' => $artisanResults,
    'errors' => $errors,
    'timestamp' => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);
