<?php

namespace App\Base\Fitters;


use App\Models\User;

class ArticleFilters extends Filters
{

    protected $filters = [
        'by',
        'popular',
        'uncommented',
        'tag',
        'archive'
    ];

    protected function by($username)
    {
        $user = User::where('name', $username)->firstOrFail();

        return $this->builder->where('user_id', $user->id);
    }

    protected function popular()
    {
        $this->builder->getQuery()->orders = [];

        return $this->builder
            ->withCount('comments')
            ->orderBy('comments_count', 'desc');
    }

    protected function uncommented()
    {
        $this->builder->getQuery()->orders = [];
        return $this->builder
            ->has('comments', '=', 0);
    }

    public function tag($name)
    {
        return $this->builder->whereHas('tags', function ($query) use ($name) {
            $query->where('slug', $name)->orWhere('name', $name);
        });
    }

    public function archive($date)
    {
        dd($date);
    }

}