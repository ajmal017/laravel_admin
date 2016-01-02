<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gate;
use App\Article;
use App\Img;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Repositories\ArticleRepository;

class ArticleController extends Controller
{
    /**
    * The task repository instance.
    *
    * @var TaskRepository
    */
    protected $articles;

    /**
    * Create a new controller instance.
    *
    * @param  TaskRepository  $tasks
    * @return void
    */
    public function __construct(ArticleRepository $articles)
    {
        $this->middleware('auth',['except' => [
            'index', 'show',
        ]]);

        $this->articles = $articles;
    }


    public function index($id = 1)
    {
        return view('articles.index', [
            'articles' => $this->articles->for_guest($id)
        ]);
    }


    public function user_index(Request $request)
    {
        return view('articles.index', [
            'articles' => $this->articles->forUser($request->user()),
        ]);
    }


    public function show($id)
    {
        $article = Article::find($id);

        return view('articles.show',['article'=>$article]);
    }


    public function edit($id)
    {
        $article = Article::find($id);

        return view('articles.edit',['article'=>$article]);
    }


    public function create(Request $request)
    {
        return view('articles.create');
    }


    public function store(Request $request)
    {
        $messages = [
            'title.required' => '标题不能为空',
            'title.unique' => '标题不能重复',
            'title.max' => '标题不能大于:max位',
            'title.min' => '标题不能小于:min位',
            'content.required' => '内容不能为空',
            'publish_at.required' => '发布时间不能为空',
        ];
        $this->validate($request, [
            'title' => 'required|min:5|max:255',
            'content' => 'required',
            'photo' => 'max:1024',
            'publish_at' => 'required',
        ],$messages);

        $request->user()->articles()->create([
            'title' => $request->title,
            'content' => $request->content,
            'thumbnail' => $request->photo,
            'published_at' => $request->publish_at,
        ]);

        Session()->flash('status', 'Article create was successful!');

        return redirect('/articles');
    }


    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);
        if (Gate::denies('article_authorize', $article)) {
            return "authorize fails";
        }

        $messages = [
            'title.required' => '标题不能为空',
            'title.unique' => '标题不能重复',
            'title.max' => '标题不能小于:max位',
            'title.min' => '标题不能小于:min位',
            'content.required' => '内容不能为空',
        ];
        $this->validate($request, [
            'title' => 'required|min:5|max:255',
            'content' => 'required',
        ],$messages);

        $article = Article::find($id);
        $article->title = $request->title;
        $article->content = $request->content;
        $article->save();

        Session()->flash('status', 'Article update was successful!');

        return redirect('/articles');
    }


    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        if (Gate::denies('article_authorize', $article)) {
            return "authorize fails";
        }

        Article::destroy($id);

        return redirect('/articles');
    }

    public function fileUpload(Request $request)
    {
        if ($request->hasFile('file'))//文件是否上传
        {
            $messages = [
                'photo.image' => '上传文件必须是图片',
                'photo.max' => '上传文件不能大于:maxkb',
            ];
            $this->validate($request, [
                'photo' => 'image|max:1000'//kilobytes
            ],$messages);
            // return "true";

            if ($request->file('file')->isValid())//上传文件是否有效
            {
                $file_pre = getdate()[0];//取得当前时间戳
                $file_suffix = substr(strchr($request->file('file')->getMimeType(),"/"),1);//取得文件后缀
                $destinationPath = 'uploads';//上传路径
                $fileName = $file_pre.'.'.$file_suffix;//上传文件名
                $request->file('file')->move($destinationPath, $fileName);

                $img = new Img;
                $img->name = $fileName;
                $img->save();

                Session()->flash('img',$fileName);

                // return view('/admin/fileselect');

                return $fileName;
            } else {
                return "上传文件无效！";
            }
        } else {
            return "文件上传失败！";
        }
    }


}
