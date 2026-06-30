<?php


namespace app\common\controller;


class UserBase extends Base
{
    public $page_limit;
    public function __construct(){
        parent::__construct();
        $this->page_limit = config('app.CMS_PAGE_SIZE');
    }
}