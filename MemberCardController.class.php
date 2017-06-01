<?php
/*
会员卡控制器
2015-11-2 by zhangyao
 */
namespace Home\Controller;
use Think\Controller;
class MemberCardController extends CommonController {
    protected $membercardService;
    protected $membercardBillService;
    protected $membercardSubService;
    protected $membercardCategoryService;
    protected $employeeService;
    protected $orderService;

    public function __construct(){
        parent::__construct();
        $this->membercardService=D('Membercard','Service');
        $this->membercardBillService=D('MembercardBill','Service');
        $this->membercardSubService=D('MembercardSub','Service');
        $this->membercardCategoryService=D('MembercardCategory','Service');
        $this->employeeService=D('Employee','Service');
        $this->orderService=D('Order','Service');
    }
    /**
     * 页面公共方法
     * @param  string $ac 访问的页面名称如果不传此参数或者填写没有的名称，则默认list
     * @return 跳转到对应的页面
     */
    public function index($ac=null){
        //设置允许访问的页面名称
        $acs = array('list','oldList','overdueList','modify','modifyPwd','consumption','info','open','recharge','shift','retreat','record','appMemberCardList');
        if(!in_array($ac,$acs)){
            $ac='list';
        }
        // 验证权限
        $resultData = $this->checkAuth($ac);
        if($resultData['status']!=1){//验证状态不等于1，未通过验证
            if($resultData['status']==4){//验证状态等于4，未通过审核
                $this->error($resultData['result']);
            }
            curl_get(C('SERVICE_API_URL').'logout?sig='.getSig(),'',cookie('saas_access_token'));
            cookie('saas_access_token',null);
            $this->error($resultData['result'],U("Do/login"));
        }

        //变量方法初始化
        //会员卡信息页面
        if($ac=='list'){
            //显示面包屑
            $this->menuAndreadcrumb('business','cardListData',array("<a href='index'>会员卡资料</a>","<a href='index'>会员卡信息</a>"),'/Static/help/cardinfo.php');
            $this->display("memberCardList");exit;
        }
        //注销会员卡信息页面
        if($ac=='oldList'){
            //显示面包屑
            $this->menuAndreadcrumb('business','cardListData',array("<a href='index'>会员卡资料</a>","<a href='oldList'>注销会员卡信息</a>"));
            $this->display("memberCardOldList");exit;
        }
        //过期会员卡信息页面
        if($ac=='overdueList'){
            //显示面包屑
            $this->menuAndreadcrumb('business','cardListData',array("<a href='index'>会员卡资料</a>","<a href='overdueList'>过期会员卡信息</a>"));
            $this->display("memberCardOverdueList");exit;
        }
        //修改会员卡信息页面
        if($ac=='modify'){
            $mcid = trim(I('mcid'));
            //获取会员卡信息
            $map['mcid']=array('eq',$mcid);
            $membercard = $this->membercardService->getListByMap($map)['0'];
            //获取所有会员卡种类
            $cateMap['tid']=array('eq',$this->loginInfo['user']['tid']);
            $cateMap['mccstatus']=array('eq',1);
            $membercardCategory = $this->membercardCategoryService->getListByMap($cateMap);
            //获取员工信息
            $employeeMap['tid']=array('eq',$this->loginInfo['user']['tid']);
            $employee = $this->employeeService->getListByMap($employeeMap);
            //获取会员卡种类信息
            $typeMap['mccid']=array('eq',$membercard['mccid']);
            $dataType = $this->membercardCategoryService->getListByMap($typeMap)['0'];
            //副卡信息
            $mapSub['mcid'] =array('eq',$mcid);
            $sub =$this->membercardSubService->getListByMap($mapSub);
            //获取会员卡
            if(is_null($membercard)) {
                $this->error('错误的访问！', U("Membercard/list"));
            }
            $this->assign('time',time());
            $this->assign('data',$membercard);
            $this->assign('sub',$sub[0]);
            $this->assign('memberCardCategoryList',$membercardCategory);
            $this->assign('employeedata',$employee);
            $this->assign('dataType',$dataType);
            $this->menuAndreadcrumb('business','cardListData',array("<a href='index'>会员卡资料</a>","<a href='index'>会员卡信息</a>","修改信息"));
            $this->display("modifyCard");exit;
        }
        //修改会员卡密码页面
        if($ac=='modifyPwd'){
            $mcid = trim(I('mcid'));
            if(is_null($mcid)) {
                $this->error('错误的访问！', U("Membercard/list"));
            }
            $this->menuAndreadcrumb('business','cardListData',$breadcrumb = array("<a href='index'>会员卡资料</a>","<a href='index'>会员卡信息</a>","修改密码"));
            $this->assign('mcid',$mcid);
            $this->display("modifyCardPwd");exit;
        }
        //会员卡消费记录页面
        if($ac=='consumption'){
            $mcid = trim(I('mcid'));
            if(is_null($mcid )) {
                $this->error('错误的访问！', U("Membercard/list"));
            }
            $this->menuAndreadcrumb('business','cardListData',array("<a href='index'>会员卡资料</a>"));
            $this->assign('mcid',I("mcid"));
            $this->display("memberCardConsumption");exit;
        }
        //查看会员卡详细信息页面
        if($ac=='info'){
            $mcid = trim(I('mcid'));
            //获取会员卡信息
            $map['mcid']=array('eq',$mcid);
            $membercard = $this->membercardService->getListByMap($map);
            if(is_null($membercard)) {
                $this->error('错误的访问！', U("MemberCard/list"));
            }
            $memberInfo = $membercard['0'];
            $this->assign('data',$memberInfo);
            $this->assign('currTime',strtotime(date("Y-m-d"))*1000);
            $this->menuAndreadcrumb('business','cardListData',array("<a href='index'>会员卡资料</a>","查看会员卡详细信息"));
            $this->display("memberCardInfo");exit;
        }
        //会员卡开卡页面
        if($ac=='open'){
            $this->menuAndreadcrumb('business','open',array("<a href='index?ac=open'>会员卡开卡</a>"),'/Static/help/opencard.php');
            $map['tid']=array('eq',$this->loginInfo['user']['tid']);
            $map['mccstatus']=array('eq',1);
            $membercardData=$this->membercardCategoryService->getListByMap($map);
            for($i=0;$i<count($membercardData);$i++){
                if($membercardData[$i]['mcchargetype']==1){
                    $membercardData[$i]['mcname'] = $membercardData[$i]['mccname'].'(存'. $membercardData[$i]['mccminprice']. '打'. intval($membercardData[$i]['mccdisc'])/10 . '折)';
                }  else{
                    $membercardData[$i]['mcname'] = $membercardData[$i]['mccname'].'(充'. $membercardData[$i]['mccminprice']. '送'. $membercardData[$i]['mcccbrate'] . ')';
                }
            }
            $map['eflag'] = array('neq',1);
            $map['estatus'] = array(array('eq',1),array('eq',3),'or');
            $employeeData= $this->employeeService->getListByMap($map, '*', 'ecode+0');
            for ($i=0; $i <count($employeeData) ; $i++) {
                if($employeeData[$i]['ecode'] == '超级管理员'){
                    $employeeData[$i]['ecname'] = $employeeData[$i]['ename'];
                }elseif($employeeData[$i]['ecode'] == '管理员'){
                    $employeeData[$i]['ecname'] = $employeeData[$i]['ename'] ."(".$employeeData[$i]['esex'].$employeeData[$i]['poname'].")";
                }else{
                    $employeeData[$i]['ecname'] = $employeeData[$i]['ecode'].$employeeData[$i]['ename'] ."(".$employeeData[$i]['esex'].$employeeData[$i]['poname'].")";
                }
            }            
            $tname=$this->loginInfo['tenant']['tname'];
            /**
             * 继续开卡
             */
            $oid =trim(I('oid'));
            if(!empty($oid)){
                $orderMap['oid'] = array('eq',$oid);
                $orderResultData = $this->orderService->getListByMap($orderMap,'oid,membercard');
                if(empty($orderResultData)){
                    $this->error('APP已支付',U('MemberCard/index').'?ac=appMemberCardList');
                }else{
                    $this->assign("oid",$oid);
                    $this->assign("orderCard",json_decode($orderResultData[0]['membercard'],true));
                }
            }
            if($this->loginInfo['user']['ucode'] == '超级管理员' || $this->loginInfo['user']['ucode'] == '管理员'){
                $ename=$this->loginInfo['user']['userName'];
            }else{
                $ename=$this->loginInfo['user']['ucode'].$this->loginInfo['user']['userName'];
            }
            $this->assign("ename",$ename);
            $this->assign('tname',$tname);
            $this->assign('opentime',time());
            $this->assign('endtime',strtotime("+2 year"));
            $this->assign('membercardCategoryList',$membercardData);
            $this->assign('employeeList',$employeeData);
            $this->display("openCard");exit;
        }
        //会员卡续卡页面
        if($ac=='recharge'){
            $this->menuAndreadcrumb('business','recharge',array("<a href='index?ac=recharge'>会员卡续卡</a>"),'/Static/help/renewcard.php');
            $map['tid']=array('eq',$this->loginInfo['user']['tid']);
            $map['mccstatus']=array('eq',1);
            $membercardData=$this->membercardCategoryService->getListByMap($map);
            for($i=0;$i<count($membercardData);$i++){
                if($membercardData[$i]['mcchargetype']==1){
                    $membercardData[$i]['mcname'] = $membercardData[$i]['mccname'].'(存'. $membercardData[$i]['mccminprice']. '打'. intval($membercardData[$i]['mccdisc'])/10 . '折)';
                }  else{
                    $membercardData[$i]['mcname'] = $membercardData[$i]['mccname'].'(充'. $membercardData[$i]['mccminprice']. '送'. $membercardData[$i]['mcccbrate'] . ')';
                }
            }

            $employeeData=$this->employeeService->chooseEmployee($this->loginInfo['user']);
            $tname=$this->loginInfo['tenant']['tname'];
            /**
             * 继续续卡
             */
            $oid =trim(I('oid'));
            if(!empty($oid)){
                $orderMap['oid'] = array('eq',$oid);
                $orderResultData = $this->orderService->getListByMap($orderMap,'oid,membercard');
                if(empty($orderResultData)){
                    $this->error('APP已支付',U('MemberCard/index').'?ac=appMemberCardList');
                }else{
                    $this->assign("oid",$oid);
                    $this->assign("orderCard",json_decode($orderResultData[0]['membercard'],true));
                }
            }
            if($this->loginInfo['user']['ucode'] == '超级管理员' || $this->loginInfo['user']['ucode'] == '管理员'){
                $ename=$this->loginInfo['user']['userName'];
            }else{
                $ename=$this->loginInfo['user']['ucode'].$this->loginInfo['user']['userName'];
            }
            $this->assign("ename",$ename);
            $this->assign('tname',$tname);
            $this->assign('opentime',time());
            $this->assign('endtime',strtotime("+2 year"));
            $this->assign('membercardCategoryList',$membercardData);
            $this->assign('employeeList',$employeeData);
            $this->assign('mcidx',I('mcid'));
            $this->display("rechargeCard");exit;
        }
        //会员卡转卡页面
        if($ac=='shift'){
            $this->menuAndreadcrumb('business','shift',array("<a href='index?ac=shift'>会员卡转卡</a>"));
            if($this->loginInfo['user']['ucode'] == '超级管理员' || $this->loginInfo['user']['ucode'] == '管理员'){
                $ename=$this->loginInfo['user']['userName'];
            }else{
                $ename=$this->loginInfo['user']['ucode'].$this->loginInfo['user']['userName'];
            }
            $tname=$this->loginInfo['tenant']['tname'];
            $this->assign("ename",$ename);
            $this->assign('tname',$tname);
            $this->display("shiftCard");exit;
        }
        //会员卡退卡页面
        if($ac=='retreat'){
            $this->menuAndreadcrumb('business','retreat',array("<a href='index?ac=retreat'>会员卡退卡</a>"),'/Static/help/retreatcard.php');
            if($this->loginInfo['user']['ucode'] == '超级管理员' || $this->loginInfo['user']['ucode'] == '管理员'){
                $ename=$this->loginInfo['user']['userName'];
            }else{
                $ename=$this->loginInfo['user']['ucode'].$this->loginInfo['user']['userName'];
            }
            $tname=$this->loginInfo['tenant']['tname'];
            $this->assign("ename",$ename);
            $this->assign('tname',$tname);
            $this->display("retreatCard");exit;
        }
        //会员卡消费记录
        if($ac=='record'){
            $this->menuAndreadcrumb('business','record',array("<a href='index?ac=record'>会员卡消费记录</a>"),'/Static/help/record.php');
            if($this->loginInfo['user']['ucode'] == '超级管理员' || $this->loginInfo['user']['ucode'] == '管理员'){
                $ename=$this->loginInfo['user']['userName'];
            }else{
                $ename=$this->loginInfo['user']['ucode'].$this->loginInfo['user']['userName'];
            }
            $tname=$this->loginInfo['tenant']['tname'];
            $this->assign("ename",$ename);
            $this->assign('tname',$tname);
            $this->display("recordCard");exit;
        }
        /**
         * 会员卡APP支付记录
         */
        if($ac == 'appMemberCardList'){
            //显示面包屑
            $this->menuAndreadcrumb('business','appMemberCardList',array("<a href='index?ac=appMemberCardList'>会员卡APP支付记录</a>"),'/Static/help/appMemberCardList.php');
            $this->display("appMemberCardList");exit;
        }
    }

    public function ajax($ac=null){
        $acs = array('list','oldList','overdueList','modify','modifyPwd','consumption','consumptionInfo','info','open','recharge','shift','retreat','search','choose','checkCode','chkmcpwd','print',"autoPassword","retreatSearch",'getCardByMcid','appMemberCardList','membercardRevoke','memberCardCategroy','getMcmemo','modifyMcmemo');
        if(!in_array($ac,$acs)){
            $ac='list';
        }

        //验证权限
        $resultData = $this->checkAuth($ac);
        if($resultData['status']!=1){//验证状态不等于1，未通过验证
            if($resultData['status']!=4){//验证状态不等于4，清session
                cookie('saas_access_token',null);
            }
            $this->ajaxReturn($resultData, 'JSON');
        }

        $resultData = getDefaultResult();
        $tenantSwitchService = D('TenantSwitch','Service');
        $loginData=$this->loginInfo;
        //会员卡信息列表数据
        if($ac=='list'){
            $order = I("order");
            $columns = I("columns");
            $length = I('length');
            $start=I("start")/$length+1;
            $serValue = I('search');
            $keyword = $serValue['value'];
            $map = '';
            $map .= ' and l.mcstatus=1 and (l.mcinvalidate>="'.getMillisecond().'" or l.mcinvalidate="")';
            $meberCardList = $this->membercardService->getMembercardList($map,$loginData,$start,$length,$keyword,$order,$columns);

            $data["status"] = $meberCardList['status'];
            $data["recordsTotal"] = $meberCardList['result']['total'];
            $data["recordsFiltered"] = $meberCardList['result']['total'];
            $data['result']["sum"] = $meberCardList['result']['sum'];
            $data["start"] = $start;
            $data['result']['data'] = $meberCardList['result']['data'];
            $this->ajaxReturn($data,'JSON');
        }
        /**
         * 获得会员卡详细资料
         */
        if($ac == 'getCardByMcid'){
            $mcid = trim(I('mcid'));
            //获取会员卡信息
            $map=array();
            $map['mcid']=array('eq',$mcid);
            $membercard = $this->membercardService->getListByMap($map)['0'];
            //获取会员卡种类信息
            $typeMap['mccid']=array('eq',$membercard['mccid']);
            $dataType = $this->membercardCategoryService->getListByMap($typeMap)['0'];
            //副卡信息
            $mapSub['mcid'] =array('eq',$mcid);
            $sub =$this->membercardSubService->getListByMap($mapSub);
            $data['mcid'] =$membercard['mcid'];
            $data['mccode'] =$membercard['mccode'];
            $data['mcname'] =$membercard['mcname'];
            $data['mcmobile'] =$membercard['mcmobile'];
            $data['mcbalance'] =$membercard['mcbalance'];
            $data['mcsbal'] =$sub[0]['mcsbal'];

            $data['mcchargetype'] =$dataType['mcchargetype'];

            $this->ajaxReturn($data,'JSON');
        }
        //注销会员卡信息列表数据
        if($ac=='oldList'){
            $order = I("order");
            $columns = I("columns");
            $length = I('length');
            $start=I("start")/$length+1;

            // $map['mcstatus']=array('eq',0);
            $map = '';
            $map .= ' and l.mcstatus=0';
            $meberCardList = $this->membercardService->getMembercardList($map,$loginData,$start,$length,'',$order,$columns);
            $data["status"] = $meberCardList['status'];
            $data["recordsTotal"] = $meberCardList['result']['total'];
            $data["recordsFiltered"] = $meberCardList['result']['total'];
            $data['result']["sum"] = $meberCardList['result']['sum'];
            $data["start"] = $start;
            $data['result']['data'] = $meberCardList['result']['data'];
            $this->ajaxReturn($data,'JSON');
            // $this->ajaxReturn($meberCardList,'JSON');
        }
        //过期会员卡信息列表数据
        if($ac=='overdueList'){
            $order = I("order");
            $columns = I("columns");
            $length = I('length');
            $start=I("start")/$length+1;
            $map = '';
            $map .= ' and l.mcinvalidate<="'.getMillisecond().'" and l.mcinvalidate!=""';
            $meberCardList = $this->membercardService->getMembercardList($map,$loginData,$start,$length,'',$order,$columns);
            $data["status"] = $meberCardList['status'];
            $data["recordsTotal"] = $meberCardList['result']['total'];
            $data["recordsFiltered"] = $meberCardList['result']['total'];
            $data['result']["sum"] = $meberCardList['result']['sum'];
            $data["start"] = $start;
            $data['result']['data'] = $meberCardList['result']['data'];
            $this->ajaxReturn($data,'JSON');
            // $this->ajaxReturn($meberCardList,'JSON');
        }
        //修改会员卡信息
        if($ac=='modify'){
            $data['mcid'] = trim(I('mcid'));
            $data['mcname'] = trim(I('mcname'));
            $data['mccode'] = trim(I('mccode'));
            $data['mcidentity'] = trim(I("mcidentity"));
            $data['mcmobile'] = trim(I("mcmobile"));
            $data['mcbirth'] = trim(I("mcbirth"));
            $data['mcedittime'] = getMillisecond();
            $data['mcvalidate'] = strtotime(trim(I('mcvalidate')))*1000;
            $data['mcinvalidate'] = trim(I("mcinvalidate"))?strtotime(trim(I("mcinvalidate")))*1000+24*3600*1000:'';
            $data['mcgender'] = trim(I("mcgender"));
            $data['mcmemo'] = trim(I("mcmemo"));
            $resultData = $this->membercardService->modify($data,$loginData);
            $this->ajaxReturn($resultData,'JSON');
        }
        //修改会员卡密码
        if($ac=='modifyPwd'){
            $data['mcid']= trim(I('mcid'));
            $data['newpassword']=trim(I('newpassword'));
            $data['confirmPassword']=trim(I('confirmPassword'));
            $resultData = $this->membercardService->modifyPwd($data,$loginData);
            $this->ajaxReturn($resultData,'JSON');
        }
        //会员卡消费记录查询
        if($ac=='consumption'){
            $mcid=trim(I('mcid'));
            $password=trim(I('password'));
            $result = $this->membercardService->checkPwd($password,$mcid,$loginData);
            if($result['status'] ==1){
                $resultData = $this->membercardBillService->consumption($mcid,$loginData);
                $this->ajaxReturn($resultData,'JSON');
            }
            $this->ajaxReturn($result,'JSON');
        }
        //会员卡资料中消费记录
        if($ac=='consumptionInfo'){
            $mcid=trim(I('mcid'));
            $resultData = $this->membercardBillService->getBill($mcid,$loginData);
            $this->ajaxReturn($resultData,'JSON');
        }
        //开卡
        if($ac=='open'){
            $loginData=$this->loginInfo;
            $membercard=$this->membercardService->genNew();
            $oid=trim(I('oid'));
            $membercard['mcbptype']=trim(I('mcbptype'));//支付类型 3 POS机 1 现金 2 APP
            $membercard['mccode'] = trim(I("mccode"));//会员卡编号
            $membercard['mcpwd'] = md5(trim(I("mcpwd")));//会员卡密码
            $membercard['mccid'] = trim(I("mccid"));//会员卡种类id
            $membercard['mccname'] = trim(I("mccname"));//会员卡种类名称
            $membercard['mcname'] = trim(I("mcname"));//会员卡姓名
            $membercard['eiid'] = trim(I("eiid"));//开卡人id
            $membercard['einame'] = trim(I("einame"));//开卡人姓名
            $membercard['mcdisc'] = trim(I('mcdisc'));//折扣
            $membercard['mcmemo']=trim(I('mcmemo'));//备注
            $membercard['mcinitprice'] = trim(I('mcinitprice'));//储值金额
            $membercard['mccbrate'] = trim(I('mccbrate'));//本店副账户金额
            $membercard['mcbalance'] = trim(I('mcbalance'));//主账户金额
            $membercard['mcidentity'] = trim(I("mcidentity"));//身份证
            $membercard['mcbirth'] = trim(I("mcbirth"));//生日
            $membercard['mcvalidate'] = trim(I("mcvalidate"))?strtotime(trim(I("mcvalidate")))*1000:'';//生效日期
            $membercard['mcinvalidate'] = trim(I("mccterm"))=='0'?strtotime(trim(I("mcinvalidate")))*1000:'';//失效日期
            $membercard['mcmobile'] = trim(I("mcmobile"));//会员卡手机号
            $membercard['mcgender'] = trim(I("mcgender"));//性别
            $membercard['mccterm'] = trim(I("mccterm")); //'有效期 0不永久 1永久',
            $resultData=$this->membercardService->open($membercard,$oid,$loginData);
            $where = array();
            $where['tid'] = $loginData['user']['tid'];
            $where['switchName'] = 'openCard';
            $switch = $tenantSwitchService->getListByMap($where);
            $resultData['switch'] = empty($switch)?0:1;
            if($resultData['status'] == 1){
                if(trim(I('mcbptype')) == '2'){
                    $oid =$resultData['result']['oid'];
                    $resultData['result']['qrcode'] ="<img width='160' height='160' src='".C('HOST').U("Do/index").'?ac=qrcode&oid='.$oid."'/>";
                }
            }
            $this->ajaxReturn($resultData,'JSON');
        }
        //续卡
        if($ac=='recharge'){
            $loginData=$this->loginInfo;
            $oid=trim(I('oid'));
            $membercard['mcid']=trim(I('mcid'));
            $membercard['mcbptype']=trim(I('mcbptype'));//支付类型 3 POS机 1 现金 2 APP
            $membercard['mccode'] = trim(I("mccode"));
            $membercard['mcpwd'] = md5(trim(I("mcpwd")));
            $membercard['mccid'] = trim(I("mccid"));
            $membercard['mccname'] = trim(I("mccname"));
            $membercard['mcname'] = trim(I("mcname"));
            $membercard['eiid'] = trim(I("eiid"));
            $membercard['einame'] = trim(I("einame"));
            $membercard['mcdisc'] = trim(I('mcdisc'));
            $membercard['mcinitprice'] = trim(I('mccminprice'));
            $membercard['mccbrate'] = trim(I('mcccbrate'));
            $membercard['dbreceived'] = trim(I('dbreceived'));
            $membercard['mcbalancemoney'] = trim(I('mcbalancemoney'));
            $membercard['mcmemo'] = trim(I('mcmemo'));
            $membercard['mcinvalidate'] = trim(I("mccterm"))=='0'?strtotime(trim(I("mcinvalidate")))*1000:'';
            $membercard['mccterm'] = trim(I("mccterm")); //'有效期 0不永久 1永久',
            $resultData=$this->membercardService->recharge($membercard,$oid,$loginData);
            $where = array();
            $where['tid'] = $loginData['user']['tid'];
            $where['switchName'] = 'rechargeCard';
            $switch = $tenantSwitchService->getListByMap($where);
            $resultData['switch']=empty($switch)?0:1;
            if($resultData['status'] == 1){
                if(trim(I('mcbptype')) == '2'){
                    $oid =$resultData['result']['oid'];
                    $resultData['result']['qrcode'] ="<img width='160' height='160' src='".C('HOST').U("Do/index").'?ac=qrcode&oid='.$oid."'/>";
                }
            }
            $this->ajaxReturn($resultData,'JSON');
        }
        //转卡
        if($ac=='shift'){
            $loginData['user']=$this->loginInfo['user'];
            $data['cover']=trim(I('cover'));
            $data['mcchargetype']=trim(I('mcchargetype'));
            $data['newmcchargetype']=trim(I('newmcchargetype'));
            $data['mccode']=trim(I('mccode'));
            $data['newmccode']=trim(I('newmccode'));
            $data['mcid']=trim(I('mcid'));
            $data['newmcid']=trim(I('newmcid'));
            $data['mccode']=trim(I('mccode'));
            $data['newmccode']=trim(I('newmccode'));
            $data['mcname']=trim(I('mcname'));
            $data['newmcname']=trim(I('newmcname'));
            $data['mccid']=trim(I('mccid'));
            $data['newmccid']=trim(I('newmccid'));
            $data['mccname']=trim(I('mccname'));
            $data['newmccname']=trim(I('newmccname'));
            $data['mcbalance']=trim(I('mcbalance'));
            $data['newmcbalance']=trim(I('newmcbalance'));
            $resultData=$this->membercardService->shift($data,$loginData);
            $this->ajaxReturn($resultData,'json');
        }
        //退卡
        if($ac=='retreat'){
            $loginData['user']=$this->loginInfo['user'];
            $memberCard['mcid']=I('mcid');
            $memberCard['mcbalance']=trim(I('rtmcbalance'));
            $memberCard['mccode']=trim(I('mccode'));
            $memberCard['mccid']=trim(I('mccid'));
            $memberCard['mccname']=trim(I('mccname'));
            $money['rtmcbalance']=formatPrice($memberCard['mcbalance']);
            $resultData=$this->membercardService->retreat($memberCard,$loginData);
            $where = array();
            $where['tid'] = $loginData['user']['tid'];
            $where['switchName'] = 'retreatCard';
            $switch = $tenantSwitchService->getListByMap($where);
            $resultData['switch']=empty($switch)?0:1;
            $this->ajaxReturn($resultData,'JSON');
        }
        //根据会员手机号查询该会员卡信息
        if($ac=='search'){
            $mobile = trim(I("mobile"));
            $loginData['user']=$this->loginInfo['user'];
            $resultData = $this->membercardService->searchMobile($mobile,$loginData);
            $this->ajaxReturn($resultData, "JSON");
        }
        /**
         * 退卡手机号查询
         */
        if($ac=='retreatSearch'){
            $mobile = trim(I("mobile"));
            $loginData['tid']=$this->loginInfo['user']['tid'];
            $resultData = $this->membercardService->searchBack($mobile,$loginData);
            $this->ajaxReturn($resultData, "JSON");
        }
        //根据卡种类id，获得卡类型
        if($ac=='choose'){
            $map['mccid'] = array('eq',trim(I('mccid')));
            $resultData['status'] = 1;
            $resultData['result'] = $this->membercardCategoryService->getListByMap($map)[0];
            $this->ajaxReturn($resultData,'JSON');
        }
        //ajax验证会员卡编号
        if($ac=='checkCode'){
            $mccode = trim(I('mccode'));
            $loginData['user']=$this->loginInfo['user'];
            $resultData = $this->membercardService->checkCode($mccode,$loginData);
            $this->ajaxReturn($resultData,'JSON');
        }
        //ajax验证会员卡密码并返回数据
        if($ac=='chkmcpwd'){
            $pwd=trim(I('mcpwd'));
            $mcid=trim(I('mcid'));
            $resultData = $this->membercardService->checkPwd($pwd,$mcid);
            $this->ajaxReturn($resultData,'JSON');
        }
        //补打小票
        if($ac=='print'){
            $mcid=trim(I('mcid'));
            $loginData=$this->loginInfo;
            $resultData=$this->membercardService->printTicket($mcid,$loginData);
            $this->ajaxReturn($resultData, 'JSON');
        }
        /**
         * 会员卡APP支付记录
         */
        if($ac == 'appMemberCardList'){
            $loginData['tid']=$this->loginInfo['user']['tid'];
            $resultData = $this->orderService->membercardOrderList($loginData);
            $this->ajaxReturn($resultData, 'JSON');
        }
        /**
         * 撤销会员卡结账
         * @param  string $oid 订单id
         * @return
         */
        if($ac == 'membercardRevoke'){
            $oid = trim(I('oid'));
            $resultData = $this->orderService->membercardRevoke($oid);
            $this->ajaxReturn($resultData, 'JSON');
        }
        /**
         * 会员卡种类
         */
        if($ac == 'memberCardCategroy'){
            $map['tid']=array('eq',$this->loginInfo['user']['tid']);
            $map['mccstatus']=array('eq',1);
            $map['mcchargetype']=array('eq',trim(I('mcchargetype')));
            $membercardData=$this->membercardCategoryService->getListByMap($map);
            for($i=0;$i<count($membercardData);$i++){
                if($membercardData[$i]['mcchargetype']==1){
                    $membercardData[$i]['mcname'] = $membercardData[$i]['mccname'].'(存'. $membercardData[$i]['mccminprice']. '打'. intval($membercardData[$i]['mccdisc'])/10 . '折)';
                }  else{
                    $membercardData[$i]['mcname'] = $membercardData[$i]['mccname'].'(充'. $membercardData[$i]['mccminprice']. '送'. $membercardData[$i]['mcccbrate'] . ')';
                }
            }
            $this->ajaxReturn($membercardData, 'JSON');
        }
        /**
         * 获取会员卡备注
         */
        if($ac == 'getMcmemo'){
            $mcid = trim(I('mcid'));
            $where = array();
            $where['mcid'] = array('eq',$mcid);
            $membercardData = $this->membercardService->getListByMap($where,'mcmemo');
            $resultData['status'] = 1;
            $resultData['result'] = $membercardData['0'];
            $this->ajaxReturn($resultData, 'JSON');
        }
        /**
         * 修改备注
         */
        if($ac == 'modifyMcmemo'){
            $data = array();
            $data['mcid'] = trim(I('mcid'));
            $data['mcmemo'] = trim(I('mcmemo'));
            $membercardResult = $this->membercardService->modifyMcmemo($data);
            $this->ajaxReturn($membercardResult, 'JSON');
        }
    }
}
?>