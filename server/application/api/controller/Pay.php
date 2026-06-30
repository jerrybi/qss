<?php


namespace app\api\controller;


use app\common\controller\ApiBase;
use app\common\lib\Tools;
use app\common\lib\LogUtil;
use app\common\model\Xbrochures;
use app\common\model\Xdevices;
use app\common\model\Xorders;
use app\common\model\XpayLogs;
use app\common\model\Xproducts;
use app\common\model\Xusers;
use think\Request;

class Pay extends ApiBase
{
    protected $payLogModel;
    protected $orderModel;
    protected $userModel;
    protected $productModel;
    protected $brochureModel;

    public function __construct()
    {
        $this->payLogModel = new XpayLogs();
        $this->orderModel = new Xorders();
        $this->userModel = new Xusers();
        $this->productModel = new Xproducts();
        $this->brochureModel = new Xbrochures();
    }

    public function payOrder(Request $request){
        $userid = $request->param('user_id');
        $price = $request->param('price');
        $payMethod = $request->param('pay_method');
        $jumpUrl = $request->param('jump_url');
        $code = $request->param('code');
        $name = $request->param('name');
        $startDate = strtotime($request->param('start_date'));
        $endDate = '';
        if($code == '001'){
            $endDate = strtotime('+1 month',$startDate);
        }else if($code == '002'){
            $endDate = strtotime('+1 year',$startDate);
        }else{
            $startDate = 0;
            $endDate = 0;
        }
        //创建订单
        $orderNo = Tools::generateOrderNo($userid);
        $order = new Xorders();
        $order->save(['order_id'=>$orderNo,'user_id'=>$userid,'count'=>1
            ,'name'=>$name,'code'=>$code,'price'=>$price,'status'=>100,
            'start_date'=>date('Y-m-d H:i:s',$startDate),'end_date'=>date('Y-m-d H:i:s',$endDate)]);
        //创建支付记录
        $payLog = new XpayLogs();
        $payLog->save(['id'=>Tools::generatePayNo($userid),'order_id'=>$orderNo,'user_id'=>$userid,'price'=>$price,
            'pay_status'=>100,'order_status'=>100]);
        $payment = config('payment');
        $html = "";
        if($payMethod == 'paypal'){
            $html .= "<html><head></head><body onload=\"document.forms[0].submit();\">";
            $html .= "<div style='margin:0 auto; width:75%; padding:2em; border:solid 0.1em #eee; text-align:center;font-size: 25px;'>Contacting ";
            $html .= $payMethod;
            $html .= "... Please do not refresh/close this page.</div>";
            $html .= "<form id=\"frmPayPal\" name=\"frmPayPal\" action=\"";
            if($payment == 0){
                $html .= config('paypal_debug.url');
            }else{
                $html .= config('paypal_live.url');
            }
            $html .= "\" method=\"POST\">\r\n\t<input type=\"hidden\" name=\"charset\" value=\"utf-8\" /><input type=\"hidden\" name=\"cmd\" value=\"_xclick\" /><input type=\"hidden\" name=\"notify_url\" value=\"";
            if($payment == 0){
                $html .= config('paypal_debug.notify_url');
            }else{
                $html .= config('paypal_live.notify_url');
            }
            $html .= "\" /><input type=\"hidden\" name=\"business\" value=\"";
            if($payment == 0){
                $html .= config('paypal_debug.merchant');
            }else{
                $html .= config('paypal_live.merchant');
            }
            $html .= "\" /><input type=\"hidden\" name=\"return\" value=\"";
            if($payment == 0){
                $html .= config('paypal_debug.return_url');
            }else{
                $html .= config('paypal_live.return_url');
            }
            $html .= "?x=".$orderNo."&y=".$jumpUrl;
            $html .= "\" /><input type=\"hidden\" name=\"cancel_return\" value=\"";
            if($payment == 0){
                $html .= config('paypal_debug.cancel_url');
            }else{
                $html .= config('paypal_live.cancel_url');
            }
            $html .= "?x=".$orderNo."&y=".$jumpUrl;
            $html .= "\" /><input type=\"hidden\" name=\"currency_code\" value=\"";
            if($payment == 0){
                $html .= config('paypal_debug.currency');
            }else{
                $html .= config('paypal_live.currency');
            }
            $html .= "\" /><input type=\"hidden\" name=\"item_name\" value=\"";
            $html .= $name;
            $html .= "\" /><input type=\"hidden\" name=\"item_number\" value=\"";
            $html .= $orderNo;
            $html .= "\" /><input type=\"hidden\" name=\"invoice\" value=\"";
            $html .= $orderNo;
            $html .= "\" /><input type=\"hidden\" name=\"amount\" value=\"";
            $html .= $price;
            $html .= "\" /><input type=\"hidden\" name=\"no_shipping\" value=\"1\"/>";
            $html .= "\r\n</form>";
            $html .= "</body></html>";
        }else{
            $html .= "<html><head></head><body>";
            $html .= "<div style='margin:0 auto; width:75%; padding:2em; border:solid 0.1em #eee; text-align:center;'>";
            $html .= $payMethod;
            $html .= " not support.please select other payment</div>";
            $html .= "</body></html>";
        }
        header("Content-Type:text/html;charset=utf-8");
        return $html;
    }

    public function completeOrder(Request $request){
        $orderNo = $request->param('x');
        $jumpUrl = $request->param('y');
        $payLog = $this->payLogModel->getOrderByOrderNo($orderNo);
        $msg = '';
        if(empty($jumpUrl)){
            $jumpUrl = '/';
        }
        if(empty($payLog)){
            $msg = 'order not exist';
        }else{
            //更新paylog支付状态
            if($payLog->pay_status != 200){
                $payLog->save(['pay_status'=>300],['order_id'=>$orderNo]);
            }
            $msg = 'payment complete';
        }
        $html = '<html><head></head><body><div>'.$msg.',go back after 3s</div><script>setTimeout(function(){window.location.href = "'.$jumpUrl.'"},3000)</script></body></html>';
        header("Content-Type:text/html;charset=utf-8");
        return $html;
    }

    public function cancelOrder(Request $request){
        $orderNo = $request->param('x');
        $jumpUrl = $request->param('y');
        $payLog = $this->payLogModel->getOrderByOrderNo($orderNo);
        $msg = '';
        if(empty($jumpUrl)){
            $jumpUrl = '/';
        }
        if(empty($payLog)){
            $msg = 'order not exist';
        }else{
            //更新paylog支付状态
            if($payLog->pay_status != 200){
                $payLog->save(['pay_status'=>600,'order_status'=>600],['order_id'=>$orderNo]);
            }
            //更新订单表状态
            $order = $this->orderModel->getOrderByOrderId($orderNo);
            if($order->status != 200){
                $order->save(['status'=>600],['order_id'=>$payLog->orderid]);
            }
            $msg = 'payment canceled';
        }
        $html = '<html><head></head><body><div>'.$msg.',back after 3 seconds</div><script>setTimeout(function(){window.location.href = "'.$jumpUrl.'"},3000)</script></body></html>';
        header("Content-Type:text/html;charset=utf-8");
        return $html;
    }

    public function notifyPayPal(Request $request){
        LogUtil::info($request->param());
        $InvoiceId = $request->param('invoice');
        $TransactionId = $request->param('txn_id');
        $PayeeKey = $request->param('receiver_email');
        $PayerKey = $request->param('payer_email');
        $Currency = $request->param('mc_currency');
        $GrossAmount = $request->param('mc_gross');
        $Fee = $request->param('mc_fee');
        $Status = $request->param('payment_status');
        //校验当前通知
        $payment = config('payment');
        $data = $request->param();
        $data['cmd'] = '_notify-validate';
        $data = http_build_query($data);
        $url = $payment == 0?config('paypal_debug.url'):config('paypal_live.url');
        $html = HttpUtil::http_post_data($url,$data);
        $html = $html[1];
        $IsVerified = ($html == 'VERIFIED');
        $IsCompleted = ($Status == 'Completed');
        if($IsVerified){
            if(!empty($InvoiceId)){
                $payLog = $this->payLogModel->getOrderByOrderNo($InvoiceId);
                if(!empty($payLog)){
                    $okToProceed = false;
                    $ApiKey = ($payment == 0)?config('paypal_debug.merchant'):config('paypal_live.merchant');
                    if($IsCompleted) {
                        $okToProceed = ($ApiKey == $PayeeKey) && ($payLog->price == $GrossAmount);
                        if($okToProceed){
                            //支付成功
                            //更新paylog表
                            $payLog->save(['order_no' => $TransactionId, 'pay_status' => 200, 'order_status' => 200], ['order_id' => $InvoiceId]);
                            //更新order表
                            $order = $this->orderModel->getOrderByOrderId($InvoiceId);
                            $order->save(['status' => 200], ['order_id' => $InvoiceId]);
                            //获取当前商品订单对应的brochure个数
                            $brochureNum = $this->productModel->getBrochureNum($order->code);
                            //更新user表中用户的续费时长
                            $user = $this->userModel->getUserByUid($order->user_id);
                            if($order->code == '001' || $order->code == '002'){
                                $user->start_date = $order->start_date;
                                $user->end_date = $order->end_date;
                                $user->step = 1;
                            }
                            $user->brochure_num = ['inc',$brochureNum];
                            $user->save();
                            //在brochure表中插入当前用户对应的brochure数据条数
                            $this->brochureModel->incBrochureRecord($user->id,$brochureNum);
                        }else{
                            LogUtil::info('[notifyPayPal]pay amount not match!!!');
                        }
                    }else{
                        $okToProceed = ($ApiKey == $PayeeKey);
                        if($okToProceed){
                            //其他状态
                            $paystatus = 900;
                            if($Status == 'Refunded'){
                                $paystatus = 700;
                            }else if($Status == 'Reversed'){
                                $paystatus = 800;
                            }
                            //更新paylog表
                            $payLog->save(['order_no'=>$TransactionId,'pay_status'=>$paystatus],['order_id'=>$InvoiceId]);
                        }else{
                            LogUtil::info('[notifyPayPal]merchant not match!!!');
                        }
                    }
                }else{
                    LogUtil::info('[notifyPayPal]order not exist');
                }
            }else{
                LogUtil::info('[notifyPayPal]order ID is empty');
            }
        }else{
            LogUtil::info('[notifyPayPal]verify failed '.$html);
        }
    }
}