# 轻轨(lightRail)框架

轻轨(lightRail)框架是一款PHP语言编写的应用框架，主要是用于替换自己手上的一些旧项目中的thinkphp5框架。

由于这几年PHP发展比较快速，而thinkphp5已经比较难适应新的发展，自己手中有几款开发多年的系统，原先基于thinkphp5框架开发的，在新的PHP环境下，每次PHP升级就需要花费大量时间去修正thinkphp5框架的代码。

由于这些问题困扰，所以最终决定一次性解决问题，开发一款新的框架，同时保留原来thinkphp5的语法习惯，这样可以做到基本不改动现有代码的情况下进行框架替换。

这款新框架我称之为“轻轨(lightRail)”。轻的意思是指新框架执行效率高，实际框架执行效率远高于thinkphp5，轨指的是遵循了thinkphp5的语法轨迹。

## 相关说明 ##

轻轨框架并不是对所有thinkphp5语法的全实现，主要是对于自己的旧项目中已经使用的语法实现，所以如果您有一些不常用的语法在旧项目中，那么可能替换会有些问题，当然如果您遇到类似问题也可以直接QQ：804644245联系我进行反馈，我看情况是否能增加这些不常用语法。

另外，在轻轨框架里面我去除了一些奇葩的语法使用，例如下面的数据库操作代码：

    Db::field('user.name,role.title')->table('think_user user,think_role role')->limit(10)->select();

在轻轨里面可以使用下面比较正常的方式来写：

    Db::table('think_user user,think_role role')->field('user.name,role.title')->limit(10)->select();

更多的区别可以参看鲶鱼cms官网相关内容。

## 安装lightRail ##

轻轨框架使用Composer方式安装，可以使用下面的Composer命令进行安装：

> composer create-project yanzicms/light-rail-app light-rail

## 框架协议 ##

轻轨(lightRail)框架使用Apache-2.0开源协议发布，如果有跟我同样需要的开发者，可以拿去使用。

