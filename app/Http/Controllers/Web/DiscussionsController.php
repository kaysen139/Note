<?php

namespace App\Http\Controllers\Web;

use App\Base\Fitters\DiscussionFilters;
use App\Http\Resources\CommentResource;
use App\Models\Article;
use App\Models\Discussion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;

class DiscussionsController extends Controller
{

    function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
    }

    public function index(DiscussionFilters $fitters)
    {
        $discussions = $this->getDiscussions($fitters);

        // https://stackoverflow.com/questions/17159273/laravel-pagination-links-not-including-other-get-parameters
        $discussions->appends(Input::except('page'));
//        return $discussions;

        return view('discussions.index', compact('discussions'));
    }

    public function show(Discussion $discussion, Request $request)
    {
        if (!empty($discussion->slug) && $discussion->slug != $request->slug) {
            return redirect($discussion->path(), 301);
        }

        $discussion->load(['comments']);

        if (auth()->check()) {
            auth()->user()->readDiscussion($discussion);
        }

        return view('discussions.show', compact('discussion', 'bestAnswer'));

    }

    /**
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @var \App\Models\User $user
     */
    public function create()
    {
        $draft = Auth::user()->drafts()->create([
            'title' => Carbon::now()->toDateTimeString(),
            'body' => '',
            'relation_type' => Discussion::class
        ]);

        return redirect()->route('drafts.edit', $draft->ref);
    }


    public function edit(Discussion $discussion)
    {
        $draft = $discussion->currentDraft;
        if (empty($draft)) {
            $draft = Auth::user()->drafts()->create([
                'title' => $discussion->title,
                'body' => json_decode($discussion->body)->raw,
                'relation_type' => Discussion::class,
                'relation_id' => $discussion->id
            ]);
        }
        return redirect()->route('drafts.edit', $draft->ref);

    }


    protected function getDiscussions(DiscussionFilters $fitters)
    {
        $discussions = Discussion::withCount('comments')->latest()->filter($fitters);

        return $discussions->paginate(10);
    }

}
