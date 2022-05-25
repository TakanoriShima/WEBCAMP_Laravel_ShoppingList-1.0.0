<?php
declare(strict_types=1);
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShoppingListPostRequest;
use App\Models\ShoppingList as ShoppingListModel;
use App\Models\CompletedShoppingList as CompletedShoppingListModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShoppingListController extends Controller
{
    /**
     * 一覧用の Illuminate\Database\Eloquent\Builder インスタンスの取得
     */
    protected function getListBuilder()
    {
        return ShoppingListModel::where('user_id', Auth::id())
                     ->orderBy('name');
    }

    /**
     * 「買うもの」一覧ページ を表示する
     * 
     * @return \Illuminate\View\View
     */
    public function list()
    {
        // 1Page辺りの表示アイテム数を設定
        $per_page = 20;

        // 一覧の取得
        $list = $this->getListBuilder()
                     ->paginate($per_page);
        //
        return view('shopping_list.list', ['list' => $list]);
    }

    /**
     * 「買うもの」の新規登録
     */
    public function register(ShoppingListPostRequest $request)
    {
        // validate済みのデータの取得
        $datum = $request->validated();

        // user_id の追加
        $datum['user_id'] = Auth::id();

        // テーブルへのINSERT
        try {
            $r = ShoppingListModel::create($datum);
        } catch(\Throwable $e) {
            // XXX 本当はログに書く等の処理をする。今回は一端「出力する」だけ
            echo $e->getMessage();
            exit;
        }

        // 「買うもの」登録成功
        $request->session()->flash('front.shopping_list_register_success', true);

        //
        return redirect(route('front.list'));
    }

    /**
     * 「単一の「買うもの」」Modelの取得
     */
    protected function getShoppingList($shopping_list_id)
    {
        // shopping_list_idのレコードを取得する
        $shopping_list = ShoppingListModel::find($shopping_list_id);
        if ($shopping_list === null) {
            return null;
        }
        // 本人以外の「買うもの」ならNGとする
        if ($shopping_list->user_id !== Auth::id()) {
            return null;
        }
        //
        return $shopping_list;
    }

    /**
     * 削除処理
     */
    public function delete(Request $request, $shopping_list_id)
    {
        // shopping_list_idのレコードを取得する
        $shopping_list = $this->getShoppingList($shopping_list_id);

        // 「買うもの」を削除する
        if ($shopping_list !== null) {
            $shopping_list->delete();
            $request->session()->flash('front.shopping_list_delete_success', true);
        }

        // 一覧に遷移する
        return redirect(route('front.list'));
    }

    /**
     * 「買うもの」の完了
     */
    public function complete(Request $request, $shopping_list_id)
    {
        /* 「買うもの」を完了テーブルに移動させる */
        try {
            // トランザクション開始
            DB::beginTransaction();

            // shopping_list_idのレコードを取得する
            $shopping_list = $this->getShoppingList($shopping_list_id);
            if ($shopping_list === null) {
                // shopping_list_idが不正なのでトランザクション終了
                throw new \Exception('');
            }

            // shopping_list側を削除する
            $shopping_list->delete();
//var_dump($shopping_list->toArray()); exit;

            // completed_shopping_lists側にinsertする
            $dask_datum = $shopping_list->toArray();
            unset($dask_datum['created_at']);
            unset($dask_datum['updated_at']);
            $r = CompletedShoppingListModel::create($dask_datum);
            if ($r === null) {
                // insertで失敗したのでトランザクション終了
                throw new \Exception('');
            }
//echo '処理成功'; exit;

            // トランザクション終了
            DB::commit();
            // 完了メッセージ出力
            $request->session()->flash('front.shopping_list_completed_success', true);
        } catch(\Throwable $e) {
//var_dump($e->getMessage()); exit;
            // トランザクション異常終了
            DB::rollBack();
            // 完了失敗メッセージ出力
            $request->session()->flash('front.shopping_list_completed_failure', true);
        }

        // 一覧に遷移する
        return redirect(route('front.list'));
    }

}