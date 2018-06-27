<?php if (!defined('THINK_PATH')) exit(); /*a:8:{s:71:"D:\phpStudy\WWW\think\public/../application/admin\view\admin\index.html";i:1503470814;s:73:"D:\phpStudy\WWW\think\public/../application/admin\view\common\header.html";i:1503541982;s:77:"D:\phpStudy\WWW\think\public/../application/admin\view\common\header_css.html";i:1503541894;s:74:"D:\phpStudy\WWW\think\public/../application/admin\view\common\nav_top.html";i:1506060234;s:76:"D:\phpStudy\WWW\think\public/../application/admin\view\common\menu_left.html";i:1504597240;s:78:"D:\phpStudy\WWW\think\public/../application/admin\view\common\footer_tips.html";i:1503388836;s:73:"D:\phpStudy\WWW\think\public/../application/admin\view\common\footer.html";i:1503631952;s:76:"D:\phpStudy\WWW\think\public/../application/admin\view\common\footer_js.html";i:1503631936;}*/ ?>
<!--_meta 作为公共模版分离出去-->
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<meta name="renderer" content="webkit|ie-comp|ie-stand">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
<meta http-equiv="Cache-Control" content="no-siteapp" />
<!-- <link rel="Bookmark" href="favicon.ico" >
<link rel="Shortcut Icon" href="favicon.ico" /> -->
<!--[if lt IE 9]>
<script type="text/javascript" src="__static__/lib/html5.js"></script>
<script type="text/javascript" src="__static__/lib/respond.min.js"></script>
<![endif]-->
<link rel="stylesheet" type="text/css" href="__static__/h-ui/css/H-ui.min.css" />
<link rel="stylesheet" type="text/css" href="__static__/h-ui.admin/css/H-ui.admin.css" />
<link rel="stylesheet" type="text/css" href="__static__/lib/Hui-iconfont/1.0.8/iconfont.css" />
<link rel="stylesheet" type="text/css" href="__static__/h-ui.admin/skin/default/skin.css" id="skin" />
<link rel="stylesheet" type="text/css" href="__static__/h-ui.admin/css/style.css?t=<?php echo time();?>" />
<!--[if IE 6]>
<script type="text/javascript" src="http://lib.h-ui.net/DD_belatedPNG_0.0.8a-min.js" ></script>
<script>DD_belatedPNG.fix('*');</script>
<![endif]-->
<!--/meta 作为公共模版分离出去-->
<title>H-ui.admin v3.0</title>
<meta name="keywords" content="H-ui.admin v3.0,H-ui网站后台模版,后台模版下载,后台管理系统模版,HTML后台模版下载">
<meta name="description" content="H-ui.admin v3.0，是一款由国人开发的轻量级扁平化网站后台模板，完全免费开源的网站后台管理系统模版，适合中小型CMS后台系统。">
</head>
<body>
<!--_header 作为公共模版分离出去-->
<header class="navbar-wrapper">
    <div class="navbar navbar-fixed-top">
        <div class="container-fluid cl"> <a class="logo navbar-logo f-l mr-10 hidden-xs" href="/aboutHui.shtml">H-ui.admin</a> <a class="logo navbar-logo-m f-l mr-10 visible-xs" href="/aboutHui.shtml">H-ui</a> 
            <span class="logo navbar-slogan f-l mr-10 hidden-xs">v3.0</span> 
            <a aria-hidden="false" class="nav-toggle Hui-iconfont visible-xs" href="javascript:;">&#xe667;</a>
            <nav class="nav navbar-nav">
                <ul class="cl">
                    <li class="dropDown dropDown_hover"><a href="javascript:;" class="dropDown_A"><i class="Hui-iconfont">&#xe600;</i> 新增 <i class="Hui-iconfont">&#xe6d5;</i></a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li><a href="javascript:;" onclick="article_add('添加资讯','article-add.html')"><i class="Hui-iconfont">&#xe616;</i> 资讯</a></li>
                            <li><a href="javascript:;" onclick="picture_add('添加资讯','picture-add.html')"><i class="Hui-iconfont">&#xe613;</i> 图片</a></li>
                            <li><a href="javascript:;" onclick="product_add('添加资讯','product-add.html')"><i class="Hui-iconfont">&#xe620;</i> 产品</a></li>
                            <li><a href="javascript:;" onclick="member_add('添加用户','member-add.html','','510')"><i class="Hui-iconfont">&#xe60d;</i> 用户</a></li>
                </ul>
            </li>
        </ul>
    </nav>
            <nav id="Hui-userbar" class="nav navbar-nav navbar-userbar hidden-xs">
                <ul class="cl">
                    <li>超级管理员</li>
                    <li class="dropDown dropDown_hover"> <a href="#" class="dropDown_A">admin <i class="Hui-iconfont">&#xe6d5;</i></a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li><a href="javascript:;" onClick="myselfinfo()">个人信息</a></li>
                            <li><a href="#">切换账户</a></li>
                            <li><a href="#">退出</a></li>
                </ul>
            </li>
                    <li id="Hui-msg"> <a href="#" title="消息"><span class="badge badge-danger">1</span><i class="Hui-iconfont" style="font-size:18px">&#xe68a;</i></a> </li>
                    <li id="Hui-skin" class="dropDown right dropDown_hover"> <a href="javascript:;" class="dropDown_A" title="换肤"><i class="Hui-iconfont" style="font-size:18px">&#xe62a;</i></a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li><a href="javascript:;" data-val="default" title="默认（黑色）">默认（黑色）</a></li>
                            <li><a href="javascript:;" data-val="blue" title="蓝色">蓝色</a></li>
                            <li><a href="javascript:;" data-val="green" title="绿色">绿色</a></li>
                            <li><a href="javascript:;" data-val="red" title="红色">红色</a></li>
                            <li><a href="javascript:;" data-val="yellow" title="黄色">黄色</a></li>
                            <li><a href="javascript:;" data-val="orange" title="橙色">橙色</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</div>
</div>
</header>
<!--/_header 作为公共模版分离出去-->
<!--_header 作为公共模版分离出去-->
<header class="navbar-wrapper">
    <div class="navbar navbar-fixed-top">
        <div class="container-fluid cl"> <a class="logo navbar-logo f-l mr-10 hidden-xs" href="<?php echo url('Admin/index'); ?>">在吗</a> <a class="logo navbar-logo-m f-l mr-10 visible-xs" href="<?php echo url('Admin/index'); ?>">H-ui</a> 
            <span class="logo navbar-slogan f-l mr-10 hidden-xs">v1.0</span> 
            <a aria-hidden="false" class="nav-toggle Hui-iconfont visible-xs" href="javascript:;">&#xe667;</a>
<!--             <nav class="nav navbar-nav">
                <ul class="cl">
                    <li class="dropDown dropDown_hover"><a href="javascript:;" class="dropDown_A"><i class="Hui-iconfont">&#xe600;</i> 新增 <i class="Hui-iconfont">&#xe6d5;</i></a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li><a href="javascript:;" onclick="article_add('添加资讯','article-add.html')"><i class="Hui-iconfont">&#xe616;</i> 资讯</a></li>
                            <li><a href="javascript:;" onclick="picture_add('添加资讯','picture-add.html')"><i class="Hui-iconfont">&#xe613;</i> 图片</a></li>
                            <li><a href="javascript:;" onclick="product_add('添加资讯','product-add.html')"><i class="Hui-iconfont">&#xe620;</i> 产品</a></li>
                            <li><a href="javascript:;" onclick="member_add('添加用户','member-add.html','','510')"><i class="Hui-iconfont">&#xe60d;</i> 用户</a></li>
                        </ul>
                    </li>
                </ul>
            </nav> -->
            <nav id="Hui-userbar" class="nav navbar-nav navbar-userbar hidden-xs">
                <ul class="cl">
                    <li>超级管理员</li>
                    <li class="dropDown dropDown_hover"> <a href="#" class="dropDown_A"><?php echo $nick_name; ?> <i class="Hui-iconfont">&#xe6d5;</i></a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li><a href="javascript:;" onClick="myselfinfo()">个人信息</a></li>
                            <!-- <li><a href="#">切换账户</a></li> -->
                            <li><a href="<?php echo url('Logout/index');?>">退出</a></li>
                </ul>
            </li>
                    <!-- <li id="Hui-msg"> <a href="#" title="消息"><span class="badge badge-danger">1</span><i class="Hui-iconfont" style="font-size:18px">&#xe68a;</i></a> </li> -->
                    <li id="Hui-skin" class="dropDown right dropDown_hover"> <a href="javascript:;" class="dropDown_A" title="换肤"><i class="Hui-iconfont" style="font-size:18px">&#xe62a;</i></a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li><a href="javascript:;" data-val="default" title="默认（黑色）">默认（黑色）</a></li>
                            <li><a href="javascript:;" data-val="blue" title="蓝色">蓝色</a></li>
                            <li><a href="javascript:;" data-val="green" title="绿色">绿色</a></li>
                            <li><a href="javascript:;" data-val="red" title="红色">红色</a></li>
                            <li><a href="javascript:;" data-val="yellow" title="黄色">黄色</a></li>
                            <li><a href="javascript:;" data-val="orange" title="橙色">橙色</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</div>
</div>
</header>
<!--/_header 作为公共模版分离出去-->
<!--_menu 作为公共模版分离出去-->
<aside class="Hui-aside">
    <div class="menu_dropdown bk_2">
        <?php if(is_array($menu) || $menu instanceof \think\Collection || $menu instanceof \think\Paginator): $i = 0; $__LIST__ = $menu;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
            <dl <?php if($controller == $vo['controller']): ?> class="selected"<?php endif; ?>>
                <dt><i class="Hui-iconfont"><?php echo htmlspecialchars_decode($vo['icon']); ?></i> <?php echo $vo['mod_name']; ?><i class="Hui-iconfont menu_dropdown-arrow">&#xe6d5;</i></dt>
                    <dd <?php if($controller == $vo['controller']): ?> style="display: block;"<?php endif; ?>>
                    <ul><?php $children = isset($vo['children']) ? $vo['children'] : [];if(is_array($children) || $children instanceof \think\Collection || $children instanceof \think\Paginator): $i = 0; $__LIST__ = $children;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$v): $mod = ($i % 2 );++$i;if($v['is_show'] == '1'): ?>
                            <li <?php if($action == $v['license_url']): ?>  class="current"<?php endif; ?>><a href="<?php echo url($v['license_url']);?>" title="<?php echo $v['license_name']; ?>"><?php echo $v['license_name']; ?></a></li>
                            <?php endif; endforeach; endif; else: echo "" ;endif; ?>
                    </ul>
                </dd>
            </dl>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        
<!--         <dl id="menu-system">
            <dt><i class="Hui-iconfont">&#xe60d;</i> 用户管理<i class="Hui-iconfont menu_dropdown-arrow">&#xe6d5;</i></dt>
            <dd>
                <ul>
                    <li><a href="<?php echo url('User/admin_lists'); ?>" title="管理员列表">管理员列表</a></li>
                    <li><a href="<?php echo url('User/user_lists'); ?>" title="web用户列表">web用户列表</a></li>
                </ul>
            </dd>
        </dl>
        <dl id="menu-system">
            <dt><i class="Hui-iconfont">&#xe62e;</i> 系统管理<i class="Hui-iconfont menu_dropdown-arrow">&#xe6d5;</i></dt>
            <dd>
                <ul>
                    <li><a href="<?php echo url('Privilege/license'); ?>" title="权限管理">权限管理</a></li>
                    <li><a href="<?php echo url('Privilege/object'); ?>" title="栏目管理">栏目管理</a></li>
                    <li><a href="<?php echo url('Privilege/group'); ?>" title="角色管理">角色管理</a></li>
                </ul>
            </dd>
        </dl> -->
    </div>
</aside>
<div class="dislpayArrow hidden-xs"><a class="pngfix" href="javascript:void(0);" onClick="displaynavbar(this)"></a></div>
<!--/_menu 作为公共模版分离出去-->
<section class="Hui-article-box">
    <nav class="breadcrumb">
        <i class="Hui-iconfont"></i> <a href="/" class="maincolor">首页</a> 
        <span class="c-999 en">&gt;</span>
        <span class="c-666">我的桌面</span> 
        <a class="btn btn-success radius r" style="line-height:1.6em;margin-top:3px" href="javascript:location.replace(location.href);" title="刷新" ><i class="Hui-iconfont">&#xe68f;</i></a>
    </nav>
    <div class="Hui-article">
        <article class="cl pd-20">
            <p class="f-20 text-success">欢迎登录在吗管理后台！<!-- <span class="f-14">v2.3</span> --></p>
            <p>上次登录IP：<?php echo $user['last_login_ip']; ?>  上次登录时间：<?php echo date("Y-m-d H:i:s",$user['last_login_time']); ?></p>
            <!-- <table class="table table-border table-bordered table-bg">
                <thead>
                    <tr>
                        <th colspan="7" scope="col">信息统计</th>
                    </tr>
                    <tr class="text-c">
                        <th>统计</th>
                        <th>资讯库</th>
                        <th>图片库</th>
                        <th>产品库</th>
                        <th>用户</th>
                        <th>管理员</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="text-c">
                        <td>总数</td>
                        <td>92</td>
                        <td>9</td>
                        <td>0</td>
                        <td>8</td>
                        <td>20</td>
                    </tr>
                </tbody>
            </table>    -->
        </article>
        <footer class="footer">
            <p>感谢jQuery、layer、laypage、Validform、UEditor、My97DatePicker、iconfont、Datatables、WebUploaded、icheck、highcharts、bootstrap-Switch<br> Copyright &copy;2015 H-ui.admin v3.0 All Rights Reserved.<br> 本后台系统由<a href="http://www.h-ui.net/" target="_blank" title="H-ui前端框架">H-ui前端框架</a>提供前端技术支持</p>
</footer>
    </div>
</section>
<!--_footer 作为公共模版分离出去-->
<script type="text/javascript" src="__static__/lib/jquery/1.9.1/jquery.min.js"></script> 
<script type="text/javascript" src="__static__/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="__static__/h-ui/js/H-ui.js"></script> 
<script type="text/javascript" src="__static__/h-ui.admin/js/H-ui.admin.page.js"></script> 
<script type="text/javascript" src="__static__/h-ui.admin/js/admin.js?t=<?php echo time();?>"></script>
<!--/_footer /作为公共模版分离出去-->
<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript">

</script>
<!--/请在上方写此页面业务相关的脚本-->
</body>
</html>