<?php

namespace app\cms\controller;

use app\common\controller\CmsBase;
use app\common\lib\IAuth;
use app\common\model\XadminAccounts;
use app\common\model\XadminRoles;
use app\common\model\Xadmins;
use app\common\model\XnavMenus;
use think\Db;
use think\Request;

/**
 * 后台管理员
 * Class Admin
 * @package app\cms\Controller
 */
class Admin extends CmsBase
{
    protected $model;
    protected $ar_model;
    protected $menuModel;
    protected $accountModel;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Xadmins();
        $this->ar_model = new XadminRoles();
        $this->menuModel = new XnavMenus();
        $this->accountModel = new XadminAccounts();
    }

    /**
     * 管理员数据列表
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request)
    {
        $search = $request->param('str_search');
        $curr_page = $request->param('curr_page', 1);
        if ($request->isPost()) {
            $list = $this->model->getAdminsForPage($curr_page, $this->page_limit,$search);
            return showMsg(1, 'success', $list);
        } else {
            $list = $this->model->getAdminsForPage($curr_page, $this->page_limit,$search);
            $record_num = $this->model->getAdminsCount($search);
            return view('index',
                [
                    'admins' => $list,
                    'search' => $search,
                    'record_num' => $record_num,
                    'page_limit' => $this->page_limit,
                ]);
        }
    }

    /**
     * 添加新用户
     * @param Request $request
     * @return \think\response\View|void
     */
    public function addAdmin(Request $request)
    {
        $adminRoles = $this->ar_model->getNormalRoles();
        if ($request->isPost()) {
            $input = $request->post();
            $opRes = $tag = $this->model->addAdmin($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            return view('add_admin', [
                'adminRoles' => $adminRoles
            ]);
        }
    }

    /**
     * @param Request $request
     * @param $id 标识 ID
     * @return \think\response\View|void
     */
    public function editAdmin(Request $request, $id)
    {
        $adminRoles = $this->ar_model->getNormalRoles();
        $adminData = $this->model->getAdminData($id);
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->editAdmin($id, $input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            return view('edit_admin', [
                'admin' => $adminData,
                'adminRoles' => $adminRoles
            ]);
        }
    }

    public function updatePassword(Request $request,$id){
        if($request->isPost()){
            $input = $request->param();
            $opRes = $this->model->updatePassword($id, $input);
            return showMsg($opRes['tag'], $opRes['message']);
        }else{
            $adminData = $this->model->getAdminData($id);
            return view('update_password',['admin'=>$adminData]);
        }
    }


    /*TODO -------------------------------------角色管理------------------------------*/

    /**
     * 读取角色列表
     * @return \think\response\View
     */
    public function role()
    {
        $adminRoles = $this->ar_model->getAllRoles();
        return view('role', [
            'roles' => $adminRoles
        ]);

    }

    public function getParents(Request $request){
        $role_id = $request->param('role_id');
        $data = $this->ar_model->getParents($role_id);
        return showMsg(1,'success',$data);
    }

    public function getChildAccounts(Request $request){
        $user_id = IAuth::getAdminIDCurrLogged();
        $list = $this->model->getChildAccounts($user_id);
        return showMsg(1,'success',$list);
    }

    /**
     * 角色添加功能
     * @param Request $request
     * @return \think\response\View|void
     */
    public function addRole(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $opRes = $this->ar_model->addRole($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            //TODO 获取所有可以分配的权限菜单
            $viewMenus = $this->menuModel->getNavMenus();
            $menuTree = $this->getMenuTreeData($viewMenus);
            return view('add_role', [
                'menus' => $viewMenus,
                'menuTree' => $menuTree
            ]);
        }
    }

    /**
     * 更新 角色数据
     * @param Request $request
     * @param $id
     * @return \think\response\View|void
     */
    public function editRole(Request $request, $id)
    {
        $roleData = $this->ar_model->getRoleData($id);
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->ar_model->editRole($id, $input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            //TODO 获取所有可以分配的权限菜单
            $viewMenus = $this->menuModel->getNavMenus();
//            $arrMenuSelf = explode('|', $roleData['nav_menu_ids']);
            $menuTree = $this->getMenuTreeData($viewMenus);
            return view('edit_role', [
                'role' => $roleData,
                'menus' => $viewMenus,
                'menuTree' => $menuTree
            ]);
        }
    }

    public function getMenuTreeData($menus){
        $arr = [];
        foreach($menus as $k => $v){
            $item = ['id'=>$v['id'],'title'=>$v['name'],'spread'=>false,'children'=>[]];
            $data = [];
            if(!empty($v['child'])){
                foreach($v['child'] as $k1 => $v1){
                    $data[] = ['id'=>$v1['id'],'title'=>$v1['name'],'spread'=>false,'children'=>[
                        ['id'=>$v['id'].'-'.$v1['id'].'-1','title'=>'View','spread'=>false],
                        ['id'=>$v['id'].'-'.$v1['id'].'-2','title'=>'Add','spread'=>false],
                        ['id'=>$v['id'].'-'.$v1['id'].'-3','title'=>'Edit','spread'=>false],
                        ['id'=>$v['id'].'-'.$v1['id'].'-4','title'=>'Delete','spread'=>false],
                        ['id'=>$v['id'].'-'.$v1['id'].'-5','title'=>'Import','spread'=>false],
                        ['id'=>$v['id'].'-'.$v1['id'].'-6','title'=>'Export','spread'=>false]
                    ]];
                }
            }else{
                $data = [
                    ['id'=>$v['id'].'-1','title'=>'View','spread'=>false],
                    ['id'=>$v['id'].'-2','title'=>'Add','spread'=>false],
                    ['id'=>$v['id'].'-3','title'=>'Edit','spread'=>false],
                    ['id'=>$v['id'].'-4','title'=>'Delete','spread'=>false],
                    ['id'=>$v['id'].'-5','title'=>'Import','spread'=>false],
                    ['id'=>$v['id'].'-6','title'=>'Export','spread'=>false]
                ];
            }
            $item['children'] = $data;
            $arr[] = $item;
        }
        return json_encode($arr);
    }

    /*TODO -------------------------------------公司结构管理------------------------------*/
    public function account(Request $request)
    {
        $search = $request->param('str_search');
        $curr_page = $request->param('curr_page', 1);
        if ($request->isPost()) {
            $list = $this->accountModel->getAccountsForPage($curr_page, $this->page_limit,$search);
            return showMsg(1, 'success', $list);
        } else {
            $list = $this->accountModel->getAccountsForPage($curr_page, $this->page_limit,$search);
            $record_num = $this->accountModel->getAccountsCount($search);
            return view('account',
                [
                    'admins' => $list,
                    'search' => $search,
                    'record_num' => $record_num,
                    'page_limit' => $this->page_limit,
                ]);
        }
    }

    /**
     * 添加新用户
     * @param Request $request
     * @return \think\response\View|void
     */
    public function addAccount(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $opRes = $tag = $this->accountModel->addAccount($input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            return view('add_account', [

            ]);
        }
    }

    /**
     * @param Request $request
     * @param $id 标识 ID
     * @return \think\response\View|void
     */
    public function editAccount(Request $request, $id)
    {
        $adminData = $this->accountModel->getAccountData($id);
        if ($request->isPost()) {
            $input = $request->param();
            $opRes = $this->model->editAccount($id, $input);
            return showMsg($opRes['tag'], $opRes['message']);
        } else {
            return view('edit_admin', [
                'admin' => $adminData
            ]);
        }
    }
}
