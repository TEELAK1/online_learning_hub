<?php
/**
 * Performance Optimization Helper
 * Provides caching, minification, and optimization functions
 */
class PerformanceOptimizer {
    
    private static $cacheDir = null;
    private static $cacheTime = 3600; // 1 hour default
    
    public static function init() {
        self::$cacheDir = __DIR__ . '/../cache/';
        
        // Ensure cache directory exists
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    
    /**
     * Simple file-based caching
     */
    public static function cache($key, $data, $ttl = null) {
        if (self::$cacheDir === null) {
            self::init();
        }
        
        $ttl = $ttl ?? self::$cacheTime;
        $file = self::$cacheDir . md5($key) . '.cache';
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($file, serialize($cacheData));
    }
    
    /**
     * Get cached data
     */
    public static function getCached($key) {
        if (self::$cacheDir === null) {
            self::init();
        }
        
        $file = self::$cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $cacheData = unserialize(file_get_contents($file));
        
        if ($cacheData['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $cacheData['data'];
    }
    
    /**
     * Clear cache
     */
    public static function clearCache($pattern = '*') {
        if (self::$cacheDir === null) {
            self::init();
        }
        
        $files = glob(self::$cacheDir . $pattern . '.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Minify CSS
     */
    public static function minifyCSS($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        // Remove unnecessary spaces
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
        
        return trim($css);
    }
    
    /**
     * Minify JavaScript (basic)
     */
    public static function minifyJS($js) {
        // Remove comments
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        $js = preg_replace('!//.*$!m', '', $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        $js = preg_replace('/\s*([{}();,])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Optimize images (basic compression)
     */
    public static function optimizeImage($sourcePath, $destPath, $quality = 85) {
        $imageInfo = getimagesize($sourcePath);
        
        if (!$imageInfo) {
            return false;
        }
        
        $mimeType = $imageInfo['mime'];
        
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                imagejpeg($image, $destPath, $quality);
                break;
                
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                imagepng($image, $destPath, 9); // Maximum compression
                break;
                
            case 'image/gif':
                $image = imagecreatefromgif($sourcePath);
                imagegif($image, $destPath);
                break;
                
            default:
                return false;
        }
        
        imagedestroy($image);
        return true;
    }
    
    /**
     * Database query optimization - prepare and cache
     */
    public static function optimizedQuery($db, $query, $params = [], $cacheKey = null, $ttl = 3600) {
        if ($cacheKey) {
            $cached = self::getCached($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Query preparation failed: " . $db->error);
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        $stmt->close();
        
        if ($cacheKey) {
            self::cache($cacheKey, $data, $ttl);
        }
        
        return $data;
    }
    
    /**
     * Lazy load images
     */
    public static function lazyLoadImage($src, $alt = '', $class = '') {
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg width="400" height="300" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f0f0f0"/></svg>'
        );
        
        return sprintf(
            '<img src="%s" data-src="%s" alt="%s" class="lazy-load %s" loading="lazy">',
            $placeholder,
            htmlspecialchars($src),
            htmlspecialchars($alt),
            $class
        );
    }
    
    /**
     * Compress output buffer
     */
    public static function compressOutput() {
        if (!ob_get_level()) {
            ob_start();
        }
        
        if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            ob_start('ob_gzhandler');
        }
    }
    
    /**
     * Set browser caching headers
     */
    public static function setCacheHeaders($maxAge = 3600) {
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('ETag: "' . md5_file(__FILE__) . '"');
    }
    
    /**
     * Generate critical CSS (simplified)
     */
    public static function generateCriticalCSS($css, $selectors = []) {
        if (empty($selectors)) {
            return '';
        }
        
        $critical = '';
        $lines = explode("\n", $css);
        $inCriticalBlock = false;
        
        foreach ($lines as $line) {
            foreach ($selectors as $selector) {
                if (strpos($line, $selector) !== false) {
                    $inCriticalBlock = true;
                    break;
                }
            }
            
            if ($inCriticalBlock) {
                $critical .= $line . "\n";
                
                if (trim($line) === '}') {
                    $inCriticalBlock = false;
                }
            }
        }
        
        return $critical;
    }
}

// Initialize performance optimizer
PerformanceOptimizer::init();
?>
