<?php

// * * * * * /path/to/php /home/USERNAME/public_html/artisan queue:work --stop-when-empty

// ssh-keygen -t ed25519 -f ~/.ssh/github_ed25519
// chmod 600 ~/.ssh/github_ed25519
// cat ~/.ssh/github_ed25519.pub <- add this on github
// GIT_SSH_COMMAND="ssh -i ~/.ssh/github_ed25519 -o IdentitiesOnly=yes" git push origin main

// ln -s ../../app/Modules/{name}/assets public/modules/{name} <- symlink

// php artisan module:publish-assets <- move to public folder

// convert instagram post images to 3840x2160 adding white background
// mkdir -p output
// for f in *.jpg; do
//     ffmpeg -i "$f" \
//     -vf "scale=3840:2160:force_original_aspect_ratio=decrease,pad=3840:2160:(ow-iw)/2:(oh-ih)/2:white" \
//     -q:v 2 \
//     "output/$f"
// done

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
