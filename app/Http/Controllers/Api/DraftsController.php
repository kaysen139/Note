<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DraftResource;
use App\Models\Draft;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DraftsController extends ApiController
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Draft $draft)
    {
        $lastUpdate = $draft;
        if ($draft->children()->count() > 0) {
            $lastUpdate = $draft->children()->latest()->first();
        }
        return new DraftResource($lastUpdate->load(['user', 'relation']));
    }

    public function update(Request $request, Draft $draft)
    {
        $this->validate($request, [
            'title' => 'required',
            'body' => ''
        ]);
        $fields = $request->except('_method');

        if (!$request->get('body')) {
            $fields['body'] = '';
        }

        $newDraft = $draft->children()->create($fields + [
                'user_id' => Auth::id()
            ]);
        $newDraft->relation_id = $draft->relation_id;
        $newDraft->relation_type = $draft->relation_type;
        $newDraft->save();
        return $this->message('保存成功');
    }

    // 获取7牛token
    public function getToken()
    {
        $accessKey = config('services.qiniu.accessKey');
        $secretKey = config('services.qiniu.secretKey');
        $auth = new \Qiniu\Auth($accessKey, $secretKey);
        $bucket = 'image';
        $token = $auth->uploadToken($bucket);
        return $this->success(compact('token'));
    }

}
