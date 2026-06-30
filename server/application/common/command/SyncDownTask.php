<?php


namespace app\common\command;


use app\common\lib\HttpUtil;
use app\common\lib\IAuth;
use app\common\lib\LogUtil;
use app\common\lib\Tools;
use app\common\model\Xevents;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class SyncDownTask extends Command
{
    protected function configure()
    {
        $this->setName('think:qss_sync_down_task')->setDescription('this is qss_sync_down_task');
    }

    protected function execute(Input $input, Output $output)
    {
        while (true){
            $events = Db::name('xevents')->where('status','=',1)
                ->select();
            if (!empty($events)){
                foreach($events as $event){
                    $eventID = $event['id'];
                    $lastID = $event['last_sync_id'];
                    $this->process_data($eventID,$lastID);
                    sleep(3);
                }
                sleep(3);
            }else{
                sleep(60);
            }
        }
    }

    private function process_data($eventID,$lastID){
        $param = "eventid=".$eventID."&lastid=".$lastID;
        $url = 'https://qmp.qestsoln.com/api/downloadData';
        $res = HttpUtil::http_post_data($url,$param);
        if($res[0] == 200 && !empty($res[1])){
            $res1 = json_decode($res[1],true);
            if($res1['status'] != 200){
                LogUtil::info('[sync_down]:'.$res1['message']);
            }else{
                $msg = IAuth::decrypt3($res1['data']);
                if(!empty($msg)){
                    $json = json_decode($msg,true);
                    if(!empty($json)){
                        foreach($json as $v){
                            $action = $v['action'];
                            $data = json_decode($v['data'],true);
                            $uniqueID = !empty($data['onsite_number']) ? $data['onsite_number'] : '';
                            // if(!empty($data['serial_number'])){
                            //    $uniqueID = $data['serial_number'];
                            // }
                            $user = Db::name('xuser_datas')->where('event_id',$eventID)
                                ->where('key','onsite_number')
                                ->where('value',$uniqueID)
                                ->find();
                            if($action == 'delete'){
                                if(!empty($user)){
                                    Db::name('xusers')->where('event_id','=',$eventID)
                                        ->where('id',$user['user_id'])->delete();
                                    Db::name('xuser_datas')->where('event_id','=',$eventID)
                                        ->where('user_id',$user['user_id'])->delete();
                                    Db::name('xuser_tables')->where('event_id','=',$eventID)
                                        ->where('user_id',$user['user_id'])->delete();
                                    Db::name('xuser_status')->where('event_id','=',$eventID)
                                        ->where('user_id',$user['user_id'])->delete();
                                }
                            }else{
                                if(!empty($user)){
                                    Db::name('xuser_datas')->where('event_id','=',$eventID)
                                        ->where('user_id',$user['user_id'])->delete();
                                    foreach($data as $key=>$value){
                                        Db::name('xuser_datas')->insert([
                                            'id'=>Tools::create_guid(),
                                            'event_id' => $eventID,
                                            'user_id'=>$user['user_id'],
                                            'key'=>$key,
                                            'value'=>$value,
                                            'create_time'=>date('Y-m-d H:i:s',time()),
                                            'status'=>1
                                        ]);
                                    }
                                }else{
                                    // 2024.10.11 避免把serial number为空的值插入数据
                                    if(!empty($uniqueID)){
                                        $userID = Db::name('xusers')->insertGetId([
                                            'unique_id'=>$uniqueID,
                                            'type'=>0,
                                            'status'=>1,
                                            'event_id'=>$eventID,
                                            'create_time'=>date('Y-m-d H:i:s',time())
                                        ]);
                                        foreach($data as $key=>$value){
                                            Db::name('xuser_datas')->insert([
                                                'id'=>Tools::create_guid(),
                                                'event_id' => $eventID,
                                                'user_id'=>$userID,
                                                'key'=>$key,
                                                'value'=>$value,
                                                'create_time'=>date('Y-m-d H:i:s',time()),
                                                'status'=>1
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                        // update last id
                        $lastItem = end($json);
                        Db::name('xevents')->where('id',$eventID)->update([
                            'last_sync_id'=>$lastItem['id'],
                            'updated_at'=>date('Y-m-d H:i:s',time())
                        ]);
                    }
                }
            }
        }
    }
}