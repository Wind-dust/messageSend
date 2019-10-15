define({ "api": [
  {
    "success": {
      "fields": {
        "Success 200": [
          {
            "group": "Success 200",
            "optional": false,
            "field": "varname1",
            "description": "<p>No type.</p>"
          },
          {
            "group": "Success 200",
            "type": "String",
            "optional": false,
            "field": "varname2",
            "description": "<p>With type.</p>"
          }
        ]
      }
    },
    "type": "",
    "url": "",
    "version": "0.0.0",
    "filename": "./public/doc/main.js",
    "group": "D__Dev_Dev_Data_www_messageSend_public_doc_main_js",
    "groupTitle": "D__Dev_Dev_Data_www_messageSend_public_doc_main_js",
    "name": ""
  },
  {
    "type": "post",
    "url": "/",
    "title": "添加服务类型",
    "description": "<p>addBusiness</p>",
    "group": "admin_Administrator",
    "name": "addBusiness",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>title</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "price",
            "description": "<p>服务价格(最多保留小数点后5位)</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "donate_num",
            "description": "<p>赠送数量，默认0</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:标题为空 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/addBusiness"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "审核服务充值",
    "description": "<p>aduitRechargeApplication</p>",
    "group": "admin_Administrator",
    "name": "aduitRechargeApplication",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "status",
            "description": "<p>状态 2.已审核 3.取消</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "message",
            "description": "<p>审核留言</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:id不存在或者不为数字 / 3002:status格式错误 / 3003:已审核 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/aduitRechargeApplication"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "账户资质审核",
    "description": "<p>auditUserQualification</p>",
    "group": "admin_Administrator",
    "name": "auditUserQualification",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "status",
            "description": "<p>审核状态:1,已提交;2,审核中;3,审核通过;4,审核不通过</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:id不存在或者不为数字 / 3002:status码错误 / 3003:该资质已审核</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/auditUserQualification"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取服务类型",
    "description": "<p>getBusiness</p>",
    "group": "admin_Administrator",
    "name": "getBusiness",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码 默认1</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数 默认10</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "getall",
            "description": "<p>1 获取全部</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:页码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/getBusiness"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "服务充值列表",
    "description": "<p>getRechargeApplication</p>",
    "group": "admin_Administrator",
    "name": "getRechargeApplication",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码 默认1</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数 默认10</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/getRechargeApplication"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取该用户该服务价格",
    "description": "<p>getUserEquities</p>",
    "group": "admin_Administrator",
    "name": "getUserEquities",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>账户手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "business_id",
            "description": "<p>业务服务id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:business_id不存在或者不是数字 / 3002:用户不存在 / 3003:price不能小于0 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/getUserEquities"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "账户资质列表",
    "description": "<p>getUserQualificationRecord</p>",
    "group": "admin_Administrator",
    "name": "getUserQualificationRecord",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码 默认1</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数 默认10</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/getUserQualificationRecord"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "服务充值申请",
    "description": "<p>rechargeApplication</p>",
    "group": "admin_Administrator",
    "name": "rechargeApplication",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>充值账户手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "business_id",
            "description": "<p>业务服务id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "num",
            "description": "<p>充值条数</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:手机号格式错误 / 3002:business_id不存在或者不为数字 / 3003:用户不存在 / 3003:price不能小于0 / 3004:该用户没有该服务，无法充值</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/rechargeApplication"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "修改服务类型",
    "description": "<p>updateBusiness</p>",
    "group": "admin_Administrator",
    "name": "updateBusiness",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "title",
            "description": "<p>标题</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "price",
            "description": "<p>服务价格(最多保留小数点后5位)</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "donate_num",
            "description": "<p>赠送数量，默认0</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/administrator/updateBusiness"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Administrator.php",
    "groupTitle": "admin_Administrator"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取会员列表",
    "description": "<p>getUsers</p>",
    "group": "admin_Users",
    "name": "getUsers",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "mobile",
            "description": "<p>手机号</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "page",
            "description": "<p>页码</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "pagenum",
            "description": "<p>查询条数</p>"
          }
        ]
      },
      "examples": [
        {
          "title": "(data) {Array} 返回用户列表",
          "content": "[\n\"code\":\"200\",返回code码\n\"totle\":\"82\",总记录条数\n {\"id\":9,\"tel\":15502123212,\n  \"name\":\"喜蓝葡萄酒\",\n  \"status\":\"1\",\n  \"image\":\"\",\"title\":\"\",\n  \"desc\":\"江浙沪皖任意2瓶包邮，其他地区参考实际支付运费\"\n },\n]",
          "type": "json"
        }
      ]
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:用户列表空 / 3001:手机号格式错误 / 3002:页码和查询条数只能是数字</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "totle",
            "description": "<p>总结果条数</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "object_array",
            "optional": false,
            "field": "data",
            "description": "<p>结果</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>用户ID</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "user_type",
            "description": "<p>用户类型1.普通账户2.总店账户</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "user_identity",
            "description": "<p>用户身份1.普通,2.钻石会员3.创业店主4.boss合伙人</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "sex",
            "description": "<p>用户性别 1.男 2.女 3.未确认</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "nick_name",
            "description": "<p>微信昵称</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "true_name",
            "description": "<p>真实姓名</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "brithday",
            "description": "<p>生日</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "avatar",
            "description": "<p>微信头像</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>手机号</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "email",
            "description": "<p>email</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/User/getUsers"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/User.php",
    "groupTitle": "admin_Users"
  },
  {
    "type": "post",
    "url": "/",
    "title": "设置用户信息",
    "description": "<p>seetingUser</p>",
    "group": "admin_Users",
    "name": "seetingUser",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "uid",
            "description": "<p>账户id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": true,
            "field": "user_status",
            "description": "<p>账户服务状态 1停止服务 2启用服务</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": true,
            "field": "reservation_service",
            "description": "<p>可否预用服务 1不可 2可以</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:用户列表空 / 3001:user_status格式错误 / 3002:reservation_service格式错误 / 3003:uid格式错误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/user/seetingUser"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/User.php",
    "groupTitle": "admin_Users"
  },
  {
    "type": "post",
    "url": "/",
    "title": "设置用户服务项目",
    "description": "<p>seetingUserEquities</p>",
    "group": "admin_Users",
    "name": "seetingUserEquities",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "uid",
            "description": "<p>账户id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "business_id",
            "description": "<p>服务id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": true,
            "field": "agency_price",
            "description": "<p>代理价格，默认统一服务价格</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:business_id格式错误或者不存在 / 3002:agency_price格式错误 / 3003:uid格式错误 / 3004:代理价格不能低于统一服务价 / 3005:该服务已添加 / 3006:子账户服务无法设置</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/user/seetingUserEquities"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/User.php",
    "groupTitle": "admin_Users"
  },
  {
    "type": "post",
    "url": "/",
    "title": "添加后台管理员",
    "description": "<p>addAdmin</p>",
    "group": "admin_admin",
    "name": "addAdmin",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "admin_name",
            "description": "<p>添加的用户名</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "passwd",
            "description": "<p>默认为:123456</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": true,
            "field": "stype",
            "description": "<p>添加的管理员类型 1.管理员 2超级管理员  默认为:1</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:账号不能为空 / 3002:密码必须为6-16个任意字符 / 3003:只有root账户可以添加超级管理员 / 3004:该账号已存在 / 3006:添加失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/addadmin"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "添加管理员到权限组",
    "description": "<p>addAdminPermissions</p>",
    "group": "admin_admin",
    "name": "addAdminPermissions",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "group_id",
            "description": "<p>分组id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "add_admin_id",
            "description": "<p>添加管理员id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:分组id错误 / 3003:权限分组不存在 /3004:添加用户不存在 / 3005:管理员id有误 / / 3006:该成员已存在 / 3007:添加失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/addadminpermissions"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "添加接口权限列表",
    "description": "<p>addPermissionsApi</p>",
    "group": "admin_admin",
    "name": "addPermissionsApi",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "menu_id",
            "description": "<p>菜单id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "api_name",
            "description": "<p>接口url</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "stype",
            "description": "<p>接口curd权限 1.增 2.删 3.改</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cn_name",
            "description": "<p>权限名称</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>权限的详细描述</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:菜单id有误 / 3002:接口url不能为空 / 3003:接口权操作类型 /3004:权限名称不能为空 / 3005:接口已存在 / 3006:菜单不存在 / 3007:添加失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/addpermissionsapi"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "添加权限分组",
    "description": "<p>addPermissionsGroup</p>",
    "group": "admin_admin",
    "name": "addPermissionsGroup",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "group_name",
            "description": "<p>分组名称</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>详细描述</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:分组名称错误 /3005:添加失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/addpermissionsgroup"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "为权限组添加菜单接口",
    "description": "<p>addPermissionsGroupPower</p>",
    "group": "admin_admin",
    "name": "addPermissionsGroupPower",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "group_id",
            "description": "<p>分组id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "permissions",
            "description": "<p>权限分组:{&quot;1&quot;:{&quot;2&quot;:1,&quot;3&quot;:0},&quot;2&quot;:{&quot;4&quot;:1,&quot;5&quot;:0}}</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:分组id错误 / 3003:权限分组不存在 / 3004:权限分组不能为空 / 3005:permissions数据有误 / 3006:菜单不存在 / 3007:更改失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/addpermissionsgrouppower"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "cms左侧菜单",
    "description": "<p>cmsMenu</p>",
    "group": "admin_admin",
    "name": "cmsMenu",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "type_name",
            "description": "<p>分类名称</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/cmsmenu"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "cms菜单详情",
    "description": "<p>cmsMenuOne</p>",
    "group": "admin_admin",
    "name": "cmsMenuOne",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": "<p>菜单id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001.菜单id有误</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "type_name",
            "description": "<p>分类名称</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/cmsmenuone"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "删除权限组的成员",
    "description": "<p>delAdminPermissions</p>",
    "group": "admin_admin",
    "name": "delAdminPermissions",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "group_id",
            "description": "<p>分组id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "del_admin_id",
            "description": "<p>要删除的admin_id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  / 3001:分组id错误 / 3003:权限分组不存在 /3004:删除用户不存在 / 3005:管理员id有误 /3006:删除的管理员不存在 / 3007:删除失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/deladminpermissions"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "修改保存cms菜单",
    "description": "<p>editMenu</p>",
    "group": "admin_admin",
    "name": "editMenu",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": "<p>菜单id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "name",
            "description": "<p>菜单名称</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001.菜单id有误 / 3002:菜单id不存在 / 3003:修改失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/editmenu"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "修改接口权限名称和详情",
    "description": "<p>editPermissionsApi</p>",
    "group": "admin_admin",
    "name": "editPermissionsApi",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cn_name",
            "description": "<p>权限名称</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>权限的详细描述</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:接口id有误 /3004:权限名称不能为空 / 3005:接口不存在 / 3007:修改失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/editpermissionsapi"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "修改权限分组",
    "description": "<p>editPermissionsGroup</p>",
    "group": "admin_admin",
    "name": "editPermissionsGroup",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "group_id",
            "description": "<p>权限分组ID</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "group_name",
            "description": "<p>分组名称</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>详细描述</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:分组名称错误 / 3003:修改的用户不存在 / 3004:分组id错误 /3005:修改失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/editpermissionsgroup"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取用户或所有的权限组列表",
    "description": "<p>getAdminGroup</p>",
    "group": "admin_admin",
    "name": "getAdminGroup",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "get_admin_id",
            "description": "<p>管理员id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:管理员id有误</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "group_name",
            "description": "<p>组名</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>描述</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getadmingroup"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取登录用户信息",
    "description": "<p>getAdminInfo</p>",
    "group": "admin_admin",
    "name": "getAdminInfo",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  / 5000:请重新登录 2.5001:账号已停用</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "admin_name",
            "description": "<p>管理员名</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "group",
            "description": "<p>所属权限组列表</p>"
          },
          {
            "group": "返回",
            "type": "Int",
            "optional": false,
            "field": "stype",
            "description": "<p>用户类型 1.后台管理员 2.超级管理员</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getadmininfo"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取后台管理员信息",
    "description": "<p>getAdminUsers</p>",
    "group": "admin_admin",
    "name": "getAdminUsers",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  / 5000:请重新登录 2.5001:账号已停用</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "admin_name",
            "description": "<p>管理员名</p>"
          },
          {
            "group": "返回",
            "type": "data",
            "optional": false,
            "field": "stype",
            "description": "<p>用户类型 1.后台管理员 2.超级管理员</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getAdminUsers"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取权限组信息",
    "description": "<p>getGroupInfo</p>",
    "group": "admin_admin",
    "name": "getGroupInfo",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "group_id",
            "description": "<p>管理员id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:分组id错误</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "group_name",
            "description": "<p>组名</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>描述</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getgroupinfo"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取接口权限列表",
    "description": "<p>getPermissionsApi</p>",
    "group": "admin_admin",
    "name": "getPermissionsApi",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "group_name",
            "description": "<p>组名</p>"
          },
          {
            "group": "返回",
            "type": "Int",
            "optional": false,
            "field": "menu_id",
            "description": "<p>所属菜单</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "stype",
            "description": "<p>权限类型 1.增 2.删 3.改</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "cn_name",
            "description": "<p>名称</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>描述</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getpermissionsapi"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取接口权限详情",
    "description": "<p>getPermissionsApiOne</p>",
    "group": "admin_admin",
    "name": "getPermissionsApiOne",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:接口id有误</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "group_name",
            "description": "<p>组名</p>"
          },
          {
            "group": "返回",
            "type": "Int",
            "optional": false,
            "field": "menu_id",
            "description": "<p>所属菜单</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "stype",
            "description": "<p>权限类型 1.增 2.删 3.改</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "cn_name",
            "description": "<p>名称</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "content",
            "description": "<p>描述</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getpermissionsapione"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取权限组下的管理员",
    "description": "<p>getPermissionsGroupAdmin</p>",
    "group": "admin_admin",
    "name": "getPermissionsGroupAdmin",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "group_id",
            "description": "<p>分组id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001:分组id错误</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "admin_name",
            "description": "<p>名字</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getpermissionsgroupadmin"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取权限列表",
    "description": "<p>getPermissionsList</p>",
    "group": "admin_admin",
    "name": "getPermissionsList",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "group_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:分组id错误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/getpermissionslist"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "后台登录",
    "description": "<p>login</p>",
    "group": "admin_admin",
    "name": "login",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "admin_name",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "passwd",
            "description": "<p>密码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:账号密码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/login"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "修改密码",
    "description": "<p>midifyPasswd</p>",
    "group": "admin_admin",
    "name": "midifyPasswd",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "passwd",
            "description": "<p>用户密码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "new_passwd1",
            "description": "<p>新密码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "new_passwd2",
            "description": "<p>确认密码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:密码错误 / 3002:密码必须为6-16个任意字符 / 3003:老密码不能为空 / 3004:密码确认有误  / 3005:修改密码失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/admin/midifypasswd"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Admin.php",
    "groupTitle": "admin_admin"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取区级列表",
    "description": "<p>getArea</p>",
    "group": "admin_provinces",
    "name": "getArea",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "cityId",
            "description": "<p>市级id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:区列表空 / 3001:市级id不存在 / 3002:市级id只能是数字</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "data",
            "description": "<p>结果</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "area_name",
            "description": "<p>名称</p>"
          },
          {
            "group": "data",
            "type": "Number",
            "optional": false,
            "field": "pid",
            "description": "<p>父级id</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/provinces/getArea"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Provinces.php",
    "groupTitle": "admin_provinces"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取市级列表",
    "description": "<p>getCity</p>",
    "group": "admin_provinces",
    "name": "getCity",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "provinceId",
            "description": "<p>省级id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:市列表空 / 3001:省级id不存在 / 3002:省级id只能是数字</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>结果</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "area_name",
            "description": "<p>名称</p>"
          },
          {
            "group": "data",
            "type": "Number",
            "optional": false,
            "field": "pid",
            "description": "<p>父级id</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/provinces/getCity"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Provinces.php",
    "groupTitle": "admin_provinces"
  },
  {
    "type": "post",
    "url": "/",
    "title": "省市列表",
    "description": "<p>getProvinceCity</p>",
    "group": "admin_provinces",
    "name": "getProvinceCity",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:省市区列表为空</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>结果</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "area_name",
            "description": "<p>名称</p>"
          },
          {
            "group": "data",
            "type": "Number",
            "optional": false,
            "field": "pid",
            "description": "<p>父级id</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/provinces/getProvinceCity"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Provinces.php",
    "groupTitle": "admin_provinces"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取运费模版的剩余可选省市列表",
    "description": "<p>getProvinceCityByfreight</p>",
    "group": "admin_provinces",
    "name": "getProvinceCityByfreight",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "freight_id",
            "description": "<p>运费模版详情id</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "freight_detail_id",
            "description": "<p>运费模版价格详情id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:运费模版Id必须为数字 / 3002:运费模版价格详情id必须为数字</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>结果</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "area_name",
            "description": "<p>名称</p>"
          },
          {
            "group": "data",
            "type": "Number",
            "optional": false,
            "field": "pid",
            "description": "<p>父级id</p>"
          },
          {
            "group": "data",
            "type": "Number",
            "optional": false,
            "field": "status",
            "description": "<p>1.可选的 2.已选的</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/provinces/getprovincecitybyfreight"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Provinces.php",
    "groupTitle": "admin_provinces"
  },
  {
    "type": "post",
    "url": "/",
    "title": "上传单个图片",
    "description": "<p>uploadFile</p>",
    "group": "admin_upload",
    "name": "uploadFilee",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "file",
            "optional": false,
            "field": "image",
            "description": "<p>图片</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>结果</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/upload/uploadfile"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Upload.php",
    "groupTitle": "admin_upload"
  },
  {
    "type": "post",
    "url": "/",
    "title": "上传多个图片",
    "description": "<p>uploadMultiFile</p>",
    "group": "admin_upload",
    "name": "uploadMultiFile",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "file",
            "optional": false,
            "field": "images",
            "description": "<p>图片集</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空 / 3004:上传文件不能为空</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>上传后的图片路径</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/admin/upload/uploadmultifile"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/admin/controller/Upload.php",
    "groupTitle": "admin_upload"
  },
  {
    "type": "post",
    "url": "/",
    "title": "关于我们",
    "description": "<p>getAboutus</p>",
    "group": "index_Aboutus",
    "name": "getAboutus",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "aboutus_id",
            "description": "<p>对应商品id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/aboutus/getAboutus"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/aboutus.php",
    "groupTitle": "index_Aboutus"
  },
  {
    "type": "post",
    "url": "/",
    "title": "应用案例",
    "description": "<p>getApplicationCase</p>",
    "group": "index_ApplicationCase",
    "name": "getApplicationCase",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "applicationCase_id",
            "description": "<p>对应商品id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/applicationCase/getApplicationCase"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/ApplicationCase.php",
    "groupTitle": "index_ApplicationCase"
  },
  {
    "type": "post",
    "url": "/",
    "title": "下载中心",
    "description": "<p>getDownloadCenter</p>",
    "group": "index_DownloadCenter",
    "name": "getDownloadCenter",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "downloadCenter_id",
            "description": "<p>对应商品id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/downloadCenter/getDownloadCenter"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/DownloadCenter.php",
    "groupTitle": "index_DownloadCenter"
  },
  {
    "type": "post",
    "url": "/",
    "title": "用户留言",
    "description": "<p>addGuestbook</p>",
    "group": "index_Guestbook",
    "name": "addGuestbook",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "name",
            "description": "<p>姓名</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "unit",
            "description": "<p>单位</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>手机</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "phone",
            "description": "<p>座机</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "qq",
            "description": "<p>QQ</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "type",
            "description": "<p>产品线:1,短信验证码；2，行业手机彩信，3，语言验证，4行业营销短信，5企业流量 6国际业务</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "email",
            "description": "<p>邮箱</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "message",
            "description": "<p>留言</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:手机号码错误 / 3000:未获取到数据 / 3002.type参数错误 / 3003.qq格式错误 / 3004:邮箱校验错误 / 3005:名称为空或者长度超出30个字符 / 3006:单位为空或者长度超出50个字符</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/guestbook/addGuestbook"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Guestbook.php",
    "groupTitle": "index_Guestbook"
  },
  {
    "type": "post",
    "url": "/",
    "title": "产品中心",
    "description": "<p>getProduct</p>",
    "group": "index_Product",
    "name": "getProduct",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "product_id",
            "description": "<p>对应商品id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/product/getProduct"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Product.php",
    "groupTitle": "index_Product"
  },
  {
    "type": "post",
    "url": "/",
    "title": "解决方案",
    "description": "<p>getSolution</p>",
    "group": "index_Solution",
    "name": "getSolution",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": true,
            "field": "solution_id",
            "description": "<p>对应商品id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "pageNum",
            "description": "<p>条数</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3002.type参数错误 / 3003.pid参数错误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/solution/getSolution"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Solution.php",
    "groupTitle": "index_Solution"
  },
  {
    "type": "post",
    "url": "/",
    "title": "开通分配子账户",
    "description": "<p>apportionSonUser</p>",
    "group": "index_user",
    "name": "apportionSonUser",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "nick_name",
            "description": "<p>子账户用户名</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "user_type",
            "description": "<p>账户类型1.个人账户2.企业账户</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "email",
            "description": "<p>手机号</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 3000:没有该用户 / 3001:手机号格式错误 / 3002:缺少con_id / 3003:conId有误查不到uid</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>用户加密id</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/apportionSonUser"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "通过con_id获取用户信息",
    "description": "<p>getUser</p>",
    "group": "index_user",
    "name": "getuser",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "con_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 3000:没有该用户 / 3001:con_id长度只能是32位 / 3002:缺少con_id / 3003:conId有误查不到uid</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": "<p>用户加密id</p>"
          },
          {
            "group": "data",
            "type": "Number",
            "optional": false,
            "field": "user_type",
            "description": "<p>1.普通账户2.总店账户</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "nick_name",
            "description": "<p>微信昵称</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "true_name",
            "description": "<p>真实姓名</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "brithday",
            "description": "<p>生日</p>"
          },
          {
            "group": "data",
            "type": "Date",
            "optional": false,
            "field": "create_time",
            "description": "<p>注册时间</p>"
          },
          {
            "group": "data",
            "type": "Double",
            "optional": false,
            "field": "money",
            "description": "<p>剩余金额（现金）</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/getuser"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "账号密码登录",
    "description": "<p>login</p>",
    "group": "index_user",
    "name": "login",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>接收的手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "password",
            "description": "<p>密码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  3001:手机格式有误 / 3002:账号不存在 / 3003:密码错误 / 3004:登录失败</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/login"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "手机快捷登录",
    "description": "<p>quickLogin</p>",
    "group": "index_user",
    "name": "quickLogin",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>接收的手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "vercode",
            "description": "<p>验证码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  3001:手机格式有误  / 3002:code码错误 / 3004:验证码格式有误 / 3005:新用户需授权 / 3006:验证码错误 / 3009:该微信号已绑定手机号</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/quicklogin"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "账户资质提交",
    "description": "<p>recordUserQualification</p>",
    "group": "index_user",
    "name": "recordUserQualification",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "company_name",
            "description": "<p>主办单位或者主办人全称</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "company_type",
            "description": "<p>主办单位性质:1,国防机构;2,政府机关;3,事业单位;4,企业;5,个人;6社会团体;7,民办非企业单位;8,基金会;9,律师执业机构;10,外国在华文化中心;11,群众性团体组织;12,司法鉴定机构;13,宗教团体;14,境外机构;15,医疗机构;16,公证机构</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "company_certificate_type",
            "description": "<p>主办单位证件类型:1,营业执照（个人或企业）;3,组织机构代码证;4,事业单位法人证书;5,部队代号;9,组织机构代码证;12,组织机构代码证;13,统一社会信用代码证书;23,军队单位对外有偿服务许可证;27,外国企业常驻代表机构登记证</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "company_certificate_num",
            "description": "<p>主办单位证件号码</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "province_id",
            "description": "<p>省份id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "city_id",
            "description": "<p>城市id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "county_id",
            "description": "<p>地区id</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "organizers_name",
            "description": "<p>主办单位或主办人名称</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "identity_address",
            "description": "<p>主办单位证件住所</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mailingAddress_address",
            "description": "<p>主办单位通讯地址(地区级)</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "user_supp_address",
            "description": "<p>主办单位通讯地址(街道门牌号级)</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "investor",
            "description": "<p>投资人或主管单位</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "entity_responsible_person_name",
            "description": "<p>负责人姓名</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "entity_responsible_person_identity_types",
            "description": "<p>负责人证件类型(参照【主办单位证件类型】)</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "entity_responsible_person_identity_num",
            "description": "<p>负责人证件号码</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "entity_responsible_person_mobile_phone",
            "description": "<p>联系方式1</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "entity_responsible_person_phone",
            "description": "<p>联系方式2</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "entity_responsible_person_msn",
            "description": "<p>应急联系电话</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "entity_responsible_person_email",
            "description": "<p>电子邮件地址</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": true,
            "field": "entity_remark",
            "description": "<p>留言</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:id不存在或者不为数字 / 3002:price格式错误 / 3003:price不能小于0 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/recordUserQualification"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "重置密码",
    "description": "<p>resetPassword</p>",
    "group": "index_user",
    "name": "resetPassword",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>接收的手机号</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "vercode",
            "description": "<p>验证码</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "password",
            "description": "<p>新密码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  3001:手机格式有误 / 3002:该手机未注册 / 3003:更新失败 / 3004:验证码格式有误 / 3005:密码强度不够 / 3006:验证码错误</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/resetpassword"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "设置子账户用户服务项目",
    "description": "<p>seetingUserEquities</p>",
    "group": "index_user",
    "name": "seetingUserEquities",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "mobile",
            "description": "<p>账户手机号</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "business_id",
            "description": "<p>服务id</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": true,
            "field": "agency_price",
            "description": "<p>代理价格，默认统一代理商服务价格</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:手机号格式错误 / 3002:agency_price格式错误 / 3003:母账户无该项服务业务 / 3004:代理价格不能低于服务商价格 / 3005:该服务已添加 / 3006:子账户服务无法设置 / 3007:business_id格式错误或者不存在 / 3008:该账户非此账户的子账户</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/seetingUserEquities"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "发送验证码短信",
    "description": "<p>sendVercode</p>",
    "group": "index_user",
    "name": "sendVercode",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>接收的手机号</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "stype",
            "description": "<p>验证码类型 1.注册 2.修改密码 3.快捷登录 4.银行卡绑卡验证 5.报名手机验证码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  3001:手机格式有误 / 3002:发送类型有误 / 3003:一分钟内不能重复发送 / 3004:短信发送失败</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/sendvercode"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "用户注册",
    "description": "<p>userRegistered</p>",
    "group": "index_user",
    "name": "userRegistered",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "nick_name",
            "description": "<p>用户姓名</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "user_type",
            "description": "<p>用户类型1.个人账户2.企业账户</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "passwd",
            "description": "<p>密码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "email",
            "description": "<p>邮箱</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "vercode",
            "description": "<p>验证码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:手机号格式错误 / 3002:passwd密码强度不够 / 3003:邮箱格式错误 / 3004:验证码错误 / 3005:该手机号已注册用户 / 3006:用户类型错误 / 3007:nick_name不能为空</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/index/user/userRegistered"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/index/controller/Users.php",
    "groupTitle": "index_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "查询短信记录",
    "description": "<p>getSms</p>",
    "group": "notify_note",
    "name": "getSms",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "phone",
            "description": "<p>手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "date",
            "description": "<p>日期Ymd</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:手机号格式有误 / 3002:日期不符合规范</p>"
          },
          {
            "group": "返回",
            "type": "json",
            "optional": false,
            "field": "data",
            "description": ""
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/notify/note/getSms"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/notify/controller/Note.php",
    "groupTitle": "notify_note"
  },
  {
    "type": "post",
    "url": "/",
    "title": "短信营销群发",
    "description": "<p>sendContent</p>",
    "group": "notify_note",
    "name": "sendContent",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "phones",
            "description": "<p>手机号</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "content",
            "description": "<p>短信内容</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:发送失败 / 3001:手机号格式有误 / 3002:短信内容不能为空</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/notify/note/sendcontent"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/notify/controller/Note.php",
    "groupTitle": "notify_note"
  },
  {
    "type": "post",
    "url": "/",
    "title": "短信验证码发送",
    "description": "<p>sendSms</p>",
    "group": "notify_note",
    "name": "sendSms",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "phone",
            "description": "<p>手机号</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "code",
            "description": "<p>验证码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:发送失败 / 3001:手机号格式有误</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/notify/note/sendSms"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/notify/controller/Note.php",
    "groupTitle": "notify_note"
  },
  {
    "type": "post",
    "url": "/",
    "title": "支付订单",
    "description": "<p>pay</p>",
    "group": "pay_wxpay",
    "name": "pay",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "order_no",
            "description": "<p>订单号</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "payment",
            "description": "<p>1.普通订单 2.购买会员订单 3.虚拟商品订单</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "platform",
            "description": "<p>环境 1.小程序 2.公众号(默认1)</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:不存在需要支付的订单 / 3001.订单号错误 / 3002.订单类型错误 / 3004:订单已取消 / 3005:订单已关闭 / 3006:订单已付款 3007:订单已过期 / 3008:第三方支付已付款 / 3009:支付方式暂不支持 / 3010:创建支付订单失败 / 3011:code有误</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "parameters",
            "description": "<p>发起支付加密数据</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/pay/pay/pay"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/pay/controller/Pay.php",
    "groupTitle": "pay_wxpay"
  },
  {
    "type": "post",
    "url": "/",
    "title": "微信支付回调",
    "description": "<p>wxPayCallback</p>",
    "group": "pay_wxpay",
    "name": "wxPayCallback",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "order_no",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:未获取到数据 / 3001.skuid错误 / 3002.con_id错误 /3003:city_id必须为数字 / 3004:商品售罄 / 3005:商品未加入购物车 / 3006:商品不支持配送 3007:商品库存不够</p>"
          },
          {
            "group": "返回",
            "type": "Int",
            "optional": false,
            "field": "goods_count",
            "description": "<p>购买商品总数</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/pay/pay/wxPayCallback"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/pay/controller/Pay.php",
    "groupTitle": "pay_wxpay"
  },
  {
    "type": "post",
    "url": "/",
    "title": "删除商品详情和轮播图",
    "description": "<p>delPromoteImage</p>",
    "group": "supadmin_promote",
    "name": "delPromoteImage",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "image_path",
            "description": "<p>商品id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:图片不能为空 / 3002:图片不存在 / 3003:上传失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/promote/delPromoteImage"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/Promote.php",
    "groupTitle": "supadmin_promote"
  },
  {
    "type": "post",
    "url": "/",
    "title": "活动图片详情",
    "description": "<p>getPromoteimagedetail</p>",
    "group": "supadmin_promote",
    "name": "getPromoteimagedetail",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "promote_id",
            "description": "<p>活动id</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3002:promote_id不存在</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/promote/getPromoteimagedetail"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/Promote.php",
    "groupTitle": "supadmin_promote"
  },
  {
    "type": "post",
    "url": "/",
    "title": "推广活动报名列表",
    "description": "<p>getSupPromoteSignUp</p>",
    "group": "supadmin_promote",
    "name": "getSupPromoteSignUp",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "promote_id",
            "description": "<p>活动ID</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "page",
            "description": "<p>页数</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "page_num",
            "description": "<p>每页条数(默认10)</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "study_name",
            "description": "<p>姓名</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "study_mobile",
            "description": "<p>电话</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "sex",
            "description": "<p>性别 1男 2女</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "start_time",
            "description": "<p>开始时间</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": true,
            "field": "end_time",
            "description": "<p>结束时间</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:列表为空 / 3001:page错误 / 3002:promote_id错误 / 3003:时间格式错误 / 3004:性别格式错误</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "study_name",
            "description": "<p>学员姓名</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "study_mobile",
            "description": "<p>学员手机号</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "sex",
            "description": "<p>性别 1男 2女</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "age",
            "description": "<p>年龄</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "signinfo",
            "description": "<p>报名内容</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "create_time",
            "description": "<p>报名时间</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/promote/getSupPromoteSignUp"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/Promote.php",
    "groupTitle": "supadmin_promote"
  },
  {
    "type": "post",
    "url": "/",
    "title": "对商品图进行排序",
    "description": "<p>sortPromoteimagedetail</p>",
    "group": "supadmin_promote",
    "name": "sortImageDetail",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "image_path",
            "description": "<p>商品id</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "order_by",
            "description": "<p>排序</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:图片不能为空 / 3002:图片不存在 / 3003:排序字段只能为数字或者排序最大为999 / 3004:修改失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/promote/sortPromoteimagedetail"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/Promote.php",
    "groupTitle": "supadmin_promote"
  },
  {
    "type": "post",
    "url": "/",
    "title": "提交活动详情和轮播图",
    "description": "<p>uploadPromoteImages</p>",
    "group": "supadmin_promote",
    "name": "uploadPromoteImages",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "cms_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "image_type",
            "description": "<p>图片类型 1.详情图 2.轮播图</p>"
          },
          {
            "group": "入参",
            "type": "Number",
            "optional": false,
            "field": "promote_id",
            "description": "<p>活动id</p>"
          },
          {
            "group": "入参",
            "type": "Array",
            "optional": false,
            "field": "images",
            "description": "<p>图片集合</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:图片类型有误 / 3002:商品id只能是数字 / 3003:图片不能空 / 3004:商品id不存在 / 3005:图片没有上传过 / 3006:上传失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/promote/uploadPromoteImages"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/Promote.php",
    "groupTitle": "supadmin_promote"
  },
  {
    "type": "post",
    "url": "/",
    "title": "上传单个图片",
    "description": "<p>uploadFile</p>",
    "group": "supadmin_upload",
    "name": "uploadFilee",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "file",
            "optional": false,
            "field": "image",
            "description": "<p>图片</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  / 3001:上传的不是图片 / 3002:上传图片不能超过2M / 3003:上传失败 / 3004:上传文件不能为空</p>"
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>结果</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/upload/uploadfile"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/Upload.php",
    "groupTitle": "supadmin_upload"
  },
  {
    "type": "post",
    "url": "/",
    "title": "添加推广",
    "description": "<p>addPromote</p>",
    "group": "supadmin_user",
    "name": "addPromote",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>标题</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "big_image",
            "description": "<p>大图</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "share_title",
            "description": "<p>微信转发分享标题</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "share_image",
            "description": "<p>微信转发分享图片</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "share_count",
            "description": "<p>需要分享次数</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "bg_image",
            "description": "<p>分享成功页面图片</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:title不能为空 / 3002:share_title不能为空 / 3003:big_image未上传 / 3004:share_image未上传 / 3005:bg_image未上传 / 3006:big_image图片没有上传过 / 3007:share_image图片没有上传过 / 3008:bg_image图片没有上传过 / 3009:share_count有误 / 3010:添加失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/user/addpromote"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/User.php",
    "groupTitle": "supadmin_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "编辑推广",
    "description": "<p>editPromote</p>",
    "group": "supadmin_user",
    "name": "editPromote",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>标题</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "big_image",
            "description": "<p>大图</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "share_title",
            "description": "<p>微信转发分享标题</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "share_image",
            "description": "<p>微信转发分享图片</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "share_count",
            "description": "<p>需要分享次数</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "bg_image",
            "description": "<p>分享成功页面图片</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 /3000:推广活动不存在 / 3001:title不能为空 / 3002:share_title不能为空 / 3006:big_image图片没有上传过 / 3007:share_image图片没有上传过 / 3008:bg_image图片没有上传过 / 3009:share_count有误 / 3010:修改失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/user/editpromote"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/User.php",
    "groupTitle": "supadmin_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "推广活动详情",
    "description": "<p>getPromoteInfo</p>",
    "group": "supadmin_user",
    "name": "getPromoteInfo",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:列表为空 / 3001:id错误 / 3002:详情id不存在</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>标题</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "big_image",
            "description": "<p>大图</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "share_title",
            "description": "<p>微信转发分享标题</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "share_image",
            "description": "<p>微信转发分享图片</p>"
          },
          {
            "group": "data",
            "type": "Int",
            "optional": false,
            "field": "share_count",
            "description": "<p>需要分享次数</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "bg_image",
            "description": "<p>分享成功页面图片</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/user/getpromoteinfo"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/User.php",
    "groupTitle": "supadmin_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "推广活动列表",
    "description": "<p>getPromoteList</p>",
    "group": "supadmin_user",
    "name": "getPromoteList",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": false,
            "field": "page",
            "description": "<p>页数</p>"
          },
          {
            "group": "入参",
            "type": "Int",
            "optional": true,
            "field": "page_num",
            "description": "<p>每页条数(默认10)</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3000:列表为空 / 3001:page错误</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": ""
          }
        ],
        "data": [
          {
            "group": "data",
            "type": "Int",
            "optional": false,
            "field": "id",
            "description": ""
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "title",
            "description": "<p>标题</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "big_image",
            "description": "<p>大图</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "share_title",
            "description": "<p>微信转发分享标题</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "share_image",
            "description": "<p>微信转发分享图片</p>"
          },
          {
            "group": "data",
            "type": "Int",
            "optional": false,
            "field": "share_count",
            "description": "<p>需要分享次数</p>"
          },
          {
            "group": "data",
            "type": "String",
            "optional": false,
            "field": "bg_image",
            "description": "<p>分享成功页面图片</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/user/getpromoteList"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/User.php",
    "groupTitle": "supadmin_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "获取管理员信息",
    "description": "<p>getSupUser</p>",
    "group": "supadmin_user",
    "name": "getSupUser",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功  / 5000:请重新登录 2.5001:账号已停用</p>"
          },
          {
            "group": "返回",
            "type": "Array",
            "optional": false,
            "field": "data",
            "description": "<p>用户信息</p>"
          },
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "admin_name",
            "description": "<p>管理员名</p>"
          },
          {
            "group": "返回",
            "type": "data",
            "optional": false,
            "field": "stype",
            "description": "<p>用户类型 1.后台管理员 2.超级管理员</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/user/getsupuser"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/User.php",
    "groupTitle": "supadmin_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "修改密码",
    "description": "<p>resetPassword</p>",
    "group": "supadmin_user",
    "name": "resetPassword",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "sup_con_id",
            "description": ""
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "passwd",
            "description": "<p>用户密码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "new_passwd1",
            "description": "<p>新密码</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "new_passwd2",
            "description": "<p>确认密码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:密码错误 / 3002:密码必须为6-16个任意字符 / 3003:老密码不能为空 / 3004:密码确认有误  / 3005:修改密码失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/user/resetpassword"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/User.php",
    "groupTitle": "supadmin_user"
  },
  {
    "type": "post",
    "url": "/",
    "title": "后台登录",
    "description": "<p>sup_login</p>",
    "group": "supadmin_user",
    "name": "sup_login",
    "parameter": {
      "fields": {
        "入参": [
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "mobile",
            "description": "<p>手机号</p>"
          },
          {
            "group": "入参",
            "type": "String",
            "optional": false,
            "field": "passwd",
            "description": "<p>密码</p>"
          }
        ]
      }
    },
    "success": {
      "fields": {
        "返回": [
          {
            "group": "返回",
            "type": "String",
            "optional": false,
            "field": "code",
            "description": "<p>200:成功 / 3001:手机号密码不能为空 / 3002:用户不存在 / 3003:密码错误 / 3004:登录失败</p>"
          }
        ]
      }
    },
    "sampleRequest": [
      {
        "url": "http://127.0.0.1:1006/supadmin/user/login"
      }
    ],
    "version": "0.0.0",
    "filename": "./application/supadmin/controller/User.php",
    "groupTitle": "supadmin_user"
  }
] });
