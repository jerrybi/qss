<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\api\controller;
use think\Db;
use think\Request;
use app\common\controller\ApiBase;
use app\common\model\XscreenSettings;

/**
 * Description of Config
 *
 * @author 冬明
 */
class Config extends ApiBase{
    //put your code here
    public function eventList(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $res = Db::name('xevents')->where('status','1')->select();
            return showMsg(1, '',$res);
        } else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }
    
    public function locationList(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $res = Db::name('xlocations')
                    ->where('status','1')
                    ->where('event_id',$input['event_id'])
                    ->select();
            return showMsg(1, '',$res);
        } else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }
    
    public function trackList(Request $request)
    {
        if ($request->isPost()) {
            $input = $request->post();
            $res = Db::name('xtracks')
                    ->where('status','1')
                    ->where('event_id',$input['event_id'])
                    ->select();
            return showMsg(1, '',$res);
        } else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }
    
    public function getScreenData(Request $request){
        if ($request->isPost()) {
            $event_id = $request->param('event_id');
            $screen_id = $request->param('screen_id');
            $screenSetting = new XscreenSettings();
            $data = $screenSetting->getInfo($event_id,$screen_id);
            if(isset($data)){
                $baseurl = $request->domain().config("ftp.IMG_SERVER_PATH");
                if(isset($data['bg_url'])){
                    $data['bg_url'] = $baseurl.$data['bg_url'];   
                }
                if(isset($data['msg_bg_url'])){
                    $data['msg_bg_url'] = $baseurl.$data['msg_bg_url'];   
                }
                if(isset($data['font_url'])){
                    $data['font_url'] = $baseurl.$data['font_url'];   
                }
            }
            return showMsg(1,'success',$data);
        }else {
            return showMsg(0, 'sorry,your request is invalid！');
        }
    }
}
