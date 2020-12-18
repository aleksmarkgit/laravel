<?php

/* Created by Xenial */

namespace app\Http\Controllers\Cabinet\Control;

use App\Http\Controllers\Controller;

# Facades
use Illuminate\Support\Facades\DB;

# Models
use App\Models\Category;
use App\Models\Article;
# use App\Models\CategoryArticle;
use Carbon\Carbon;

# Requests
use Illuminate\Http\Request;
use App\Http\Requests\ArticleRequest;

use Illuminate\Validation\ValidationException;

class ArticlesController extends Controller
{
    public function __construct()
    {
        $this->middleware('root');
    }

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    # Вывод Статей
    public function index()
    {
        $objArticle = Article::getInstance();
        $articles = $objArticle->get();

        $params = [
            'active'    => 'Articles',
            'title'     => 'Articles',
            'articles'  => $articles,
        ];

        return view('cabinet.articles.show', $params);

    } # END index()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    # Добавление Статей (web-форма)
    public function add()
    {
        $objCategory = Category::getInstance();
        # Выбираем все Категории
        $categories = $objCategory->get();

        #dd($categories);

        $params = [
            'active'    => 'Articles',
            'title'     => 'Article Add',
            'categories'  => $categories,
        ];

        return view('cabinet.articles.add', $params);
    } # END add()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    # Добавление Статей (Request)
    # ------------------------------------------------------------
    # ››› php artisan make:request ArticleRequest ----------------
    # ------------------------------------------------------------
    public function addRequest(ArticleRequest $request)
    {
#        dd($request);

        # dd($request->input('full_text'));

        $objArticle = Article::getInstance();

        # ⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝
        # Перед добавлением данных в БД, нужно обработать изображение / -я, если они были загружены ⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝

        # Получить last запись в таблицу articles
        $last = $objArticle->latest()->first();

        # dd($last);

        # Если это первая статья - не может быть выполнен :: latest()->first()
        if($last === NULL) {

            # dd('*');

            $results = DB::select("SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'poly' AND TABLE_NAME = 'articles'");
            $ai = $results[0]->AUTO_INCREMENT; $ai = ($ai-1); # AI даёт следующий за текущим ключ (исп. для правильного имени каждой отдельной папки)

            if($ai == 0) {
                $last['id'] = 1;
                $last['first'] = true;
                /* for local */
                $id = 1;
            } else {
                $last['id'] = $ai;
                $last['first'] = false;
                /* for local */
                $id = $ai;
            }

        } else {

            $results = DB::select("SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'poly' AND TABLE_NAME = 'articles'");
            $ai = $results[0]->AUTO_INCREMENT; $ai = ($ai-1); # AI даёт следующий за текущим ключ (исп. для правильного имени каждой отдельной папки)

            $last['id'] = $ai;
            $last['first'] = false;

            /* for local */
            $id = $ai;
            $id = $id+1;
        }

        # dd($last);

        $result = _editorParseAdd($last, $request->input('full_text'));

        # dd($result);

        /**/ $string = NULL; /* [Var not defined] */
        /**/ $ipath = NULL; /* [Var not defined] */
        if(is_array($result)) {

            # Всегда будет array - если явно не был return false или 'empty',
            # однако (возможно) есть смысл проверить поле string на пустоту,
            # в этом случае, фактически данные не должны быть добавлены,
            # также как и мы не должны дойти до такого действия,
            # т.к. у нас проверка на длинну в Request, и 3 проверки на пустоту в самой функции _editorParse()

            if($result['string'] == '') { } # Данные full_text -→ не введены - удалить все другие добавленные данные: title, author и прочее (не исполняем)

            /*
                array:3
                        img => array:1
                                        0 => "Flowers.jpg"

                        base => array:1
                                        0 => "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQA...SAMEbA"

                        string =>   <p>yui</p>

                                    <p><img style="width: 256px;" src="38/Flowers.jpg" alt="Flowers"></p>

                                    <p><br></p>
            */

            # Прежде чем идти далее, нам нужно провести пользовательскую валидацию ---------
            # чтобы кол-во символов имени изображения не превышало 100 ---------------------
            # Пользовательская валидация на длинну имени изображения - не более 100 символов
            foreach($result['img'] as $val) {
                if(mb_strlen($val) > 100) {
                    return back()->with('error', trans('messages.cabinet.articles.longLength'));
                }
            }
            unset($val);

            # ------------------------------------------------------------------------------

            # ⚑ Вхождение точки кроме как в расширении - вызывает неверную интерпретацию файла

            if($result['img'] == NULL) {

            } else {
                $s = $result['img'][0]; $s = (string)$s;
                $s = strrev($s);
                $dpos = strpos($s, '.');
                $s[$dpos] = '*';
                $s = str_replace('.', 'x', $s);
                $s = str_replace('/', 'x', $s);
                $s = str_replace('\\', 'x', $s);
                $s = str_replace('_', 'x', $s);
                $s = str_replace('-', 'x', $s);
                $s = str_replace('@', 'x', $s);
                $s[$dpos] = '.';
                $result['img'][0] = strrev($s);
            }

            # ------------------------------------------------------------------------------

            # Разграничить массив на массив и строку для записи $result['string']
            $string = $result['string'];

            # Во-первых удалим проблемную строку / строки
            $string = str_replace('<p><br></p>', '', $string);
            $string = str_replace('<br>', '', $string);

            $tmpArr = array('img' => $result['img'], 'base' => $result['base']);

            if($result['img'] == NULL) {
                $ipath = NULL;
            } else {
                $ipath = $result['img'][0];
            }

            unset($result);

            # Base 64 Encode & Save Images
            # $result = _base64decode($last, $tmpArr);

        } else {

            /**/ $tmpArr = NULL; /* [Var not defined] Fix bug на условии вызова функции _base64decode() */

            # Если xml parse не выполнен
            if($result === 'empty') { return back()->with('error', trans('messages.cabinet.articles.errorEmpty')); }
            if($result == false) { return back()->with('error', trans('messages.cabinet.articles.errorImage')); }
            # Здесь же проводить действия по удалению записи из articles и category_articles - нет особого смысла,
            # т.к. если будет ошибка, мы это увидим и в сообщении и на результате добавления (после чего будем решать проблему)
        }

        # ⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝

        # $objArticle = Article::getInstance();
        $objArticle = $objArticle->addArticleModel($request, $string, $ipath);

        unset($ipath);

        # dd($objArticle);

        # если Объект не NULL, т.е. запись в Таблицу `articles` была успешной
        if(is_object($objArticle)){

            # dd($objArticle);

            $result = $objArticle->addCategoryArticleModel($request, $objArticle);

            # Проверим, был ли создан Объект Модели
            if($result == true) {

                # Relationship установлена

                # AND

                # Base 64 Encode & Save Images
                # Изображения могут и не добавляться - проверим массив на пустоту, и в зависимости от этого вызов функции
                if($tmpArr['img'] === []){

                    $result64['state'] = false;

                } else {

                    $result64 = _base64decodeAdd($last, $tmpArr);
                }

                if($result64['state'] === true) {
                    # ::: Данные добавлены (включая строку статьи), связи установлены, xml-parse выполнен удачно, base64 и сохранение изображения - вызвало ошибку
                    # ::: message.cabinet.articles.errorBase64
                    return redirect()->route('articles')->with('error', trans('base64 и сохранение изображения - ☂ вызвало ошибку :: '.$result64['error']));
                }

                if($result64['state'] === false) {

                    # перенаправляем на страницу Статей, с выводом соответствующего сообщения
                    return redirect()->route('articles')->with('success', trans('messages.cabinet.articles.add'));
                }

            } elseif($result == false) {

                # Удаление last статьи -→ если связь не установлена
                # код по удалению последней добавленной записи в таблице articles,
                # и корректнее осуществить redirect а не back
                $result = $objArticle->where('id', $objArticle->id)->delete();
                if($result == true) {

                    # ::: связь статьи и категории не установлена
                    return redirect()->route('articles')->with('error', trans('messages.cabinet.articles.errorRelation'));

                } else {

                    # ::: множественная ошибка добавления
                    return redirect()->route('articles')->with('error', trans('messages.cabinet.articles.errorUnknown'));
                }
            }

        } # end IF

        # ::: 'вряд ли мы часто будем попадать на данный return' :::
        # Иначе, возвращаем Пользователя обратно, и выводим сообщение, что 'Не удалось добавить статью'
        return back()->with('error', trans('messages.cabinet.articles.errorAdd'));

    } # END addRequest()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    # Редактирование Статей
    # (приведём явно к int входящий тип данных - id редактируемой Статьи)
    public function edit(int $id)
    {
        $objArticle = Article::getInstance();
        $arrayData = $objArticle->editArticleModel($id);

        if($arrayData=='404'){
            # возвращаем 404 Ошибку
            return abort(404);
        }

        $params = [
            'active'        => 'Articles',
            'title'         => 'Article Edit',
            'categories'    => $arrayData['categories'],     # все Категории (Объект со Свойствами)
            'article'       => $arrayData['objArticle'],     # выбранная Статья
            'arrCategories' => $arrayData['arrCategories']   # Массив изначально выбранных id Категорий к Статье
        ];

        # в шаблон на редактирование, передаём:
        return view('cabinet.articles.edit', $params);

    } # END edit()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    # Редактирование Статей (Request)
    # повторно используем созданную нами Валидацию, и также принимаем id Статьи
    public function editRequest(ArticleRequest $request, int $id)
    {
        #dd($request->all());

        $objArticle = Article::getInstance();

        # ⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝
        # Перед обновлением данных в БД - обработать изображение / -я, если они были загружены ⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝

        $result = _editorParseEdit($id, $request->input('full_text'));

        # dd($result);

        /**/ $string = NULL; /* [Var not defined] */
        /**/ $ipath = NULL; /* [Var not defined] */
        if(is_array($result)) {

            if($result['string'] == '') { } # Данные full_text -→ не введены - удалить все другие добавленные данные: title, author и прочее (не исполняем)

            # dd($result);

            /*
                array:3
                            img => array:3
                                            0 => Flowers.-jpg
                                            1 => funnybird.png
                                            2 => bc7018fd278da0fda6c4298db3994d56.png


                            base => array:3
                                            0 => /articles/images/21/Flowers.jpg
                                            1 => /articles/images/21/funnybird.png
                                            2 => data:image/png;base64,iVBORw0KGgoA...UwAADq


                            string => <meta charset="UTF-8">
                                        <p>ONE</p>
                                        <p>
                                            <img style="width: 256px;" src="/articles/images/21/Flowers.jpg" alt="Flowers" type="jpg">
                                        </p>

                                        <p>
                                            <img style="width: 25%;" src="/articles/images/21/funnybird.png" alt="funnybird" type="png">
                                        </p>

                                        <p>base</p>

                                        <p>
                                            <img style="width: 25%;" src="/articles/images/21/bc7018fd278da0fda6c4298db3994d56.png" alt="bc7018fd278da0fda6c4298db3994d56" type="png">
                                        </p>
            */

            # Прежде чем идти далее, нам нужно провести пользовательскую валидацию ---------
            # чтобы кол-во символов имени изображения не превышало 100 ---------------------
            # Пользовательская валидация на длинну имени изображения - не более 100 символов
            foreach($result['img'] as $val) {
                if(mb_strlen($val) > 100) {
                    return back()->with('error', trans('messages.cabinet.articles.longLength'));
                }
            }
            unset($val);

            # ------------------------------------------------------------------------------

            # ⚑ Вхождение точки кроме как в расширении - вызывает неверную интерпретацию файла

            if($result['img'] == NULL) {

            } else {

                $s = $result['img'][0]; $s = (string)$s;
                $s = strrev($s);
                $dpos = strpos($s, '.');
                $s[$dpos] = '*';
                $s = str_replace('.', 'x', $s);
                $s = str_replace('/', 'x', $s);
                $s = str_replace('\\', 'x', $s);
                $s = str_replace('_', 'x', $s);
                $s = str_replace('-', 'x', $s);
                $s = str_replace('@', 'x', $s);
                $s[$dpos] = '.';
                $result['img'][0] = strrev($s);
            }
            # ------------------------------------------------------------------------------

            # Разграничить массив на массив и строку для записи $result['string']
            $string = $result['string'];

            # Во-первых удалим проблемную строку / строки
            $string = str_replace('<p><br></p>', '', $string);
            $string = str_replace('<br>', '', $string);

            $tmpArr = array('img' => $result['img'], 'base' => $result['base']);

            if($result['img'] == NULL) {
                $ipath = NULL;
            } else {
                $ipath = $result['img'][0];
            }

            unset($result);

        } else {

            /**/ $tmpArr = NULL; /* [Var not defined] Fix bug на условии вызова функции _base64decode() */

            # Если xml parse не выполнен
            if($result === 'empty') { return back()->with('error', trans('messages.cabinet.articles.errorEmpty')); }
            if($result == false) { return back()->with('error', trans('messages.cabinet.articles.errorImage')); }
            # Здесь же проводить действия по удалению записи из articles и category_articles - нет особого смысла,
            # т.к. если будет ошибка, мы это увидим и в сообщении и на результате добавления (после чего будем решать проблему)

        }

        # ⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝⁝

        # $objArticle = Article::getInstance();
        $result = $objArticle->editArticleModelRequest($request, $id, $string, $ipath);

        unset($ipath);

        #

        if($result==true) {

            # Все действия выше выполнены успешно

            # Base 64 Encode & Save Images
            # Изображения могут и не добавляться - проверим массив на пустоту, и в зависимости от этого вызов функции
            if($tmpArr['img'] === []){

                $result64['state'] = false;

            } else {

                $result64 = _base64decodeEdit($id, $tmpArr);
            }

            if($result64['state'] === true) {
                # ::: base64 и сохранение изображения - вызвало ошибку
                # ::: message.cabinet.articles.errorBase64
                return redirect()->route('articles')->with('error', trans('base64 и сохранение изображения - ☂ вызвало ошибку :: '.$result64['error']));
            }

            if($result64['state'] === false) {

                # и в окончании, мы переадресовываем Пользователя на страницу Статей, и пишем, что Статья успешно обновлена
                return redirect()->route('articles')->with('success', trans('messages.cabinet.articles.edit'));
            }

        } else {

            # ::: 'вряд ли мы часто будем попадать на данный return' :::
            # Иначе, возвращаем Пользователя на страницу редактирования, с выводом соответствующего сообщения
            return back()->with('error', trans('messages.cabinet.articles.errorEdit'));
        }

    } # END editRequest()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function del(int $id = 0)
    {
        # Если данной Статьи нет (она не была найдена / возвращена по id)
        if(!Article::find($id)){return abort(404);}

        #dd($id);

        $objArticle = Article::getInstance();
        $result = $objArticle->deleteArticle($id);

        if($result==true) {

            # Удаляем все файлы и директорию с картинками ---------------------------

            /* path */ $path = '../public/articles/images/'.$id;

            if(file_exists($path)) {

                $files = scandir($path);

                # Перебираем массив - удаляем . и ..
                foreach($files as $v) {

                    if($v === '.' || $v === '..') {

                    } else {

                        # Удаляем все файлы
                        unlink($path.'/'.$v);
                    }
                }
                unset($files, $v);

                # Удаляем директорию
                rmdir($path);
            }
            # -----------------------------------------------------------------------


            return back()->with('success', trans('messages.cabinet.articles.delete'));

        }

        return back()->with('error', trans('messages.cabinet.articles.errorDelete'));

    } # END del()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function read(int $id = 0)
    {
        $objArticle = Article::getInstance();

        $objArticle = $objArticle->find($id);

        # dd($objArticle);

        if($objArticle == NULL){ return abort(404); } else {

            $arrayData = $objArticle->returnCategories($objArticle);

            # dd($arrayData);

            $params = [
                'active'    => 'Articles',
                'title'     => 'Articles',
                'categories'    => $arrayData['categories'],     # все Категории (Объект со Свойствами)
                'article'       => $arrayData['objArticle'],     # выбранная Статья
                'arrCategories' => $arrayData['arrCategories']   # Массив изначально выбранных id Категорий к Статье
            ];

            # dd($params);

            return view('cabinet.articles.read', $params);
        }

    } # END read()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function mark(int $id = 0)
    {
        $objArticle = Article::getInstance();

        $objArticle = $objArticle->find($id);

        # dd($objArticle); /* Object / NULL */

        if($objArticle == NULL){ return abort(404); } else {

            if($objArticle->mark_delete == 0) {
                $objArticle->mark_delete = 1;

                # save() - в случае успеха возвращает TRUE
                if($objArticle->save()) { return back()->with('success', trans('messages.cabinet.articles.markset')); }
                else {

                    return back()->with('error', trans('messages.cabinet.categories.errorMark'));
                }
            }

            if($objArticle->mark_delete == 1) {
                $objArticle->mark_delete = 0;

                # save() - в случае успеха возвращает TRUE
                if($objArticle->save()) { return back()->with('success', trans('messages.cabinet.articles.unmark')); }
                else {

                    return back()->with('error', trans('messages.cabinet.articles.errorMark'));
                }
            }

        } # end main if

    } # END mark()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function pub(int $id = 0)
    {
        $objArticle = Article::getInstance();

        $objArticle = $objArticle->find($id);

        if($objArticle == NULL){ return abort(404); } else {

            if($objArticle->flex == 1 && $objArticle->published == 0) { return back()->with('error', trans('messages.cabinet.articles.flexSet')); }

            #

            if($objArticle->published == 0) {
                $objArticle->published = 1;

                # При публикации статьи - мы устанавливаем time для поля published_at
                $objArticle->published_at = Carbon::now();

                # save() - в случае успеха возвращает TRUE
                if($objArticle->save()) { return back()->with('success', trans('messages.cabinet.articles.pub')); }
                else {

                    return back()->with('error', trans('messages.cabinet.articles.errorMark'));
                }
            }

            if($objArticle->published == 1) {
                $objArticle->published = 0;

                # При снятии с публикации - поле published_at остаётся не изменным (изменяется только при публикации)
                # Изменения отражаются в Date
                # $objArticle->published_at = Carbon::now();

                # save() - в случае успеха возвращает TRUE
                if($objArticle->save()) { return back()->with('success', trans('messages.cabinet.articles.unpub')); }
                else {

                    return back()->with('error', trans('messages.cabinet.articles.errorPub'));
                }
            }

        } # end main if
    }

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    # Изменение позиционирования и размеров изображения /-ий в статье
    public function flex(int $id = 0)
    {
        $objArticle = Article::getInstance();

        $objArticle = $objArticle->find($id);

        # dd($objArticle);

        if($objArticle == NULL){ return abort(404); } else {

            $arrayData = $objArticle->returnCategories($objArticle);

            # dd($arrayData);

            $params = [
                'active'    => 'Articles',
                'title'     => 'Flex',
                'categories'    => $arrayData['categories'],     # все Категории (Объект со Свойствами)
                'article'       => $arrayData['objArticle'],     # выбранная Статья
                'arrCategories' => $arrayData['arrCategories']   # Массив изначально выбранных id Категорий к Статье
            ];

            # dd($params);

            # return view('cabinet.articles.flex', $params);
            return view('cabinet.articles.fx.fx', $params);
        }

    } # END flex()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    # Пересохраняем full text
    public function flexRequest(Request $request, int $id = 0)
    {
        # Валидация а Контроллере
        try {
            $this->validate($request, [
                'fullt' => 'min:3'
            ]);
        } catch (ValidationException $e) {
            return back()->with('error', trans('messages.cabinet.articles.fxError'));
        }

        # dd($request->input('fullt'));

        $fullt = $request->input('fullt');

        # На сервере зачистить от <p></p>, и onclick="fx(this)" и id="fx"
        $fullt = str_replace('<p></p>', '', $fullt);
        $fullt = str_replace('onclick="fx(this)"', '', $fullt);
        $fullt = str_replace("onclick='fx(this)'", '', $fullt);

        # Небольшие нюансы
        $fullt = str_replace('" >', '">', $fullt);

        # dd($fullt);

        # Сохраняем данные
        $objArticle = Article::getInstance();
        $objArticle = $objArticle->find($id);

        if($objArticle == NULL){ return abort(404); } else {

            $objArticle->full_text = $fullt;

            # И изменим поле flex на 0 - т.е. FX применён, и статья разрешена к публикации
            $objArticle->flex = 0;

            if($objArticle->save()) { return redirect()->route('articles')->with('success', trans('messages.cabinet.articles.editFx')); }
            else {

                return back()->with('error', trans('messages.cabinet.articles.errorEditFx'));
            }
        }

    } # END flexRequest()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

} /* END Class */