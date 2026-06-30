<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
/**
 * 前台页面  仅作简单展示
 */
Route::get('/M5sq12CzaPO6y','cms/index/index');
Route::get('/index/index','index/index/index');
Route::post('/index/index','index/index/index');
Route::get('/index/announcements','index/index/announcements');
Route::get('/index/notices/:id','index/index/notices');
Route::get('/index/marketing','index/index/marketing');
Route::get('/index/directory','index/index/directory');
Route::get('/index/detail/:id','index/index/detail');
Route::get('/index/download','index/index/download');
Route::get('/index/v1','index/index/v1');
Route::get('/index/v2','index/index/v2');
Route::get('/index/v3','index/index/v3');
Route::get('/index/v4','index/index/v4');
Route::get('/index/v5','index/index/v5');
Route::get('/index/v6','index/index/v6');
Route::get('/index/v7','index/index/v7');
Route::get('/index/v8','index/index/v8');
Route::get('/index/v9','index/index/v9');
Route::get('/index/v11','index/index/v11');
Route::get('/index/v12','index/index/v12');
Route::get('/index/v13','index/index/v13');
Route::get('/index/v14','index/index/v14');
Route::get('/index/v15','index/index/v15');
Route::get('/index/v16','index/index/v16');
Route::get('/index/v17','index/index/v17');
Route::get('/index/v18','index/index/v18');
Route::get('/index/v19','index/index/v19');
Route::get('/index/v20','index/index/v20');
Route::get('/index/confirmation','index/index/confirmation');
Route::get('/index/reminderToRegister','index/index/reminderToRegister');
Route::get('/index/reminderToAttend','index/index/reminderToAttend');
Route::get('/index/internalGuest','index/index/internalGuest');
Route::get('/index/singleCard','index/index/singleCard');
Route::get('/index/doubleCard','index/index/doubleCard');
Route::get('/index/join','index/index/join');
Route::get('/index/accept','index/index/accept');
Route::get('/index/reject','index/index/reject');
Route::get('/index/support','index/index/support');
Route::any('/index/form','index/index/form')->middleware('blacklist');
Route::get('/index/search','index/index/search');
Route::get('/index/result','index/index/result');
Route::get('/index/user','index/index/user');
Route::get('/index/makeCard/:id','index/index/makeCard');
Route::get('/index/close','index/index/close');
Route::get('/index/receipt','index/index/receipt');
Route::get('/oP3n0rM','index/index/openForm');
Route::get('/privacy','index/index/privacy');
//前台登录
Route::get('index/login/index','index/login/index');
Route::any('index/login/logout','index/login/logout');
Route::post('index/login/ajaxLogin','index/login/ajaxLogin');

Route::get('checkin','api/user/checkin');
Route::get('logo','api/user/logo');
Route::get('result','api/user/result');
Route::get('changeFn','api/user/changeFn');


//exhibitor页面
Route::get('exhibitor/index/index','exhibitor/index/index');
Route::get('exhibitor/records','exhibitor/index/records');
Route::post('exhibitor/records','exhibitor/index/records');
Route::get('exhibitor/profile','exhibitor/index/profile');
Route::get('exhibitor/download','exhibitor/index/download');
Route::get('exhibitor/updatePwd','exhibitor/index/updatePwd');
Route::post('exhibitor/updatePwd','exhibitor/index/updatePwd');
Route::get('exhibitor/contact','exhibitor/index/contact');
//exhibitor登录
Route::get('exhibitor/login/index','exhibitor/login/index');
Route::get('exhibitor/login/logout','exhibitor/login/logout');
Route::post('exhibitor/login/ajaxLogin','exhibitor/login/ajaxLogin');
//放在最后一个匹配
Route::get('exhibitor','exhibitor/index/index');

/**
 * 后台 CMS配置
 */
Route::get('admin','cms/index/index');
Route::get('cms/index/index','cms/index/index');
Route::any('cms/home','cms/index/home');
Route::any('cms/index/home','cms/index/home');
Route::any('cms/index/admin/:id','cms/index/admin');

//后台导航菜单管理
Route::any('cms/menu/index','cms/navMenu/index');
Route::any('cms/menu/add','cms/navMenu/add');
Route::any('cms/menu/edit/:id','cms/navMenu/edit');
Route::any('cms/menu/auth/:id','cms/navMenu/auth');

//zones
Route::any('cms/zones/index','cms/zones/index');
Route::any('cms/zones/add','cms/zones/add');
Route::any('cms/zones/edit/:id','cms/zones/edit');
Route::get('cms/zones/viewLogs/:id','cms/zones/viewLogs');
Route::get('cms/zones/preview/:id','cms/zones/preview');
Route::get('cms/zones/download','cms/zones/download');
Route::any('cms/zones/report','cms/zones/report');
Route::post('cms/zones/getList','cms/zones/getList');

//tables
Route::any('cms/tables/index','cms/tables/index');
Route::any('cms/tables/add','cms/tables/add');
Route::any('cms/tables/edit/:id','cms/tables/edit');
Route::get('cms/tables/viewLogs/:id','cms/tables/viewLogs');
Route::get('cms/tables/preview/:id','cms/tables/preview');
Route::get('cms/tables/download','cms/tables/download');
Route::any('cms/tables/report','cms/tables/report');
Route::post('cms/tables/getList','cms/tables/getList');

//edm templates
Route::any('cms/edmTemplates/index','cms/edmTemplates/index');
Route::any('cms/edmTemplates/add','cms/edmTemplates/add');
Route::any('cms/edmTemplates/edit/:id','cms/edmTemplates/edit');

//edm tasks
Route::any('cms/edmTasks/index','cms/edmTasks/index');
Route::any('cms/edmTasks/add','cms/edmTasks/add');
Route::any('cms/edmTasks/edit/:id','cms/edmTasks/edit');
Route::any('cms/edmTasks/resend','cms/edmTasks/resend');
Route::any('cms/edmTasks/resendSelected','cms/edmTasks/resendSelected');
Route::any('cms/edmTasks/download','cms/edmTasks/download');
Route::any('cms/edmTasks/assign','cms/edmTasks/assign');

// exhibitors
Route::any('cms/exhibitors/index','cms/exhibitors/index');
Route::any('cms/exhibitors/add','cms/exhibitors/add');
Route::any('cms/exhibitors/edit/:id','cms/exhibitors/edit');
Route::post('cms/exhibitors/ajaxUpdateUserStatus','cms/exhibitors/ajaxUpdateUserStatus');
Route::any('cms/exhibitors/resetPassword/:id','cms/exhibitors/resetPassword');
Route::any('cms/exhibitors/download','cms/exhibitors/download');
Route::any('cms/exhibitors/upload','cms/exhibitors/upload');

//card templates
Route::any('cms/cardTemplates/index','cms/cardTemplates/index');
Route::any('cms/cardTemplates/add','cms/cardTemplates/add');
Route::any('cms/cardTemplates/edit/:id','cms/cardTemplates/edit');

//user tables
Route::any('cms/userTable/index','cms/userTable/index');
Route::any('cms/userTable/add','cms/userTable/add');
Route::any('cms/userTable/edit/:id','cms/userTable/edit');
Route::any('cms/userTable/change','cms/userTable/change');

//kiosk
Route::any('cms/kiosk/index','cms/kiosk/index');
Route::get('cms/kiosk/preview/:id','cms/kiosk/preview');

//formMarkets
Route::any('cms/formMarkets/index','cms/formMarkets/index');
Route::any('cms/formMarkets/add','cms/formMarkets/add');
Route::any('cms/formMarkets/edit/:id','cms/formMarkets/edit');
Route::get('cms/formMarkets/viewLogs/:id','cms/formMarkets/viewLogs');
Route::get('cms/formMarkets/preview/:id','cms/formMarkets/preview');
Route::get('cms/formMarkets/download','cms/formMarkets/download');
Route::get('cms/formMarkets/report','cms/formMarkets/report');
Route::any('cms/formMarkets/getOrderItems','cms/formMarkets/getOrderItems');
Route::post('cms/formMarkets/getVendorAccounts','cms/formMarkets/getVendorAccounts');

//formBookings
Route::any('cms/formBookings/index','cms/formBookings/index');
Route::any('cms/formBookings/add','cms/formBookings/add');
Route::any('cms/formBookings/edit/:id','cms/formBookings/edit');
Route::get('cms/formBookings/viewLogs/:id','cms/formBookings/viewLogs');
Route::get('cms/formBookings/preview/:id','cms/formBookings/preview');
Route::get('cms/formBookings/download','cms/formBookings/download');
Route::any('cms/formBookings/report','cms/formBookings/report');
Route::any('cms/formBookings/getOrderItems','cms/formBookings/getOrderItems');
Route::post('cms/formBookings/getVendorAccounts','cms/formBookings/getVendorAccounts');

//formbadges
Route::any('cms/formBadges/index','cms/formBadges/index');
Route::any('cms/formBadges/add','cms/formBadges/add');
Route::any('cms/formBadges/edit/:id','cms/formBadges/edit');
Route::get('cms/formBadges/viewLogs/:id','cms/formBadges/viewLogs');
Route::get('cms/formBadges/preview/:id','cms/formBadges/preview');
Route::get('cms/formBadges/download','cms/formBadges/download');
Route::any('cms/formBadges/report','cms/formBadges/report');
Route::any('cms/formBadges/getOrderItems','cms/formBadges/getOrderItems');
Route::post('cms/formBadges/getVendorAccounts','cms/formBadges/getVendorAccounts');

//formManpowers
Route::any('cms/formManpowers/index','cms/formManpowers/index');
Route::any('cms/formManpowers/add','cms/formManpowers/add');
Route::any('cms/formManpowers/edit/:id','cms/formManpowers/edit');
Route::get('cms/formManpowers/viewLogs/:id','cms/formManpowers/viewLogs');
Route::get('cms/formManpowers/preview/:id','cms/formManpowers/preview');
Route::get('cms/formManpowers/download','cms/formManpowers/download');
Route::any('cms/formManpowers/report','cms/formManpowers/report');
Route::any('cms/formManpowers/getOrderItems','cms/formManpowers/getOrderItems');
Route::post('cms/formManpowers/getVendorAccounts','cms/formManpowers/getVendorAccounts');

Route::any('cms/formDatas/index','cms/formDatas/index');
Route::get('cms/formDatas/download','cms/formDatas/download');
Route::get('cms/formDatas/downloadAll','cms/formDatas/downloadAll');

//configs
Route::any('cms/configs/index','cms/configs/index');
Route::any('cms/configs/edit/:id','cms/configs/edit');
Route::get('cms/configs/viewLogs/:id','cms/configs/viewLogs');

//Announcements
Route::any('cms/announcements/index','cms/announcements/index');
Route::any('cms/announcements/add','cms/announcements/add');
Route::any('cms/announcements/edit/:id','cms/announcements/edit');

//Notices
Route::any('cms/notices/index','cms/notices/index');
Route::any('cms/notices/add','cms/notices/add');
Route::any('cms/notices/edit/:id','cms/notices/edit');

//MailSettings
Route::any('cms/mailSettings/index','cms/mailSettings/index');
Route::post('cms/mailSettings/testSendEmail','cms/mailSettings/testSendEmail');

//freight
Route::any('cms/freightSettings/index','cms/freightSettings/index');

//Data Field
Route::any('cms/dataField/index','cms/dataField/index');
Route::any('cms/dataField/add','cms/dataField/add');
Route::any('cms/dataField/edit/:id','cms/dataField/edit');

//Companies
Route::any('cms/companies/index','cms/companies/index');
Route::any('cms/companies/add','cms/companies/add');
Route::any('cms/companies/edit/:id','cms/companies/edit');
Route::any('cms/companies/fieldList','cms/companies/fieldList');
Route::any('cms/companies/editField/:id','cms/companies/editField');
Route::any('cms/companies/getCompanyAttrs','cms/companies/getCompanyAttrs');
Route::any('cms/companies/download','cms/companies/download');
Route::any('cms/companies/upload','cms/companies/upload');
Route::any('cms/companies/viewForms','cms/companies/viewForms');
Route::any('cms/companies/viewForm','cms/companies/viewForm');
Route::any('cms/companies/industryView/:id','cms/companies/industryView');
Route::any('cms/companies/productView/:id','cms/companies/productView');

//catalogs
Route::any('cms/catalogs/index','cms/catalogs/index');
Route::any('cms/catalogs/add','cms/catalogs/add');
Route::any('cms/catalogs/edit/:id','cms/catalogs/edit');
Route::any('cms/catalogs/fieldList','cms/catalogs/fieldList');
Route::any('cms/catalogs/editField/:id','cms/catalogs/editField');
Route::any('cms/catalogs/getCatalogAttrs','cms/catalogs/getCatalogAttrs');
Route::any('cms/catalogs/download','cms/catalogs/download');
Route::any('cms/catalogs/upload','cms/catalogs/upload');
Route::any('cms/catalogs/getMainCategories','cms/catalogs/getMainCategories');
Route::any('cms/catalogs/getSubCategories','cms/catalogs/getSubCategories');
Route::any('cms/catalogs/getUsedMainCategories','cms/catalogs/getUsedMainCategories');

//visitor type
Route::any('cms/visitorType/index','cms/visitorType/index');
Route::any('cms/visitorType/add','cms/visitorType/add');
Route::any('cms/visitorType/edit/:id','cms/visitorType/edit');
Route::any('cms/visitorType/getList','cms/visitorType/getList');

//catalogMarkets
Route::any('cms/catalogMarkets/index','cms/catalogMarkets/index');
Route::any('cms/catalogMarkets/add','cms/catalogMarkets/add');
Route::any('cms/catalogMarkets/edit/:id','cms/catalogMarkets/edit');
Route::any('cms/catalogMarkets/fieldList','cms/catalogMarkets/fieldList');
Route::any('cms/catalogMarkets/editField/:id','cms/catalogMarkets/editField');
Route::any('cms/catalogMarkets/getCatalogAttrs','cms/catalogMarkets/getCatalogAttrs');
Route::any('cms/catalogMarkets/download','cms/catalogMarkets/download');
Route::any('cms/catalogMarkets/upload','cms/catalogMarkets/upload');
Route::any('cms/catalogMarkets/getMainCategories','cms/catalogMarkets/getMainCategories');
Route::any('cms/catalogMarkets/getSubCategories','cms/catalogMarkets/getSubCategories');
Route::any('cms/catalogMarkets/getUsedMainCategories','cms/catalogMarkets/getUsedMainCategories');

//catalogManpowers
Route::any('cms/catalogManpowers/index','cms/catalogManpowers/index');
Route::any('cms/catalogManpowers/add','cms/catalogManpowers/add');
Route::any('cms/catalogManpowers/edit/:id','cms/catalogManpowers/edit');
Route::any('cms/catalogManpowers/fieldList','cms/catalogManpowers/fieldList');
Route::any('cms/catalogManpowers/editField/:id','cms/catalogManpowers/editField');
Route::any('cms/catalogManpowers/getCatalogAttrs','cms/catalogManpowers/getCatalogAttrs');
Route::any('cms/catalogManpowers/download','cms/catalogManpowers/download');
Route::any('cms/catalogManpowers/upload','cms/catalogManpowers/upload');
Route::any('cms/catalogManpowers/getMainCategories','cms/catalogManpowers/getMainCategories');
Route::any('cms/catalogManpowers/getSubCategories','cms/catalogManpowers/getSubCategories');
Route::any('cms/catalogManpowers/getUsedMainCategories','cms/catalogManpowers/getUsedMainCategories');

//Vendors
Route::any('cms/vendors/index','cms/vendors/index');
Route::any('cms/vendors/add','cms/vendors/add');
Route::any('cms/vendors/edit/:id','cms/vendors/edit');
Route::any('cms/vendors/fieldList','cms/vendors/fieldList');
Route::any('cms/vendors/editField/:id','cms/vendors/editField');
Route::any('cms/vendors/getVendorAttrs','cms/vendors/getVendorAttrs');
Route::any('cms/vendors/download','cms/vendors/download');
Route::any('cms/vendors/upload','cms/vendors/upload');
Route::any('cms/vendors/getVendors','cms/vendors/getVendors');

//locations
Route::any('cms/locations/index','cms/locations/index');
Route::any('cms/locations/add','cms/locations/add');
Route::any('cms/locations/edit/:id','cms/locations/edit');
Route::get('cms/locations/viewLogs/:id','cms/locations/viewLogs');
Route::any('cms/locations/getList','cms/locations/getList');

//location groups
Route::any('cms/locationGroups/index','cms/locationGroups/index');
Route::any('cms/locationGroups/add','cms/locationGroups/add');
Route::any('cms/locationGroups/edit/:id','cms/locationGroups/edit');
Route::get('cms/locationGroups/viewLogs/:id','cms/locationGroups/viewLogs');
Route::any('cms/locationGroups/getList','cms/locationGroups/getList');

//participants
Route::any('cms/participants/index','cms/participants/index');
Route::any('cms/participants/edit/:id','cms/participants/edit');
Route::get('cms/participants/viewLogs/:id','cms/participants/viewLogs');

//系统信息配置
Route::any('cms/sysConf/auth','cms/sysConf/auth');
Route::any('cms/sysConf/opfile','cms/sysConf/opfile');
Route::any('cms/sysConf/ipWhite','cms/sysConf/ipWhite');


//管理员
Route::any('cms/admin/index','cms/admin/index');
Route::any('cms/admin/addAdmin','cms/admin/addAdmin');
Route::any('cms/admin/editAdmin/:id', 'cms/admin/editAdmin');
Route::any('cms/admin/getParents','cms/admin/getParents');
Route::any('cms/admin/getChildAccounts','cms/admin/getChildAccounts');

//机构账号
Route::any('cms/admin/account','cms/admin/account');
Route::any('cms/admin/addAccount','cms/admin/addAccount');
Route::any('cms/admin/editAccount/:id', 'cms/admin/editAccount');
Route::any('cms/admin/updatePassword/:id', 'cms/admin/updatePassword');

//角色管理
Route::any('cms/admin/role','cms/admin/role');
Route::any('cms/admin/addRole','cms/admin/addRole');
Route::any('cms/admin/editRole/:id', 'cms/admin/editRole');

//后台登录管理
Route::get('cms/login/index','cms/login/index');
Route::any('cms/login/logout','cms/login/logout');
Route::post('cms/login/ajaxLogin','cms/login/ajaxLogin');
Route::post('cms/login/ajaxCheckLoginStatus','cms/login/ajaxCheckLoginStatus');

//show directory
Route::any('cms/showDirectory/index','cms/showDirectory/index');
Route::any('cms/showDirectory/download','cms/showDirectory/download');
/**
 * 网站业务
 */
//用户管理
Route::any('cms/users/index','cms/users/index');
Route::any('cms/users/add','cms/users/add');
Route::any('cms/users/edit/:id','cms/users/edit');
Route::post('cms/users/ajaxUpdateUserStatus','cms/users/ajaxUpdateUserStatus');
Route::any('cms/users/resetPassword/:id','cms/users/resetPassword');
Route::any('cms/users/vendor','cms/users/vendor');
Route::any('cms/users/attend/:id','cms/users/attend');
Route::any('cms/users/unattend/:id','cms/users/unattend');
Route::any('cms/users/view/:id','cms/users/view');
Route::any('cms/users/upload','cms/users/upload');
Route::any('cms/users/deleteAll','cms/users/deleteAll');
Route::any('cms/users/attendAll','cms/users/attendAll');
Route::any('cms/users/fieldOptions','cms/users/fieldOptions');
Route::any('cms/users/downloadQRCode','cms/users/downloadQRCode');
Route::any('cms/users/downloadAllQRCode','cms/users/downloadAllQRCode');
Route::any('cms/users/download','cms/users/download');
Route::any('cms/users/trackList','cms/users/trackList');
Route::any('cms/users/attendTrack','cms/users/attendTrack');
Route::any('cms/users/unAttendTrack','cms/users/unAttendTrack');
Route::any('cms/users/preview','cms/users/preview');
Route::any('cms/users/approve','cms/users/approve');
Route::any('cms/users/reject','cms/users/reject');
Route::any('cms/users/remark','cms/users/remark');
Route::any('cms/users/confirmation','cms/users/confirmation');
Route::any('cms/users/reminder','cms/users/reminder');

//visitors
Route::any('cms/visitors/index','cms/visitors/index');
Route::any('cms/visitors/contact','cms/visitors/contact');
Route::any('cms/visitors/download','cms/visitors/download');

// level setting
Route::any('cms/levelSettings/index','cms/levelSettings/index');
Route::any('cms/levelSettings/edit/:id','cms/levelSettings/edit');

//统计分析
Route::any('cms/analyze/goodsPricePie','cms/analyze/goodsPricePie');

//Events
Route::any('cms/events/index','cms/events/index');
Route::any('cms/events/add','cms/events/add');
Route::any('cms/events/edit/:id','cms/events/edit');
Route::get('cms/events/viewLogs/:id','cms/events/viewLogs');
Route::any('cms/events/rule/:id','cms/events/rule');
Route::any('cms/events/getData','cms/events/getData');
Route::any('cms/events/bindAdmin','cms/events/bindAdmin');
Route::any('cms/events/getDays','cms/events/getDays');
Route::any('cms/events/active','cms/events/active');
Route::any('cms/events/duplicate','cms/events/duplicate');

//Ems
Route::any('cms/edms/index','cms/edms/index');
Route::any('cms/edms/add','cms/edms/add');
Route::any('cms/edms/edit/:id','cms/edms/edit');
Route::get('cms/edms/viewLogs/:id','cms/edms/viewLogs');

//report
Route::any('cms/report/index','cms/report/index');
Route::any('cms/report/zones','cms/report/zones');
Route::any('cms/report/zoneView','cms/report/zoneView');
Route::any('cms/report/tables','cms/report/tables');
Route::any('cms/report/tableView','cms/report/tableView');
Route::any('cms/report/visitorCategory','cms/report/visitorCategory');
Route::any('cms/report/rsvp','cms/report/rsvp');
/**
 * 工具类
 */

Route::post('api/login/login','api/login/login');
Route::post('api/config/eventList','api/config/eventList');
Route::post('api/config/locationList','api/config/locationList');
Route::post('api/config/trackList','api/config/trackList');
Route::post('api/config/getScreenData','api/config/getScreenData');

/**
 * Uni API 接口类，用于 uniApp 开发学习
 */
Route::any('uniapi/getArticleList','uniapi/index/getArticleList');
Route::post('uniapi/article','uniapi/index/getArticleInfo');

//API
Route::group('api/',function (){
    Route::rule('login','api/User/login','POST');
    Route::rule('regist','api/User/regist','POST');
    Route::rule('sendEmailCode','api/User/sendEmailCode','POST');
    Route::rule('getUserInfo','api/User/getUserInfo','POST');
    Route::post('updateCompanyLogo','api/User/updateCompanyLogo');
    Route::rule('payOrder','api/Pay/payOrder','GET');
    Route::rule('completeOrder','api/Pay/completeOrder','GET');
    Route::rule('cancelOrder','api/Pay/cancelOrder','GET');
    Route::rule('notifyPayPal','api/Pay/notifyPayPal','POST');
    Route::rule('getProductList','api/Index/getProductList','POST');
    Route::post('getProduct','api/Index/getProduct');
    Route::post('uploadFile','api/upload/img_file');
    Route::any('downloadFile','api/Download/download');
    Route::any('downloadTemplate','api/Download/downloadTemplate');
    Route::any('downloadPdfZip','api/Download/downloadPdfZip');
    Route::any('downloadVisitors','api/Download/downloadVisitors');
    Route::post('updateBrochure','api/Index/updateBrochure');
    Route::post('getBrochureList','api/Index/getBrochureList');
    Route::post('saveScanRecord','api/Index/saveScanRecord');
    Route::post('getVisitors','api/Index/getVisitors');
    Route::post('getEdms','api/Index/getEdms');
    Route::post('parseEdm','api/Index/parseEdm');
    Route::post('previewEdm','api/Index/previewEdm');
    Route::post('sendEmail','api/Index/sendEmail');
    Route::any('getDailyScanReport','api/Index/getDailyScanReport');
    Route::any('getDailyDownloadReport','api/Index/getDailyDownloadReport');
    Route::post('uploadContact','api/Index/uploadContact');
    Route::post('checkLogin','api/Index/checkLogin');
})->header('Access-Control-Allow-Origin','*')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->header('Access-Control-Allow-Headers', 'token,Origin,X-Requested-With,Content-Type,Accept,Authorization')
    ->header('Access-Control-Allow-Methods', 'POST,GET,PUT,DELETE')
    ->allowCrossDomain();

Route::get('/:event','index/index/index')
    ->pattern(['event'=>'(?!(admin|exhibitor|vendor|api|cms|index)).*']);
Route::miss('/');





