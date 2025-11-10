<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['message' => 'Welcome to the API'];
});

require __DIR__.'/auth.php';

// SPA ルート（Vue用）
// これを追加すると、フロントのすべてのルートを index.html に返す
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '^(?!api).*$');
