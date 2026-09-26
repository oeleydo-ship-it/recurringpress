<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CmsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'excerpt',
        'meta_title',
        'meta_description',
        'author_id',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Superadmin::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Format content for proper blog display
     */
    public function getFormattedContentAttribute()
    {
        $content = $this->content;
        
        // If content is empty, return empty string
        if (empty($content)) {
            return '';
        }
        
        // Wrap bare text lines in paragraph tags if not already wrapped
        $content = preg_replace_callback('/(<\/(?:h[1-6]|ul|ol|blockquote|pre)>\s*)([^<]+?)(?=<|$)/s', function($matches) {
            $text = trim($matches[2]);
            if (empty($text)) {
                return $matches[0];
            }
            return $matches[1] . '<p>' . $text . '</p>';
        }, $content);
        
        // Wrap content that starts with bare text
        if (!preg_match('/^\s*</', $content)) {
            $content = '<p>' . $content . '</p>';
        }
        
        // Convert Quill's default p tags to have proper spacing
        $content = str_replace(['<p><br></p>', '<p><br/></p>'], '<p>&nbsp;</p>', $content);
        
        // Ensure proper spacing between paragraphs
        $content = preg_replace('/<\/p>\s*<p>/', '</p><p>', $content);
        
        return $content;
    }
}
