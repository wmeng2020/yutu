(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-mine-setting-about"],{"09ce":function(t,r,e){"use strict";e("a9e3"),Object.defineProperty(r,"__esModule",{value:!0}),r.default=void 0;var n={name:"c-cell",data:function(){return{}},props:{height:{type:[String,Number],default:""},thumb:{type:String,default:""},size:{type:[String,Number],default:90},isDefault:{type:Boolean,default:!1},circle:{type:Boolean,default:!1},isPadding:{type:Boolean,default:!0},title:{type:String,default:""},sub:{type:String,default:""},border:{type:Boolean,default:!1},exra:{type:Boolean,default:!0},arrow:{type:Boolean,default:!1},radius:{type:[String,Number],default:"10rpx"},subColor:{type:[String],default:"#fff"}},methods:{click:function(){this.$emit("click")}},computed:{radiusType:function(){return typeof this.radius}}};r.default=n},"0c34":function(t,r,e){"use strict";var n=e("5850"),a=e.n(n);a.a},"17d2":function(t,r,e){"use strict";e.d(r,"b",(function(){return a})),e.d(r,"c",(function(){return i})),e.d(r,"a",(function(){return n}));var n={cThumb:e("6e29").default,uniIcons:e("aa3d").default},a=function(){var t=this,r=t.$createElement,e=t._self._c||r;return e("v-uni-view",{staticClass:"c-cell c-row c-row-middle",class:{p30:t.isPadding},style:{height:t.height?t.height+"rpx":"auto",borderBottom:t.border?"1px solid #f8f8f8":"none",borderRadius:"number"==t.radiusType?t.radius+"rpx":t.radius},on:{click:function(r){arguments[0]=r=t.$handleEvent(r),t.click.apply(void 0,arguments)}}},[t.thumb?e("v-uni-view",{staticClass:"image"},[e("c-thumb",{attrs:{src:t.thumb,isDefault:t.isDefault,size:t.size,circle:t.circle,equal:t.circle}})],1):t._e(),e("v-uni-view",{staticClass:"title c-row c-column flex-cover "},[e("v-uni-view",{staticClass:"main-title"},[t._t("title",[t._v(t._s(t.title))])],2),e("v-uni-view",{staticClass:"sub-title",style:{color:t.subColor}},[t._t("sub",[t._v(t._s(t.sub))])],2)],1),t.exra?e("v-uni-view",{staticClass:"extend c-row c-row-middle"},[t._t("default"),t.arrow?e("uni-icons",{staticClass:"exra",attrs:{type:"arrowright",color:"#fff"}}):t._e()],2):t._e()],1)},i=[]},"1adf":function(t,r,e){var n=e("24fb");r=n(!1),r.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 项目的基本配置 */\r\n/* 基础 */\r\n/* 行为相关颜色 */\r\n/* 行为悬停颜色 */\r\n/* 行为背景颜色 */\r\n/* 渐变色 */\r\n/* 按钮长度 */\r\n/* 距离  */\r\n/* 字体 */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.about .footer[data-v-469080ec]{position:fixed;left:50%;bottom:%?30?%;-webkit-transform:translateX(-50%);transform:translateX(-50%);font-size:%?26?%}.about .footer .about-text[data-v-469080ec]{font-size:%?20?%}',""]),t.exports=r},"2e9c":function(t,r,e){"use strict";e.r(r);var n=e("4ec9"),a=e.n(n);for(var i in n)"default"!==i&&function(t){e.d(r,t,(function(){return n[t]}))}(i);r["default"]=a.a},"2fa5":function(t,r,e){"use strict";e.r(r);var n=e("7fe3"),a=e("2e9c");for(var i in a)"default"!==i&&function(t){e.d(r,t,(function(){return a[t]}))}(i);e("cf85");var c,o=e("f0c5"),u=Object(o["a"])(a["default"],n["b"],n["c"],!1,null,"469080ec",null,!1,n["a"],c);r["default"]=u.exports},"4c11":function(t,r,e){"use strict";e.r(r);var n=e("17d2"),a=e("64d9");for(var i in a)"default"!==i&&function(t){e.d(r,t,(function(){return a[t]}))}(i);e("0c34");var c,o=e("f0c5"),u=Object(o["a"])(a["default"],n["b"],n["c"],!1,null,"91d284f8",null,!1,n["a"],c);r["default"]=u.exports},"4ec9":function(t,r,e){"use strict";var n=e("4ea4");Object.defineProperty(r,"__esModule",{value:!0}),r.default=void 0,e("96cf");var a=n(e("1da1")),i=n(e("5530")),c=e("2f62"),o={name:"about",data:function(){return{}},computed:(0,i.default)({},(0,c.mapState)(["APP_INFO"])),onLoad:function(){this.about()},methods:{about:function(){var t=this;return(0,a.default)(regeneratorRuntime.mark((function r(){var e;return regeneratorRuntime.wrap((function(r){while(1)switch(r.prev=r.next){case 0:return r.next=2,t.$fetch.post("about",{});case 2:e=r.sent,e.data,e.msg,e.err,e.code;case 7:case"end":return r.stop()}}),r)})))()}}};r.default=o},5038:function(t,r,e){var n=e("24fb");r=n(!1),r.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 项目的基本配置 */\r\n/* 基础 */\r\n/* 行为相关颜色 */\r\n/* 行为悬停颜色 */\r\n/* 行为背景颜色 */\r\n/* 渐变色 */\r\n/* 按钮长度 */\r\n/* 距离  */\r\n/* 字体 */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.c-cell .thumb[data-v-91d284f8]{margin-right:%?15?%}.c-cell .title .main-title[data-v-91d284f8]{font-size:%?30?%;max-width:%?450?%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#fff;font-weight:500}.c-cell .title .sub-title[data-v-91d284f8]{font-size:%?25?%;color:#fff;margin-top:%?10?%}.c-cell .image[data-v-91d284f8]{margin-right:%?15?%}.c-cell .extend[data-v-91d284f8]{font-size:%?26?%;text-align:right}.c-cell .extend .exra[data-v-91d284f8]{margin-left:%?10?%}',""]),t.exports=r},5850:function(t,r,e){var n=e("5038");"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var a=e("4f06").default;a("085ffaaf",n,!0,{sourceMap:!1,shadowMode:!1})},"64d9":function(t,r,e){"use strict";e.r(r);var n=e("09ce"),a=e.n(n);for(var i in n)"default"!==i&&function(t){e.d(r,t,(function(){return n[t]}))}(i);r["default"]=a.a},"66cd":function(t,r,e){"use strict";e.r(r);var n=e("9362"),a=e.n(n);for(var i in n)"default"!==i&&function(t){e.d(r,t,(function(){return n[t]}))}(i);r["default"]=a.a},"6e29":function(t,r,e){"use strict";e.r(r);var n=e("f79c"),a=e("66cd");for(var i in a)"default"!==i&&function(t){e.d(r,t,(function(){return a[t]}))}(i);e("8712");var c,o=e("f0c5"),u=Object(o["a"])(a["default"],n["b"],n["c"],!1,null,"4cbc5cc2",null,!1,n["a"],c);r["default"]=u.exports},"7fe3":function(t,r,e){"use strict";e.d(r,"b",(function(){return a})),e.d(r,"c",(function(){return i})),e.d(r,"a",(function(){return n}));var n={cCell:e("4c11").default,cThumb:e("6e29").default},a=function(){var t=this,r=t.$createElement,e=t._self._c||r;return e("v-uni-view",{staticClass:"about"},[e("v-uni-view",{staticClass:"card m30"},[e("c-cell",{attrs:{title:"官方邮箱"}},[e("v-uni-text",[t._v("121212")])],1),e("c-cell",{attrs:{title:"官方微信"}},[e("v-uni-text",[t._v("121212")])],1),e("c-cell",{attrs:{title:"官方QQ"}},[e("v-uni-text",[t._v("121212")])],1)],1),e("v-uni-view",{staticClass:"footer c-row c-column c-row-middle-center"},[e("v-uni-view",{},[e("c-thumb",{attrs:{src:"/static/shop/product.png",size:"160",equal:""}})],1),e("v-uni-text",{staticClass:"mt20 mb20"},[t._v("商务合作")]),e("v-uni-text",{staticClass:"about-text"},[t._v("dvdsnlsdvd@2020 "+t._s(t.APP_INFO.name)+"电竞")])],1)],1)},i=[]},8712:function(t,r,e){"use strict";var n=e("a3ad"),a=e.n(n);a.a},9362:function(t,r,e){"use strict";(function(t){e("a9e3"),Object.defineProperty(r,"__esModule",{value:!0}),r.default=void 0;var n={name:"c-thumb",data:function(){return{loadStatus:!0,errorStatus:!1}},props:{src:{type:String,default:""},mode:{type:String,default:"widthFix"},height:{type:[String,Number],default:""},size:{type:[String,Number],default:320},circle:{type:Boolean,default:!1},radius:{type:[String,Number],default:10},equal:{type:Boolean,default:!1},loadColor:{type:String,default:"#ccc"},loadSize:{type:[String,Number],default:30}},methods:{load:function(t){this.loadStatus=!1,this.errorStatus=!1},error:function(t){this.errorStatus=!0,this.loadStatus=!1,this.equal=!0},click:function(){this.$emit("click")}},onLoad:function(){t("log",this.src," at components/control/c-thumb/c-thumb.vue:147")},mounted:function(){this.src||(this.loadStatus=!1,this.errorStatus=!0,this.equal=!0)}};r.default=n}).call(this,e("0de9")["log"])},a3ad:function(t,r,e){var n=e("a3e0");"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var a=e("4f06").default;a("4297bedc",n,!0,{sourceMap:!1,shadowMode:!1})},a3e0:function(t,r,e){var n=e("24fb");r=n(!1),r.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 项目的基本配置 */\r\n/* 基础 */\r\n/* 行为相关颜色 */\r\n/* 行为悬停颜色 */\r\n/* 行为背景颜色 */\r\n/* 渐变色 */\r\n/* 按钮长度 */\r\n/* 距离  */\r\n/* 字体 */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* 文字基本颜色 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.c-thumb[data-v-4cbc5cc2]{position:relative;overflow:hidden;box-sizing:border-box}.c-thumb.active[data-v-4cbc5cc2]{max-height:%?100?%}.c-thumb .load[data-v-4cbc5cc2], .c-thumb .err[data-v-4cbc5cc2]{position:absolute;top:0;bottom:0;right:0;left:0;background:rgba(0,0,0,.1)}.c-thumb .load .container[data-v-4cbc5cc2], .c-thumb .err .container[data-v-4cbc5cc2]{-webkit-animation:rotate-data-v-4cbc5cc2 1s linear infinite;animation:rotate-data-v-4cbc5cc2 1s linear infinite}.c-thumb .err[data-v-4cbc5cc2]{max-height:%?200?%}.c-thumb .err uni-image[data-v-4cbc5cc2]{width:50%}.c-thumb .err.isDefault[data-v-4cbc5cc2]{padding:0}.c-thumb .err.isDefault uni-image[data-v-4cbc5cc2]{width:100%}@keyframes rotate-data-v-4cbc5cc2{from{-webkit-transform:rotate(0);transform:rotate(0)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}@-moz-keyframes rotate-data-v-4cbc5cc2{from{transform:rotate(0)}to{transform:rotate(1turn)}}@-webkit-keyframes rotate-data-v-4cbc5cc2{from{-webkit-transform:rotate(0);transform:rotate(0)}to{-webkit-transform:rotate(1turn);transform:rotate(1turn)}}@-o-keyframes rotate-data-v-4cbc5cc2{from{transform:rotate(0)}to{transform:rotate(1turn)}}',""]),t.exports=r},a84a:function(t,r,e){var n=e("1adf");"string"===typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var a=e("4f06").default;a("280c4250",n,!0,{sourceMap:!1,shadowMode:!1})},cf85:function(t,r,e){"use strict";var n=e("a84a"),a=e.n(n);a.a},f79c:function(t,r,e){"use strict";e.d(r,"b",(function(){return a})),e.d(r,"c",(function(){return i})),e.d(r,"a",(function(){return n}));var n={uniIcons:e("aa3d").default},a=function(){var t=this,r=t.$createElement,e=t._self._c||r;return e("v-uni-view",{staticClass:"c-thumb c-row c-row-middle-center",class:{active:1==t.loadStatus},style:{borderRadius:t.circle?"50%":t.radius+"rpx",width:t.size+"rpx",height:t.equal?t.size+"rpx":""},on:{click:function(r){arguments[0]=r=t.$handleEvent(r),t.click.apply(void 0,arguments)}}},[e("v-uni-image",{style:{height:t.equal||t.circle?t.size+"rpx":t.height+"rpx",width:t.size+"rpx",minHeight:t.equal||t.circle?t.size+"rpx":"auto"},attrs:{src:t.src,mode:t.height?"":t.mode},on:{load:function(r){arguments[0]=r=t.$handleEvent(r),t.load.apply(void 0,arguments)},error:function(r){arguments[0]=r=t.$handleEvent(r),t.error.apply(void 0,arguments)}}}),t.loadStatus?e("v-uni-view",{staticClass:"load c-row c-row-middle-center"},[e("v-uni-view",{staticClass:"container"},[e("uni-icons",{attrs:{type:"reload",color:t.loadColor,size:t.loadSize}})],1)],1):t._e(),t.errorStatus?e("v-uni-view",{staticClass:"err c-row c-row-middle-center"},[e("v-uni-image",{attrs:{src:"/static/default/img-error.png",mode:"widthFix"}})],1):t._e()],1)},i=[]}}]);