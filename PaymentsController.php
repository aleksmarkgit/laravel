<?php

/* Created by Xenial */

namespace App\Http\Controllers\Cabinet\Control;

# Facades
use Illuminate\Support\Facades\DB;

# Models
use App\Models\User;
# use App\Models\Essence;
use App\Models\Payments;

# Auth
# use Illuminate\Support\Facades\Auth;

# Requests
use Illuminate\Http\Request;

# CONTROLLER
use App\Http\Controllers\Controller;

class PaymentsController extends Controller
{
    public function __construct()
    {
        $this->middleware('root');
    }

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function index()
    {
        # Объект ----------------------------------------
        # -----------------------------------------------

        $objPayments = Payments::getInstance();

        # Выбираем всю коллекцию ------------------------
        # -----------------------------------------------
        $getResult = $objPayments->get();

        $params = [
            'active'    => 'Payments',
            'title'     => 'Payments',
            'payments'  => $getResult,
        ];

        # Передаём данные в представление ---------------
        # -----------------------------------------------

        return view('cabinet.payments.show', $params);

    } # END index()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function add()
    {
        # Объект сторонний ------------------------------
        # -----------------------------------------------

        $objUsers = User::getInstance();

        # Выбираем всю коллекцию ------------------------
        # -----------------------------------------------
        $getResult = $objUsers->get();

        $params = [
            'active'    => 'Payments',
            'title'     => 'Payment Add',
            'users'     => $getResult,
        ];

        return view('cabinet.payments.add', $params);
    } # END add()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function addRequest(Request $request)                                # Здесь проверка на существование id - не нужна
    {                                                                           # id (первый или последующий) ещё не создан
        # Объект сторонний ------------------------------
        # -----------------------------------------------

        $objUsers = User::getInstance();

        # Выбираем всю коллекцию ------------------------
        # -----------------------------------------------
        $getResult = $objUsers->get();

        $usersArray = array();
        foreach($getResult as $key => $val) {
            $usersArray[] = $val->id;
        }
        # Переиндексируем массив
        $arrTmp = array(); $i = 1;
        foreach($usersArray as $key => $val) {
            $arrTmp[$i] = $val;
            $i = $i+1;
        }
        $usersArray = $arrTmp;
        unset($arrTmp);

        # -----------------------------------------------

        # Принимаем данные (проверим существование пользователя)
        $user = $request->input('user');    # INT

        # Валидация
        if(is_numeric($user)) {

            # Идём далее

        } else {

            return back()->with('error', trans('messages.cabinet.payments.errorValidUser'));
        }

        if(in_array($user, $usersArray)) {

            # Идём далее

        } else {

            return back()->with('error', trans('messages.cabinet.payments.unknownUser'));
        }

        # -----------------------------------------------
        # ----------------------------------------------------------------------------------------------
        # -----------------------------------------------

        # Выбираем по user-у ----------------------------
        # -----------------------------------------------

        $getDBEssences = DB::table('essences_objects')->where('user_id', $user)->get();
        if(count($getDBEssences) == 0) {

            # Сюда мы не должны попасть, т.к. проверка на существование user-а выше,
            # однако для выбранного user-а может не существовать объектов,
            # но у нас проверка на клиенте - средствами JS, что выбрать пользователя можно только того, у кого существует объекты / объекты
            # поэтому - можно считать как ошибка валидации пользователя
            return back()->with('error', trans('messages.cabinet.payments.errorValidUserVerification'));

        } else {

            # В переменной $getDBEssences храняться все объекты пользователя

        }

        $objectsArray = array();
        foreach($getDBEssences as $key => $val) {
            $objectsArray[] = $val->id;
        }
        # Переиндексируем массив
        $arrTmp = array(); $i = 1;
        foreach($objectsArray as $key => $val) {
            $arrTmp[$i] = $val;
            $i = $i+1;
        }
        $objectsArray = $arrTmp;
        unset($arrTmp);

        # -----------------------------------------------

        # Принимаем данные (проверим существование объекта)
        $object = $request->input('object');    # INT

        # Валидация
        if(is_numeric($object)) {

            # Идём далее

        } else {

            return back()->with('error', trans('messages.cabinet.payments.errorValidObject'));
        }

        if(in_array($object, $objectsArray)) {

            # Идём далее

        } else {

            return back()->with('error', trans('messages.cabinet.payments.unknownObject'));
        }

        # -----------------------------------------------

        # Принимаем данные

        $sum = $request->input('sum');      # MEDIUMINT › VARCHAR(12) NOT NULL DEFAULT 0
        $pay1 = $request->input('pay1');    # MEDIUMINT › VARCHAR(12) NOT NULL DEFAULT 0

        # Если для авторизованного пользователя уже создана запись в таблице payments
        $getDBPayments = DB::table('payments')->where('essences_objects_id', $object)->get();
        if(count($getDBPayments) == 0) {

            # Нет объектов
            # Для пользователя ещё не сформирован платёж (разрешить дальнейшее выполнение кода)

            if(is_numeric($sum)) {

                $sum = str_replace('-', '', $sum);
                $sum = str_replace('+', '', $sum);

                # ----------------------------------------------------------------------------

                # 212,202,911 Error
                # 212,202911  Valid

                # Проверка на более чем 1-у запятую
                # если больше 1-й запятой - тогда FALSE
                $sum_arr = explode(',', $sum);
                if(count($sum_arr) > 2) { # т.е. 2-я запятые (или более), соответственно 3-и элемента (или более), т.е. если более 2-х элементов
                    # Error
                    return back()->with('error', trans('messages.cabinet.payments.errorValidSumEx'));
                }

                # иначе меням запятую на точку
                $sum = str_replace(',', '.', $sum);

                # 212,202911  › Valid 212.202911
                # 212.202,911 › Error 212.202.911

                # Проверка на более чем 1-у точку
                $sum_arr = explode('.', $sum);
                if(count($sum_arr) > 2) { # т.е. если более 2-х элементов (более 1-й точки)
                    # Error
                    return back()->with('error', trans('messages.cabinet.payments.errorValidSumEx'));
                }

                # Проверка на FLOAT
                $sum_arr = explode('.', $sum);
                if(count($sum_arr) == 1) {
                    # Идём далее ...
                } else {
                    # Иначе формируем float
                    $sum_int = $sum_arr[0];
                    $sum_float = $sum_arr[1];
                    $sum_float = mb_substr($sum_float, 0, 2);
                    $sum = $sum_int . '.' . $sum_float; # xx.xx $
                }

                # ----------------------------------------------------------------------------
                # MEDIUMINT : без знака от 0 до 16777215
                if($sum > 16777215) {

                    return back()->with('error', trans('messages.cabinet.payments.errorValidSumMedium'));
                }
                # Идём далее

            } else {

                return back()->with('error', trans('messages.cabinet.payments.errorValidSum'));
            }

            if(is_numeric($pay1)) {

                $pay1 = str_replace('-', '', $pay1);
                $pay1 = str_replace('+', '', $pay1);

                # ----------------------------------------------------------------------------

                # 212,202,911 Error
                # 212,202911  Valid

                # Проверка на более чем 1-у запятую
                # если больше 1-й запятой - тогда FALSE
                $pay1_arr = explode(',', $pay1);
                if(count($pay1_arr) > 2) { # т.е. 2-я запятые (или более), соответственно 3-и элемента (или более), т.е. если более 2-х элементов
                    # Error
                    return back()->with('error', trans('messages.cabinet.payments.errorValidPayEx'));
                }

                # иначе меням запятую на точку
                $pay1 = str_replace(',', '.', $pay1);

                # 212,202911  › Valid 212.202911
                # 212.202,911 › Error 212.202.911

                # Проверка на более чем 1-у точку
                $pay1_arr = explode('.', $pay1);
                if(count($pay1_arr) > 2) { # т.е. если более 2-х элементов (более 1-й точки)
                    # Error
                    return back()->with('error', trans('messages.cabinet.payments.errorValidPayEx'));
                }

                # Проверка на FLOAT
                $pay1_arr = explode('.', $pay1);
                if(count($pay1_arr) == 1) {
                    # Идём далее ...
                } else {
                    # Иначе формируем float
                    $pay1_int = $pay1_arr[0];
                    $pay1_float = $pay1_arr[1];
                    $pay1_float = mb_substr($pay1_float, 0, 2);
                    $pay1 = $pay1_int . '.' . $pay1_float; # xx.xx $
                }

                # ----------------------------------------------------------------------------

                # MEDIUMINT : без знака от 0 до 16777215
                if($pay1 > 16777215) {

                    return back()->with('error', trans('messages.cabinet.payments.errorValidPayMedium'));
                }
                # Идём далее

            } else {

                return back()->with('error', trans('messages.cabinet.payments.errorValidPay'));
            }

            # -----------------------------------------------

            # Идём далее (записываем данные)

            # Номинально приведём данные к числу (здесь мы готовим данные - которые прошли валидацию - к записи)

            $user = $user*1;        /* Не используем в сохранении */
            $object = $object*1;
            $sum = $sum*1;
            $pay1 = $pay1*1;

            # $str = 'Пользователь : ' . $user . ' | Объект : ' . $object . ' | Сумма : ' . $sum . ' | Платёж : ' . $pay1;
            # dd($str);

            # Перед высчитыванием процента, нужно проверить, превышает ли платеж - основную сумму платежа
            # Если превышает, записываем разницу в поле ...
            if($pay1 > $sum) {
                $difference = $pay1-$sum; # › VARCHAR(12) NULL
                $pay1 = $pay1; # (неверный код : $pay1 = $sum) сумма платежа должна соответствовать реальной, а не уменьшаться вводя в заблуждение
            } else {
                $difference = NULL;
            }

            # Высчитываем процент
            # Если pay1 равен 0, тогда 0%
            if($pay1 == 0) {
                $percentage = 0;
            } else {

                $percentage = $pay1*100/$sum;
                if($percentage > 100) { $percentage = 100; }

                # Иначе, высчитываем процент › VARCHAR(6) NOT NULL DEFAULT 0

                # Если процент - это FLOAT
                if(is_float($percentage)) {
                    $pos = mb_strpos($percentage, '.');
                    $pos = ($pos*1)+4; # xx.xxx %
                    $percentage = mb_substr($percentage, 0, $pos);
                    if($percentage < 1) {
                        $percentage = round($percentage,3);
                    } else {
                        $percentage = round($percentage,1);
                    }

                }
            }

            # Объект ----------------------------------------
            # -----------------------------------------------

            $objPayments = Payments::getInstance();

            $createResult = $objPayments->create([
                'sum' => $sum,                      # VARCHAR(12) NOT NULL DEFAULT 0    (Если FLOAT : xx.xx $)
                'pay_1' => $pay1,                   # VARCHAR(12) NOT NULL DEFAULT 0    (Если FLOAT : xx.xx $)
                'paydate_1' => NOW(),               # TIMESTAMP NOT NULL
                'percentage' => $percentage,        # VARCHAR(6) NOT NULL DEFAULT 0     (Если FLOAT : xx.xxx %)
                'difference' => $difference,        # VARCHAR(12) NULL
                'essences_objects_id' => $object,   # INT UNSIGNED NOT NULL FK
            ]);

            if($createResult == false){

                return back()->with('error', trans('messages.cabinet.payments.errorSave'));

            } else {

                return redirect()->route('payments')->with('success', trans('messages.cabinet.payments.successSave'));
            }

            # -----------------------------------------------

        } else {

            # У пользователя уже сформирован платёж
            return redirect()->route('payments')->with('error', trans('messages.cabinet.payments.paymentIsFormed'));

        }

    } # END addRequest()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function preview(int $id)                        # ПРАВИЛО : входящий параметр id - всегда нужно проверять на find
    {
        $getDBPayments = DB::table('payments')->where('essences_objects_id', $id)->get();
        if(count($getDBPayments) == 0) {
            return abort(404);
        } else {
            $id = $getDBPayments[0]->id;
        }

        # Объект --------------------------------------------
        # ---------------------------------------------------

        $objPayments = Payments::getInstance();

        # Выбираем объект -----------------------------------
        # ---------------------------------------------------

        $findResult = $objPayments->find($id);

        # В случае успеха Объект, иначе NULL ----------------
        # ---------------------------------------------------

        if($findResult == NULL){ return abort(404); } else {

            # Определим, сумму заказа в зависимости от существования значения в поле recount
            $sum = $findResult->sum;
            $recount = $findResult->recount;

            if($recount == NULL) {

                $sum = $sum;

            } else {

                $sum = $recount;
            }

            #

            # Получим суммированный результат всех платежей
            $result = ($findResult->pay_1)+($findResult->pay_2)+($findResult->pay_3)+($findResult->pay_4)+($findResult->pay_5)+($findResult->pay_6)+($findResult->pay_7)+($findResult->pay_8)+($findResult->pay_9)+($findResult->pay_10);

            #

            # Передаём данные в представление ---------------
            # -----------------------------------------------

            $params = [
                'active'    => 'Payments',
                'title'     => 'Payment Preview',
                'sum'       => $sum,
                'result'    => $result,
                'payment'   => $findResult,
            ];

            return view('cabinet.payments.preview', $params);
        }

    } # END preview()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function edit(int $id)                           # ПРАВИЛО : входящий параметр id - всегда нужно проверять на find
    {
        $getDBPayments = DB::table('payments')->where('essences_objects_id', $id)->get();
        if(count($getDBPayments) == 0) {
            return abort(404);
        } else {
            $id = $getDBPayments[0]->id;
        }

        # Объект --------------------------------------------
        # ---------------------------------------------------

        $objPayments = Payments::getInstance();

        # Выбираем объект -----------------------------------
        # ---------------------------------------------------

        $findResult = $objPayments->find($id);

        # В случае успеха Объект, иначе NULL ----------------
        # ---------------------------------------------------

        if($findResult == NULL){ return abort(404); } else {

            # Определим, сумму заказа в зависимости от существования значения в поле recount
            $sum = $findResult->sum;
            $recount = $findResult->recount;

            if($recount == NULL) {

                $sum = $sum;

            } else {

                $sum = $recount;
            }

            #

            # Получим суммированный результат всех платежей
            $result = ($findResult->pay_1)+($findResult->pay_2)+($findResult->pay_3)+($findResult->pay_4)+($findResult->pay_5)+($findResult->pay_6)+($findResult->pay_7)+($findResult->pay_8)+($findResult->pay_9)+($findResult->pay_10);

            # Передаём данные в представление ---------------
            # -----------------------------------------------

            $params = [
                'active'    => 'Payments',
                'title'     => 'Payment Edit',
                'sum'       => $sum,
                'result'    => $result,
                'payment'   => $findResult,
            ];

            return view('cabinet.payments.edit', $params);
        }

    } # END edit()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

    public function editRequest(Request $request, int $id)                      # ПРАВИЛО : входящий параметр id - всегда нужно проверять на find
    {
        $getDBPayments = DB::table('payments')->where('essences_objects_id', $id)->get();
        if(count($getDBPayments) == 0) {
            return abort(404);
        } else {
            $id = $getDBPayments[0]->id;
        }

        $objPayments = Payments::getInstance();
        $findResult = $objPayments->find($id);


        # Принимает данные из $request
        # dd($request);
        # dd($findResult);

        $recount = $request->input('recount');  # MEDIUMINT UNSIGNED NULL › VARCHAR(12) NULL

        $sum   = $request->input('sum');        # MEDIUMINT : без знака от 0 до 16777215 (NOT NULL DEFAULT 0) › VARCHAR(12) NOT NULL DEFAULT 0
        $pay1  = $request->input('pay1');       # MEDIUMINT : без знака от 0 до 16777215 (NOT NULL DEFAULT 0) › VARCHAR(12) NOT NULL DEFAULT 0

        $pay2  = $request->input('pay2');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay3  = $request->input('pay3');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay4  = $request->input('pay4');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay5  = $request->input('pay5');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay6  = $request->input('pay6');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay7  = $request->input('pay7');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay8  = $request->input('pay8');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay9  = $request->input('pay9');       # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL
        $pay10 = $request->input('pay10');      # SMALLINT : без знака от 0 до 65535 (NOT NULL) › VARCHAR(12) NULL

        # $str = $recount.'›recount | '.$sum.'›sum | '.$pay1.'›pay1 | '.$pay2.'›pay2 | '.$pay3.'›pay3 | '.$pay4.'›pay4 | '.$pay5.'›pay5 | '.$pay6.'›pay6 | '.$pay7.'›pay7 | '.$pay8.'›pay8 | '.$pay9.'›pay9 | '.$pay10.'›pay10';

        # --------------------------------------------------------------------------------------------------------------

            # Валидацию никто не отменял
            $validation = function($data, $type)
            {
                # Для экстренной перезаписи 1-го платежа ⓩ
                if($data == 'zero') { return 0; }

                if(is_numeric($data)) {

                    if($data === 0) {

                        return 0;

                    } else {

                        $data = str_replace('-', '', $data);
                        $data = str_replace('+', '', $data);

                        # ----------------------------------------------------------------------------

                        # 212,202,911 Error
                        # 212,202911  Valid

                        # Проверка на более чем 1-у запятую
                        # если больше 1-й запятой - тогда FALSE
                        $data_arr = explode(',', $data);
                        if(count($data_arr) > 2) { # т.е. 2-я запятые (или более), соответственно 3-и элемента (или более), т.е. если более 2-х элементов
                            # Error
                            return FALSE;
                        }

                        # иначе меням запятую на точку
                        $data = str_replace(',', '.', $data);

                        # 212,202911  › Valid 212.202911
                        # 212.202,911 › Error 212.202.911

                        # Проверка на более чем 1-у точку
                        $data_arr = explode('.', $data);
                        if(count($data_arr) > 2) { # т.е. если более 2-х элементов (более 1-й точки)
                            # Error
                            return FALSE;
                        }

                        # Проверка на FLOAT
                        $data_arr = explode('.', $data);
                        if(count($data_arr) == 1) {
                            # Идём далее ...
                        } else {
                            # Иначе формируем float
                            $data_int = $data_arr[0];
                            $data_float = $data_arr[1];
                            $data_float = mb_substr($data_float, 0, 2);
                            $data = $data_int . '.' . $data_float; # xx.xx $
                        }

                        # ----------------------------------------------------------------------------

                        if($type == 'medium') {
                            if($data > 16777215) {
                                # Error
                                return FALSE;
                            }
                        }

                        if($type == 'small') {
                            if($data > 65535) {
                                # Error
                                return FALSE;
                            }
                        }

                        $data = $data*1;

                        # Идём далее
                        return $data;
                    }

                } elseif($data === NULL) {

                    # Идём далее
                    return NULL;

                } else {

                    # Error
                    return FALSE;
                }
            };

            #

            $sum  = $validation($sum, 'medium');
            $valid = $sum;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay1 = $validation($pay1, 'medium');
            $valid = $pay1;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            #

            $recount = $validation($recount, 'medium');
            $valid = $recount;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            #

            $pay2  = $validation($pay2, 'small');
            $valid = $pay2;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay3  = $validation($pay3, 'small');
            $valid = $pay3;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay4  = $validation($pay4, 'small');
            $valid = $pay4;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay5  = $validation($pay5, 'small');
            $valid = $pay5;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay6  = $validation($pay6, 'small');
            $valid = $pay6;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay7  = $validation($pay7, 'small');
            $valid = $pay7;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay8  = $validation($pay8, 'small');
            $valid = $pay8;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay9  = $validation($pay9, 'small');
            $valid = $pay9;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            $pay10 = $validation($pay10, 'small');
            $valid = $pay10;
            if($valid === FALSE) { return back()->with('error', trans('messages.cabinet.payments.errorValid')); }

            unset($valid);

        # --------------------------------------------------------------------------------------------------------------

        # Пересчитываем сумму для вычисления процента (на основе сохранённых данных и передаваемых данных)
        $result = 0;
        if(is_numeric($pay1))  { $result = $result + $pay1;  } else { $result = $result + $findResult->pay_1;  }
        if(is_numeric($pay2))  { $result = $result + $pay2;  } else { $result = $result + $findResult->pay_2;  }
        if(is_numeric($pay3))  { $result = $result + $pay3;  } else { $result = $result + $findResult->pay_3;  }
        if(is_numeric($pay4))  { $result = $result + $pay4;  } else { $result = $result + $findResult->pay_4;  }
        if(is_numeric($pay5))  { $result = $result + $pay5;  } else { $result = $result + $findResult->pay_5;  }
        if(is_numeric($pay6))  { $result = $result + $pay6;  } else { $result = $result + $findResult->pay_6;  }
        if(is_numeric($pay7))  { $result = $result + $pay7;  } else { $result = $result + $findResult->pay_7;  }
        if(is_numeric($pay8))  { $result = $result + $pay8;  } else { $result = $result + $findResult->pay_8;  }
        if(is_numeric($pay9))  { $result = $result + $pay9;  } else { $result = $result + $findResult->pay_9;  }
        if(is_numeric($pay10)) { $result = $result + $pay10; } else { $result = $result + $findResult->pay_10; }

        # dd($result,$recount);

        # Расчитываем процент ----------------------------------------------

        # Если $recount не NULL, всегда пересчитываем процент по $recount-у (сначала ориентируясь на input, потом на DB)
        # Если в двух случаях NULL, также перезаписываем процент

        # Если input как disabled, значит в DB данные актуальны
        if($recount === NULL) {

            # По DB
            if($findResult->recount === NULL) {
                # По sum - если значения recount нет в DB
                # Однако, мы в реальном времени можем поменять и сумму, тогда процент будет высчитан не верно, по не актуальным данным в DB
                if($sum === NULL || $sum === 0) {

                    # Также, изменям процент, только если во время редактирования мы не перезаписываем 1-й платёжж в 0-ноль
                    # иначе берём данные о проценте из БД
                    if($pay1 === 0) {
                        $percentage = $findResult->percentage;
                    } else {
                        $percentage = $result*100/$findResult->sum;
                    }
                    $realSum = $findResult->sum;

                } else {
                    # Также, изменям процент, только если во время редактирования мы не перезаписываем 1-й платёжж в 0-ноль
                    # иначе берём данные о проценте из БД
                    if($pay1 === 0) {
                        $percentage = $findResult->percentage;
                    } else {
                        $percentage = $result*100/$sum;
                    }
                    $realSum = $sum;
                }

            } else {
                # По recount - если значение есть в DB
                # $percentage = $result*100/$findResult->recount;
                # $realSum = $findResult->recount;

                if($pay1 === 0) {
                    $percentage = $findResult->percentage;
                } else {
                    $percentage = $result*100/$findResult->recount;
                }
                $realSum = $findResult->recount;
            }

            # Если input перезаписываем в 0-ноль
        } elseif($recount === 0) {

            $findResult->recount = NULL;

                # По DB - по полю sum, если не изменям данные в реальном времени, иначе по input-у sum
                # Однако, мы в реальном времени можем поменять и сумму, тогда процент будет высчитан не верно, по не актуальным данным в DB
                if($sum === NULL || $sum === 0) {

                    # Также, изменям процент, только если во время редактирования мы не перезаписываем 1-й платёжж в 0-ноль
                    # иначе берём данные о проценте из БД
                    if($pay1 === 0) {
                        # Процент до перезаписи recount в 0-ноль считался по recount, поэтому он не верен
                        # Поэтому при pay 1 как 0 - нужно правильно пересчитать процент
                        # Для этого нужно взять pay 1 из БД, и прибавить к $result - и расчитать процент
                        $result = $result+$findResult->pay_1;
                        $percentage = $result*100/$findResult->sum;
                    } else {
                        $percentage = $result*100/$findResult->sum;
                    }
                    $realSum = $findResult->sum;

                } else {

                    # Также, изменям процент, только если во время редактирования мы не перезаписываем 1-й платёжж в 0-ноль
                    # иначе берём данные о проценте из БД
                    if($pay1 === 0) {
                        $result = $result+$findResult->pay_1;
                        $percentage = $result*100/$findResult->sum;
                    } else {
                        $percentage = $result*100/$sum;
                    }
                    $realSum = $sum;
                }

            # Берём данные по input-у (recount-а)
        } else {

            # Также, изменям процент, только если во время редактирования мы не перезаписываем 1-й платёжж в 0-ноль
            # иначе берём данные о проценте из БД
            if($pay1 === 0) {
                $percentage = $findResult->percentage;
            } else {
                $percentage = $result*100/$recount;
            }
            $realSum = $recount;

            # $percentage = $result*100/$recount;
            # $realSum = $recount;
        }

        # Корректируем процент, если он завышен
        if($percentage>100) { $percentage = 100; }

        # --------------------------------------------------------------------------------------------------------------

        # Сохраняем данные

        # В случае успеха Объект, иначе NULL ----------------
        # ---------------------------------------------------

        if($findResult === NULL){ return abort(404); } else {

            # ------------------------------------------------------------------

            /* Определяем что сохранять, float или int */
            if(is_float($percentage)) {

                $pos = mb_strpos($percentage, '.');
                $pos = ($pos*1)+4; # xx.xxx %
                $percentage = mb_substr($percentage, 0, $pos);
                
                if($percentage < 1) {
                    $findResult->percentage = round($percentage,3);
                } else {
                    $findResult->percentage = round($percentage,1);
                }

            } else {
                $findResult->percentage = $percentage;
            }

            # ------------------------------------------------------------------

            # Если расчёт будет больше суммы, которая требуется к платежу
            if($result > $realSum) {
                $findResult->difference = ($result-$realSum);
            } else {
                $findResult->difference = NULL;
            }

            # ------------------------------------------------------------------

            if($sum === NULL) {
                #
            } elseif($sum === 0) {
                $findResult->sum = $findResult->sum;
            } else {
                # Перезапись
                $findResult->sum = $sum;
            }

            # Для экстренной перезаписи 1-го платежа (и в случае если следующих платежей нет) ⓩ
            if($request->input('pay1') == 'zero') {

                $res = ($findResult->pay_1)+($findResult->pay_2)+($findResult->pay_3)+($findResult->pay_4)+($findResult->pay_5)+($findResult->pay_6)+($findResult->pay_7)+($findResult->pay_8)+($findResult->pay_9)+($findResult->pay_10);
                # Если общая сумма платежей больше pay 1, значит существуют следующие платежи, хотя бы один
                if($res > $findResult->pay_1) {
                    # В этом случае, перезапись 1-го платежа запрещена
                }
                # Если общая сумма платежей == pay 1, значит pay 1  - единственный платёж
                if($res == $findResult->pay_1) {
                    # В этом случае, перезапись 1-го платежа разрешена
                    $findResult->pay_1 = 0;
                    $findResult->percentage = 0;
                }

            } else {

                # Стандартная логика
                if($pay1 === NULL) {
                    #
                } elseif($pay1 === 0) {
                    #
                    $findResult->pay_1 = $findResult->pay_1;
                } else {
                    # Перезапись
                    $findResult->pay_1 = $pay1;
                    $findResult->paydate_1 = NOW();
                }
            }
            # ------------------------------------------------------------------

            if($recount === NULL) {
                #
            } elseif($recount === 0) {
                #
                $findResult->recount = NULL;
            } else {
                $findResult->recount = $recount;
            }

            #

            if($pay2 === NULL) {
                #
            } elseif($pay2 === 0) {
                $findResult->pay_2 = NULL;
                $findResult->paydate_2 = NULL;
            } else {
                $findResult->pay_2 = $pay2;
                $findResult->paydate_2 = NOW();
            }

            #

            if($pay3 === NULL) {
                #
            } elseif($pay3 === 0) {
                $findResult->pay_3 = NULL;
                $findResult->paydate_3 = NULL;
            } else {
                $findResult->pay_3 = $pay3;
                $findResult->paydate_3 = NOW();
            }

            #

            if($pay4 === NULL) {
                #
            } elseif($pay4 === 0) {
                $findResult->pay_4 = NULL;
                $findResult->paydate_4 = NULL;
            } else {
                $findResult->pay_4 = $pay4;
                $findResult->paydate_4 = NOW();
            }

            #

            if($pay5 === NULL) {
                #
            } elseif($pay5 === 0) {
                $findResult->pay_5 = NULL;
                $findResult->paydate_5 = NULL;
            } else {
                $findResult->pay_5 = $pay5;
                $findResult->paydate_5 = NOW();
            }

            #

            if($pay6 === NULL) {
                #
            } elseif($pay6 === 0) {
                $findResult->pay_6 = NULL;
                $findResult->paydate_6 = NULL;
            } else {
                $findResult->pay_6 = $pay6;
                $findResult->paydate_6 = NOW();
            }

            #

            if($pay7 === NULL) {
                #
            } elseif($pay7 === 0) {
                $findResult->pay_7 = NULL;
                $findResult->paydate_7 = NULL;
            } else {
                $findResult->pay_7 = $pay7;
                $findResult->paydate_7 = NOW();
            }

            #

            if($pay8 === NULL) {
                #
            } elseif($pay8 === 0) {
                $findResult->pay_8 = NULL;
                $findResult->paydate_8 = NULL;
            } else {
                $findResult->pay_8 = $pay8;
                $findResult->paydate_8 = NOW();
            }

            #

            if($pay9 === NULL) {
                #
            } elseif($pay9 === 0) {
                $findResult->pay_9 = NULL;
                $findResult->paydate_9 = NULL;
            } else {
                $findResult->pay_9 = $pay9;
                $findResult->paydate_9 = NOW();
            }

            #

            if($pay10 === NULL) {
                #
            } elseif($pay10 === 0) {
                $findResult->pay_10 = NULL;
                $findResult->paydate_10 = NULL;
            } else {
                $findResult->pay_10 = $pay10;
                $findResult->paydate_10 = NOW();
            }

            #

            # Сохраняем Объект (Save) -----------------------
            # -----------------------------------------------
            if($findResult->save()) {

                # Редактирование успешно --------------------
                # -------------------------------------------
                return back()->with('success', trans('messages.cabinet.payments.successEdit'));

            } else {

                # Ошибка редактирования ----------------------
                # --------------------------------------------
                return back()->with('error', trans('messages.cabinet.payments.errorEdit'));
            }
        }

    } # END editRequest()

    # :::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
}
