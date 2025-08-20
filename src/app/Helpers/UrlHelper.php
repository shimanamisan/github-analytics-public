<?php

namespace App\Helpers;

class UrlHelper
{
    /**
     * カスタムドメインとポートでURLを生成
     */
    public static function urlWithCustomDomain($path, $domain = 'api.example.com', $port = 8090, $scheme = 'http')
    {
        // パスの先頭のスラッシュを除去
        $path = ltrim($path, '/');
        
        return "{$scheme}://{$domain}:{$port}/{$path}";
    }
    
    /**
     * 基本的なURL生成（ポート付与）
     */
    public static function url($path = '')
    {
        $path = ltrim($path, '/');
        $domain = env('CUSTOM_DOMAIN', 'api.example.com');
        $port = env('CUSTOM_PORT', 8090);
        return self::urlWithCustomDomain($path, $domain, $port);
    }
    
    /**
     * 管理画面用のURL生成
     */
    public static function adminUrl($path = '')
    {
        $fullPath = $path ? "admin/{$path}" : 'admin';
        return self::url($fullPath);
    }
    
    /**
     * GitHub表示画面用のURL生成
     */
    public static function githubUrl($path = 'views')
    {
        return self::url("github/{$path}");
    }
    
    /**
     * 設定からドメインとポートを取得
     */
    public static function getCustomDomain()
    {
        return env('CUSTOM_DOMAIN', 'api.example.com');
    }
    
    /**
     * 設定からポートを取得
     */
    public static function getCustomPort()
    {
        return env('CUSTOM_PORT', 8090);
    }
}
