(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-promotion-withdraw-withdraw"],{"034b":function(n,r,t){"use strict";t.d(r,"b",(function(){return a})),t.d(r,"c",(function(){return c})),t.d(r,"a",(function(){return e}));var e={sWithdraw:t("64c1").default},a=function(){var n=this,r=n.$createElement,t=n._self._c||r;return t("v-uni-view",{staticClass:"withdraw"},[t("s-withdraw",{attrs:{total:n.total}})],1)},c=[]},2561:function(n,r,t){var e=t("24fb");r=e(!1),r.push([n.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 项目的基本配置 */\r\n/* 基础 */\r\n/* 行为相关颜色 */\r\n/* 行为悬停颜色 */\r\n/* 行为背景颜色 */\r\n/* 渐变色 */\r\n/* 按钮长度 */\r\n/* 距离  */\r\n/* 字体 */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */',""]),n.exports=r},"4acb":function(n,r,t){"use strict";t.r(r);var e=t("5c57"),a=t.n(e);for(var c in e)"default"!==c&&function(n){t.d(r,n,(function(){return e[n]}))}(c);r["default"]=a.a},"5c57":function(n,r,t){"use strict";var e=t("4ea4");Object.defineProperty(r,"__esModule",{value:!0}),r.default=void 0,t("96cf");var a=e(t("1da1")),c={name:"withdraw",data:function(){return{total:0}},onLoad:function(){this.withdrawMoney()},methods:{withdrawMoney:function(){var n=this;return(0,a.default)(regeneratorRuntime.mark((function r(){var t,e;return regeneratorRuntime.wrap((function(r){while(1)switch(r.prev=r.next){case 0:return r.next=2,n.$fetch.post("withdrawMoney",{type:1});case 2:t=r.sent,e=t.data,t.msg,t.err,t.code,n.total=e.withdrawal_money;case 8:case"end":return r.stop()}}),r)})))()}}};r.default=c},"6c36":function(n,r,t){"use strict";var e=t("99c4"),a=t.n(e);a.a},7453:function(n,r,t){"use strict";t.r(r);var e=t("034b"),a=t("4acb");for(var c in a)"default"!==c&&function(n){t.d(r,n,(function(){return a[n]}))}(c);t("6c36");var o,i=t("f0c5"),u=Object(i["a"])(a["default"],e["b"],e["c"],!1,null,"0f6b74cf",null,!1,e["a"],o);r["default"]=u.exports},"99c4":function(n,r,t){var e=t("2561");"string"===typeof e&&(e=[[n.i,e,""]]),e.locals&&(n.exports=e.locals);var a=t("4f06").default;a("337715e6",e,!0,{sourceMap:!1,shadowMode:!1})}}]);