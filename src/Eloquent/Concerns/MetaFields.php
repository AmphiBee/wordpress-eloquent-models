<?php

namespace AmphiBee\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Builder;
use UnexpectedValueException;

/**
 * Trait HasMetaFields
 *
 * @package AmphiBee\Eloquent\Traits
 * @author Junior Grossi <juniorgro@gmail.com>
 */
trait MetaFields
{
    /**
     * @var array
     */
    protected $builtInClasses = [
        \AmphiBee\Eloquent\Model\Comment::class => \AmphiBee\Eloquent\Model\Meta\CommentMeta::class,
        \AmphiBee\Eloquent\Model\Post::class => \AmphiBee\Eloquent\Model\Meta\PostMeta::class,
        \AmphiBee\Eloquent\Model\Term::class => \AmphiBee\Eloquent\Model\Meta\TermMeta::class,
        \AmphiBee\Eloquent\Model\User::class => \AmphiBee\Eloquent\Model\Meta\UserMeta::class,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fields()
    {
        return $this->meta();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meta()
    {
        return $this->hasMany($this->getMetaClass(), $this->getMetaForeignKey());
    }

    /**
     * @return string
     * @throws \UnexpectedValueException
     */
    protected function getMetaClass()
    {
        foreach ($this->builtInClasses as $model => $meta) {
            if ($this instanceof $model) {
                return $meta;
            }
        }

        throw new UnexpectedValueException(sprintf(
            '%s must extends one of AmphiBee\Eloquent built-in models: Comment, Post, Term or User.',
            static::class
        ));
    }

    /**
     * @return string
     * @throws \UnexpectedValueException
     */
    protected function getMetaForeignKey(): string
    {
        foreach ($this->builtInClasses as $model => $meta) {
            if ($this instanceof $model) {
                return sprintf('%s_id', strtolower(class_basename($model)));
            }
        }

        throw new UnexpectedValueException(sprintf(
            '%s must extends one of AmphiBee\Eloquent built-in models: Comment, Post, Term or User.',
            static::class
        ));
    }

    /**
     * @param Builder $query
     * @param string|array $meta
     * @param mixed $value
     * @param string $operator
     * @return Builder
     */
    public function scopeHasMeta(Builder $query, $meta, $value = null, string $operator = '=')
    {
        if (!is_array($meta)) {
            $meta = [$meta => $value];
        }

        foreach ($meta as $key => $value) {
            $query->whereHas('meta', function (Builder $query) use ($key, $value, $operator) {
                if (!is_string($key)) {
                    return $query->where('meta_key', $operator, $value);
                }

                if (is_null($value)) {
                    $query->where('meta_key', $operator, $key);
                } else {
                    $query->where('meta_key', '=', $key);
                    $query->where('meta_value', $operator, $value);
                }

                return $query;
            });
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param string $key
     * @param array $value
     * @return Builder
     */
    public function scopeHasMetaIn(Builder $query, string $key, array $value)
    {
        $query->whereHas('meta', function (Builder $query) use ($key, $value) {
            return $query->where('meta_key', '=', $key)
                        ->whereIn('meta_value', $value);
        });

        return $query;
    }

    /**
     * @param Builder $query
     * @param string $meta
     * @param mixed $value
     * @return Builder
     */
    public function scopeHasMetaLike(Builder $query, $meta, $value = null)
    {
        return $this->scopeHasMeta($query, $meta, $value, 'like');
    }


    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function saveField($key, $value)
    {
        return $this->saveMeta($key, $value);
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @return bool
     */
    public function saveMeta($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->saveOneMeta($k, $v);
            }
            $this->load('meta');

            return true;
        }

        return $this->saveOneMeta($key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    private function saveOneMeta($key, $value)
    {
        $meta = $this->meta()->where('meta_key', $key)
            ->firstOrNew(['meta_key' => $key]);

        $result = $meta->fill(['meta_value' => $value])->save();
        $this->load('meta');

        return $result;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createField($key, $value)
    {
        return $this->createMeta($key, $value);
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Support\Collection
     */
    public function createMeta($key, $value = null)
    {
        if (is_array($key)) {
            return collect($key)->map(function ($value, $key) {
                return $this->createOneMeta($key, $value);
            });
        }

        return $this->createOneMeta($key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function createOneMeta($key, $value)
    {
        $meta =  $this->meta()->create([
            'meta_key' => $key,
            'meta_value' => $value,
        ]);
        $this->load('meta');

        return $meta;
    }

    /**
     * @param string $attribute
     * @return mixed|null
     */
    public function getMeta($attribute)
    {
        return $this->meta->{$attribute} ?: null;
    }
}
