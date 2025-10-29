<?php
/**
 * YouTube utility functions for extracting video IDs and generating thumbnail URLs
 */

/**
 * Extract YouTube video ID from various YouTube URL formats
 * 
 * @param string $url YouTube URL
 * @return string|false Video ID or false if not found
 */
function extractYouTubeVideoId($url) {
    // Remove any whitespace
    $url = trim($url);
    
    // Pattern for youtu.be/VIDEO_ID
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    // Pattern for youtube.com/watch?v=VIDEO_ID
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    // Pattern for youtube.com/embed/VIDEO_ID
    if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    // Pattern for youtube.com/v/VIDEO_ID
    if (preg_match('/youtube\.com\/v\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    return false;
}

/**
 * Generate YouTube thumbnail URL
 * 
 * @param string $videoId YouTube video ID
 * @param string $quality Thumbnail quality (default, mqdefault, hqdefault, sddefault, maxresdefault)
 * @return string Thumbnail URL
 */
function getYouTubeThumbnail($videoId, $quality = 'mqdefault') {
    if (empty($videoId)) {
        return 'assets/default-course.jpg';
    }
    
    $validQualities = ['default', 'mqdefault', 'hqdefault', 'sddefault', 'maxresdefault'];
    if (!in_array($quality, $validQualities)) {
        $quality = 'mqdefault';
    }
    
    return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
}

/**
 * Check if URL is a YouTube link
 * 
 * @param string $url URL to check
 * @return bool True if YouTube URL
 */
function isYouTubeUrl($url) {
    return preg_match('/(youtube\.com|youtu\.be)/i', $url);
}

/**
 * Check if URL is a Google Drive link
 * 
 * @param string $url URL to check
 * @return bool True if Google Drive URL
 */
function isGoogleDriveUrl($url) {
    return preg_match('/drive\.google\.com/i', $url);
}

/**
 * Generate Google Drive thumbnail URL
 * 
 * @param string $url Google Drive URL
 * @return string Thumbnail URL
 */
function getGoogleDriveThumbnail($url) {
    // Extract file ID from various Google Drive URL formats
    $fileId = null;
    if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
        $fileId = $matches[1];
    } elseif (preg_match('/[?&]id=([^&]+)/', $url, $matches)) {
        $fileId = $matches[1];
    }

    if ($fileId) {
        // Use direct view endpoint which is generally more reliable for public files
        // Thumbnail endpoint sometimes requires auth depending on file settings
        return "https://drive.google.com/uc?export=view&id={$fileId}";
    }
    return 'assets/default-course.jpg';
}

/**
 * Convert Google Drive file URL to a direct view URL for images
 */
function getGoogleDriveDirectView($url) {
    if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
        $fileId = $matches[1];
        return "https://drive.google.com/uc?export=view&id={$fileId}";
    }
    return $url;
}

/**
 * Resolve an image URL for thumbnail usage (supports Drive links)
 */
function resolveThumbnailImageUrl($url, $fallback = 'assets/default-course.jpg') {
    $url = trim((string)$url);
    if (!$url) return $fallback;
    if (isGoogleDriveUrl($url)) {
        return getGoogleDriveThumbnail($url);
    }
    return $url;
}

/**
 * Resolve an image URL for full display (supports Drive links)
 */
function resolveDisplayImageUrl($url, $fallback = 'assets/default-course.jpg') {
    $url = trim((string)$url);
    if (!$url) return $fallback;
    if (isGoogleDriveUrl($url)) {
        return getGoogleDriveDirectView($url);
    }
    return $url;
}

/**
 * Generate MP4 video thumbnail using HTML5 video element
 * 
 * @param string $url MP4 video URL
 * @return string Thumbnail URL or default
 */
function getMp4Thumbnail($url) {
    // For MP4 videos, we'll use a default video thumbnail
    // In a real implementation, you might want to generate thumbnails server-side
    return 'assets/default-course.jpg';
}

/**
 * Check if file is a video
 * 
 * @param string $filePath File path or URL
 * @return bool True if video file
 */
function isVideoFile($filePath) {
    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return in_array($extension, $videoExtensions);
}

/**
 * Generate video thumbnail using FFmpeg (if available)
 * 
 * @param string $videoPath Path to video file
 * @param string $thumbnailPath Path to save thumbnail
 * @return bool True if thumbnail generated successfully
 */
function generateVideoThumbnail($videoPath, $thumbnailPath) {
    // Check if FFmpeg is available
    $ffmpegPath = 'ffmpeg'; // You may need to specify full path on Windows
    
    // Create thumbnail directory if it doesn't exist
    $thumbDir = dirname($thumbnailPath);
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }
    
    // Generate thumbnail at 5 seconds into the video
    $command = "{$ffmpegPath} -i " . escapeshellarg($videoPath) . " -ss 00:00:05 -vframes 1 -q:v 2 " . escapeshellarg($thumbnailPath) . " 2>&1";
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    return $returnCode === 0 && file_exists($thumbnailPath);
}

/**
 * Check if URL is an MP4 video
 * 
 * @param string $url URL to check
 * @return bool True if MP4 URL
 */
function isMp4Url($url) {
    return preg_match('/\.mp4($|\?)/i', $url);
}

/**
 * Get course thumbnail with fallback logic
 * 
 * @param string $imagePath Course image path (could be YouTube URL, image URL, video file, etc.)
 * @param string $title Course title for alt text
 * @return array Array with 'src', 'alt', 'isVideo', and 'videoId' keys
 */
function getCourseThumbnail($imagePath, $title = '') {
    $imagePath = trim($imagePath);
    
    // If it's a YouTube URL, get the thumbnail
    if (isYouTubeUrl($imagePath)) {
        $videoId = extractYouTubeVideoId($imagePath);
        if ($videoId) {
            return [
                'src' => getYouTubeThumbnail($videoId),
                'alt' => $title ?: 'Course Thumbnail',
                'isVideo' => true,
                'videoId' => $videoId,
                'type' => 'youtube'
            ];
        }
    }
    
    // If it's a Google Drive URL, get the thumbnail
    if (isGoogleDriveUrl($imagePath)) {
        return [
            'src' => getGoogleDriveThumbnail($imagePath),
            'alt' => $title ?: 'Course Thumbnail',
            'isVideo' => true,
            'type' => 'drive'
        ];
    }
    
    // If it's an MP4 URL, use default video thumbnail
    if (isMp4Url($imagePath)) {
        return [
            'src' => 'assets/default-course.jpg', // Will show with video indicator
            'alt' => $title ?: 'Course Thumbnail',
            'isVideo' => true,
            'type' => 'mp4',
            'videoUrl' => $imagePath
        ];
    }
    
    // If it's a video file, check for existing thumbnail or generate one
    if (isVideoFile($imagePath)) {
        $videoName = pathinfo($imagePath, PATHINFO_FILENAME);
        $thumbnailPath = "assets/thumbnails/{$videoName}.jpg";
        
        // If thumbnail doesn't exist, try to generate it
        if (!file_exists($thumbnailPath)) {
            generateVideoThumbnail($imagePath, $thumbnailPath);
        }
        
        // Use generated thumbnail or fallback
        $thumbnailSrc = file_exists($thumbnailPath) ? $thumbnailPath : 'assets/default-course.jpg';
        
        return [
            'src' => $thumbnailSrc,
            'alt' => $title ?: 'Course Thumbnail',
            'isVideo' => true,
            'type' => 'video',
            'videoPath' => $imagePath
        ];
    }
    
    // If it's a direct image URL or file path
    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imagePath)) {
        return [
            'src' => $imagePath,
            'alt' => $title ?: 'Course Thumbnail',
            'isVideo' => false,
            'type' => 'image'
        ];
    }
    
    // Default fallback
    return [
        'src' => 'assets/default-course.jpg',
        'alt' => $title ?: 'Course Thumbnail',
        'isVideo' => false,
        'type' => 'default'
    ];
}
?>
