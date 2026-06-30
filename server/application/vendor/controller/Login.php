<?php

namespace app\vendor\controller;

use app\common\lib\IAuth;
use app\common\model\Xusers;
use think\Request;

/**
 * 登录管理类
 * Class Login
 * @package app\cms\Controller
 */
class Login
{
    protected $model;

    public function __construct()
    {
        $this->model = new Xusers();
    }

    /**
     * 登录页
     * @return \think\response\View
     */
    public function index()
    {
        if (IAuth::getVendorUserIDCurrLogged()) {
            return redirect('vendor/index/index');
        } else {
            return view('index');
        }
    }

    /**
     * 登出账号
     * @return \think\response\Redirect
     */
    public function logout()
    {
        IAuth::logoutVendorUserCurrLogged();
        return redirect('vendor/login/index');
    }

    /**
     * ajax 进行管理员的登录操作
     * @param Request $request
     */
    public function ajaxLogin(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $tagRes = $this->model->checkVendorUserLogin($input);
            return showMsg($tagRes['tag'], $tagRes['message']);
        } else {
            return showMsg(0, 'sorry,invalid request!');
        }
    }

    /**
     * ajax 检查登录状态
     * @param Request $request
     */
    public function ajaxCheckLoginStatus(Request $request)
    {
        if ($request->isPost()) {
            $cmsAID = IAuth::getVendorUserIDCurrLogged();
            if ($cmsAID) {
                return showMsg(1, 'login state');
            } else {
                return showMsg(0, 'logout state');
            }
        } else {
            return showMsg(0, 'sorry,invalid request!');
        }
    }
}
