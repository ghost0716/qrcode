<?php

namespace App\Console\Commands;


//require 'vendor/autoload.php';


use Illuminate\Support\Facades\Cache ;
use Intervention\Image\ImageManagerStatic as Image;


use Illuminate\Console\Command;

class CreateQrcode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:qrcode {key}';
//    protected $signature = 'create:qrcode ';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $key = $this->argument('key');
        $data = array(  "Hs"=>array("suffix" => "a172hrs_",
            "begin" => '100001',
            "end" => '101001'),
            "HA2"=>array("suffix" => "a172hos_",
                "begin" => '111068',
                "end" => '131001'),
            "HA3"=>array("suffix" => "a172hrs_",
                "begin" => '0',
                "end" => '100'),

        );
        $path = public_path("images/$key");
        if(!file_exists("$path")){
            mkdir($path);
        }
        for($i = $data[$key]['begin'];$i < $data[$key]['end'];$i++){
            $name = $data[$key]['suffix'].$i;
            $path_qrcode = $path."/".$name."."."jpg";
            //判断是否存在且为正常文件
            if(is_file($path_qrcode)){
                continue;
            }
            //生成二维码
            $result = $this->getQRCode($name);
            file_put_contents(public_path("images/$key/$name.jpg"),$result);
            //判断是否为正常文件
            if(!is_file($path_qrcode)){
                continue;
            }
            //二维码插画处理
            $image = Image::make(public_path("images/$key/$name.jpg"));
            $width = $image->width();
            $height = $image->height();
            $image->insert(public_path('images/cm.jpg'), 'center')
            ->text($name,$width-173,$height-10,function ($font){
                $font->file(public_path('font/wryh.ttf'));
                $font->size(18);
            })
            ->save(public_path("images/$key/$name.jpg"));
        }
    }

    public function getAccessToken() {
       return Cache::remember('access_token', 3, function () {
           $url = 'http://www.woaap.com/api/accesstoken?appid=wxc035139b07a4a831';
//           $url = "http://woaapsh.etocrm.com/api/accesstoken?appid=wxdf047930c744b993";
           $result = json_decode(file_get_contents($url), true);

           if ($result && isset($result['access_token']))
               return $result['access_token'];

           return null;
       });
    }



    private function _getQRCodeTicket($content,$access_token){

        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";

        $data_arr["action_name"] = "QR_LIMIT_STR_SCENE";
        $data_arr["action_info"]["scene"]["scene_str"] = $content;

        $data = json_encode($data_arr);

        $result = $this->_requestPost($url, $data);
        if (!$result) {
            return false;
        }
//        dd($result);
        $result_obj = json_decode($result);

        return $result_obj->ticket;



    }
    public function getQRCode($content){
        $access_token = $this->getAccessToken();

        $ticket = $this->_getQRCodeTicket($content,$access_token);

        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=$ticket";

        $result = $this->_requestGet($url);
//        dd($result);
        return $result;
    }



    private function _requestPost($url,$data, $ssl=true) {
        // curl完成
        $curl = curl_init();

        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//URL
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '
	Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//user_agent，请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        //SSL相关
        if ($ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//禁用后cURL将终止从服务端进行验证
//            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);//检查服务器SSL证书中是否存在一个公用名(common name)。
        }
        // 处理post相关选项
        curl_setopt($curl, CURLOPT_POST, true);// 是否为POST请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);// 处理请求数据
        // 处理响应结果
        curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果

        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            echo '<br>', curl_error($curl), '<br>';
            return false;
        }
        return $response;
    }

    private function _requestGet($url, $ssl=true) {
        // curl完成
        $curl = curl_init();

        //设置curl选项
        curl_setopt($curl, CURLOPT_URL, $url);//URL
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '
Mozilla/5.0 (Windows NT 6.1; WOW64; rv:38.0) Gecko/20100101 Firefox/38.0 FirePHP/0.7.4';
        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);//user_agent，请求代理信息
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);//referer头，请求来源
        //SSL相关
        if ($ssl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//禁用后cURL将终止从服务端进行验证
//            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);//检查服务器SSL证书中是否存在一个公用名(common name)。
        }
        curl_setopt($curl, CURLOPT_HEADER, false);//是否处理响应头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//curl_exec()是否返回响应结果

        // 发出请求
        $response = curl_exec($curl);
        if (false === $response) {
            echo '<br>', curl_error($curl), '<br>';
            return false;
        }
        return $response;
    }
}
