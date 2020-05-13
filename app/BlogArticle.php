<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * App\BlogArticle
 *
 * @property int $id
 * @property string $title
 * @property string $text
 * @property string $url
 * @property int $views
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $preview
 * @property string $text_preview
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\BlogView[] $registeredViews
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle wherePreview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereTextPreview($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\BlogArticle whereViews($value)
 * @mixin \Eloquent
 */
class BlogArticle extends Model
{
    public function registeredViews()
    {
        return $this->hasMany(BlogView::class, 'article_id', 'id');
    }
}
