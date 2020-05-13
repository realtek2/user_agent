<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BlogArticle;
use App\BlogComment;
use App\BlogView;
use App\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Browser;
use App\Custom\Helpers;
use Telegram\Bot\Api;
use Telegram\Bot\HttpClients\GuzzleHttpClient as TGGuzzleHttpClient;
use GuzzleHttp\Client as GuzzleHttpClient;
use Telegram\Bot\Laravel\Facades\Telegram;

class BlogController extends Controller
{
    public function mailing( Request $request, $id ){
        $scriptTime = microtime(true);
        if( !Auth::check() ){
            return abort(404);
        }
        $administrator = Auth::user();
        if( $administrator->role_id !== 1 ){///// role_id????????
            return abort(404);
        }
        $article = BlogArticle::findOrFail($id);

        /*$client = new TGGuzzleHttpClient(
            new GuzzleHttpClient([
                'verify' => false,
                'proxy' => '159.69.206.106:443'
            ])
        );*/
        $token = Telegram::getAccessToken();
        $telegram = new Api( $token/*, false, $client*/ );

        $users = User::all();
        foreach( $users as $user ){
            $token = Helpers::genToken( $user );
            //Log::info($token);
            $url = route('blog:article', [
                'url'        => $article->url,
                'token'      => $token,
                'utm_source' => 'telegram'
            ]);

            $message = $article->title."\n".$url."\n\nНикому не сообщайте вашу ссылку для авторизации.";
            $sm = [ 'chat_id' => $user->name, 'text' => $message, 'caption' => $message];
            try{
                $telegram->sendMessage( $sm );
            }catch( \Exception $e ){
                Log::error( ['tg_err', $user->name] );
                Log::error( $e );
            }
        }
        return back()->with([
            'message'    => "Сообщение отправлено ".$users->count()." пользователям. Время выполнения - ".(microtime(true) - $scriptTime),
            'alert-type' => 'success',
        ]);
    }
    public function deleteComment( Request $request, $id ){
        if( !Auth::check() ){
            return abort(404);
        }
        $comment = BlogComment::findOrFail($id);
        $user    = Auth::user();
        if( $user->role_id !== 1 || $comment->user_id !== $user->id ){///// role_id????????
            return abort(404);
        }
        $comment->delete();
        return redirect()->back();
    }
    public function addComment( Request $request, $url ){
        if( !Auth::check() ){
            return abort(404);
        }
        $article = BlogArticle::where('url', $url)->first();
        if( !$article ){
            return abort(404);
        }
        $user = Auth::user();
        $this->validate($request, [
            'comment_text' => 'required|string|min:2|max:1000'
        ], config('custom.validation'));

        $comment = new BlogComment();
        $comment->text       = $request['comment_text'];
        $comment->user_id    = $user->id;
        $comment->article_id = $article->id;
        $comment->save();

        return redirect()->back();
    }
    public function showBlog( Request $request ){
        if( !Auth::check() ){
            return abort(404);
        }
        $articles = BlogArticle::orderBy('created_at', 'desc')->get();
        return view( 'blog.index', ['articles' => $articles] );
    }
    public function showArticle( Request $request, $url ){
        if( !Auth::check() ){
            return abort(404);
        }
        $article = BlogArticle::where('url', $url)->first();
        if( !$article ){
            abort(404);
        }
        // ищем просмотр
        $view = BlogView::where([
            'user_id'    => Auth::user()->id,
            'article_id' => $article->id
        ])->first();
        // если нет, то создаем и увеличиваем счетчик
        if( !$view ){
            $view = new BlogView();
            $view->user_id    = Auth::user()->id;
            $view->article_id = $article->id;
            if( $request->has('utm_source') ){
                $view->source = $request->only('utm_source')['utm_source'];
            }
            $view->save();
            $article->increment( 'views' );
        }
        $comments = BlogComment::select('blog_comments.*')->withUser()->where('article_id', $article->id)
            ->orderBy('created_at', 'desc')->get();
        return view( 'blog.article', [
            'article'  => $article,
            'comments' => $comments
        ] );
    }
}
