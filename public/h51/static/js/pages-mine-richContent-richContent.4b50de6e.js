(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-mine-richContent-richContent"],{"4d23":function(t,n,e){var r=e("24fb");n=r(!1),n.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 项目的基本配置 */\r\n/* 基础 */\r\n/* 行为相关颜色 */\r\n/* 行为悬停颜色 */\r\n/* 行为背景颜色 */\r\n/* 渐变色 */\r\n/* 按钮长度 */\r\n/* 距离  */\r\n/* 字体 */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.richContent[data-v-5e9bfd6b]{overflow:hidden}.richContent .header[data-v-5e9bfd6b]{margin:%?60?% %?45?% %?30?%}.richContent .header .title[data-v-5e9bfd6b]{font-weight:700;font-size:%?40?%}.richContent .header .time[data-v-5e9bfd6b]{font-size:%?20?%;font-weight:500}',""]),t.exports=n},"723e":function(t,n,e){"use strict";var r=e("adb0"),i=e.n(r);i.a},"8c98":function(t,n,e){"use strict";var r;e.d(n,"b",(function(){return i})),e.d(n,"c",(function(){return a})),e.d(n,"a",(function(){return r}));var i=function(){var t=this,n=t.$createElement,e=t._self._c||n;return e("v-uni-view",{staticClass:"richContent"},[e("v-uni-view",{staticClass:"content p30"},[e("v-uni-rich-text",{attrs:{nodes:t.content}})],1)],1)},a=[]},"9ffc":function(t,n,e){"use strict";e.r(n);var r=e("b104"),i=e.n(r);for(var a in r)"default"!==a&&function(t){e.d(n,t,(function(){return r[t]}))}(a);n["default"]=i.a},adb0:function(t,n,e){var r=e("4d23");"string"===typeof r&&(r=[[t.i,r,""]]),r.locals&&(t.exports=r.locals);var i=e("4f06").default;i("221f74c9",r,!0,{sourceMap:!1,shadowMode:!1})},b104:function(t,n,e){"use strict";var r=e("4ea4");Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var i=r(e("5530"));e("96cf");var a=r(e("1da1")),c={name:"",data:function(){return{title:"",content:"",time:""}},onLoad:function(){var t=this.query.title;t&&(uni.setNavigationBarTitle({title:t}),this.title=t),this.getData()},methods:{getData:function(){var t=this;return(0,a.default)(regeneratorRuntime.mark((function n(){var e,r,a;return regeneratorRuntime.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return n.next=2,t.$fetch.post(t.query.url,(0,i.default)({},t.query.query));case 2:e=n.sent,r=e.data,e.msg,e.err,e.code,t.time=r.create_time,t.content=t.$until.formatRichText(t.$until.jointRichImage(r.content)),a=r.title,a&&(uni.setNavigationBarTitle({title:a}),t.title=a);case 11:case"end":return n.stop()}}),n)})))()}}};n.default=c},ec74:function(t,n,e){"use strict";e.r(n);var r=e("8c98"),i=e("9ffc");for(var a in i)"default"!==a&&function(t){e.d(n,t,(function(){return i[t]}))}(a);e("723e");var c,o=e("f0c5"),u=Object(o["a"])(i["default"],r["b"],r["c"],!1,null,"5e9bfd6b",null,!1,r["a"],c);n["default"]=u.exports}}]);