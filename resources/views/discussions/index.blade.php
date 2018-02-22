@extends('layouts.app')

@section('title','评论首页')


@section('content')

    <div class="columns">

        <div class="column is-9">
            <div class="card">
                <div class="card-content nopadding">
                    <div class="content">
                        @forelse($discussions as $discussion)
                            @include('discussions.partials.discussion')
                        @empty
                            <div class="empty-block">没有文章数据</div>
                        @endforelse

                        <div class="center-block">
                            {{ $discussions->links('vendor.pagination.default') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>


        <div class="column">
            <div class="card">
                <div class="card-content">
                    <div class="content">

                        <div class="columns is-multiline">
                            <div class="column is-12 level-item has-text-centered">
                                <button class="button is-primary" style="width: 100%">提问</button>
                            </div>

                            <aside class="menu">
                                <p class="menu-label">
                                    选择过滤条件
                                </p>
                                <ul class="menu-list">
                                    <li><a href="/discussions">所有问题</a></li>
                                    <li><a href="/discussions?by={{ Auth::user()->name }}">我的问题</a></li>
                                    <li><a href="/discussions?popular=1">热门问题</a></li>
                                    <li><a href="/discussions?uncommented=1">零回复问题</a></li>
                                </ul>
                                <p class="menu-label">
                                    通过分类过滤
                                </p>
                                <ul class="menu-list">
                                    <li><a>Payments</a></li>
                                    <li><a>Transfers</a></li>
                                    <li><a>Balance</a></li>
                                </ul>
                            </aside>

                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>


@endsection