<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\IAuth;
use app\common\model\Xadmins;
use app\common\model\Xevents;
use app\common\model\Xconfigs;
use app\common\model\XmailSettings;
use app\common\model\XnavMenus;
use app\common\model\XsysConf;
use app\common\model\XuserEvents;
use think\Request;

/**
 * 登录管理类
 * Class Login
 * @package app\cms\Controller
 */
class Login
{
    protected $adminModel;
    protected $navMenuModel;
    protected $exhibitorModel;
    protected $eventModel;
    protected $userEventModel;
    public function __construct()
    {
        $this->adminModel = new Xadmins();
        $this->navMenuModel = new XnavMenus();
        $this->exhibitorModel = new Xconfigs();
        $this->eventModel = new Xevents();
        $this->userEventModel = new XuserEvents();
    }

    /**
     * 登录页
     * @return \think\response\View
     */
    public function index()
    {
        if (IAuth::getAdminIDCurrLogged()) {
            return redirect('cms/index/index');
        } else {
            $event = $this->eventModel->getFirstEvent();
            $exhibitorModel = $this->exhibitorModel->getCmsData($event['id']);
            return view('index',['config'=>$exhibitorModel]);
        }
    }

    /**
     * 登出账号
     * @return \think\response\Redirect
     */
    public function logout()
    {
        IAuth::logoutAdminCurrLogged();
        return redirect('cms/login/index');
    }

    /**
     * ajax 进行管理员的登录操作
     * @param Request $request
     */
    public function ajaxLogin(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $sysConf = new XsysConf();
            if ($sysConf->checkCmsIpAuth()){
                $tagRes = $this->adminModel->checkAdminLogin($input);
                if($tagRes['tag'] > 0){
                    $this->userEventModel->checkUpdateData($tagRes['tag']);
                }
            }else{
                $tagRes = ['tag'=>0,'message'=>'Sorry,Your IP is abnormal, please contact the administrator!'];
            }

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
            $cmsAID = IAuth::getAdminIDCurrLogged();
            $nav_menu_id = $request->param('nav_menu_id');
            //TODO 判断当前菜单是否属于他的权限内
            $checkTag = $this->navMenuModel->checkNavMenuMan($nav_menu_id, $cmsAID);
            if ($cmsAID && $checkTag) {
                return showMsg(1, 'login state');
            } else {
                return showMsg(0, 'logout state');
            }
        } else {
            return showMsg(0, 'sorry,invalid request!');
        }
    }
}
