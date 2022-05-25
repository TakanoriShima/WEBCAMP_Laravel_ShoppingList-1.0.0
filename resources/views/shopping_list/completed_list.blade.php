@extends('layout')

{{-- タイトル --}}
@section('title')(購入済み「買うもの」一覧画面)@endsection

{{-- メインコンテンツ --}}
@section('contets')
        <h1>購入済み「買うもの」一覧</h1>
        <a href="/shopping_list/list">「買うもの」一覧に戻る</a><br>
        <table border="1">
        <tr>
            <th>「買うもの」名
            <th>購入日
@foreach ($list as $shopping_item)
        <tr>
            <td>{{ $shopping_item->name }}
            <td>{{ $shopping_item->created_at->format('Y/m/d') }}
@endforeach
        </table>
        <!-- ページネーション -->
        {{-- {{ $list->links() }} --}}
        現在 {{ $list->currentPage() }} ページ目<br>
        @if ($list->onFirstPage() === false)
            <a href="/completed_shopping_list/list">最初のページ</a>
        @else
            最初のページ
        @endif
        /
        @if ($list->previousPageUrl() !== null)
            <a href="{{ $list->previousPageUrl() }}">前に戻る</a>
        @else
            前に戻る
        @endif
        /
        @if ($list->nextPageUrl() !== null)
            <a href="{{ $list->nextPageUrl() }}">次に進む</a>
        @else
            次に進む
        @endif
        <br>
        <hr>
        <menu label="リンク">
        <a href="/logout">ログアウト</a><br>
        </menu>
@endsection
